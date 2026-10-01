<?php
namespace Academy\Ajax;

use Academy\Classes\AbstractAjaxHandler;
use Academy\Classes\Sanitizer;
use Academy\Lesson\LessonApi\Lesson as LessonApi;
use Throwable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lesson extends AbstractAjaxHandler {

	public function __construct() {
		$this->actions = array(
			'import_lessons' => array(
				'callback' => array( $this, 'import_lessons' ),
			),
			'render_lesson' => array(
				'callback'              => array( $this, 'render_lesson' ),
				'allow_visitor_action'  => true,
			),
			'lesson_slug_unique_check' => array(
				'callback'   => array( $this, 'lesson_slug_unique_check' ),
				'capability' => 'manage_academy_instructor',
			),
			'save_lesson_note' => array(
				'callback'   => array( $this, 'save_lesson_note' ),
				'capability' => 'read',
			),
			'get_lesson_note' => array(
				'callback'   => array( $this, 'get_save_lesson_note' ),
				'capability' => 'read',
			),
			'complete_lesson_video' => array(
				'callback'   => array( $this, 'complete_lesson_video' ),
				'capability' => 'read',
			),
			'save_lesson_video_progress' => array(
				'callback'   => array( $this, 'save_lesson_video_progress' ),
				'capability' => 'read',
			),
			'save_external_video_dwell' => array(
				'callback'   => array( $this, 'save_external_video_dwell' ),
				'capability' => 'read',
			),
		);
	}

	public function import_lessons() {
		// Nonce + capability are verified by AbstractAjaxHandler::handle_ajax_request().
		if ( ! isset( $_FILES['upload_file']['tmp_name'], $_FILES['upload_file']['name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			wp_send_json_error( __( 'Upload File is empty.', 'academy' ) );
		}

		$file = $_FILES['upload_file']; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- uploaded-file array; only the PHP tmp path is read.

		if ( 'csv' !== pathinfo( $file['name'], PATHINFO_EXTENSION ) ) {
			wp_send_json_error(
				__( 'Wrong File Format! Please import csv file.', 'academy' )
			);
		}

		$link_header = array();

		$file_open = fopen( $file['tmp_name'], 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- reads the PHP upload tmp file

		if ( false === $file_open ) {
			wp_send_json_error( __( 'Failed to open the file', 'academy' ) );
		}

		$results = array();
		$count   = 0;
		$user_id = get_current_user_id();

		while ( true ) {
			$item = fgetcsv( $file_open );
			if ( false === $item ) {
				break;
			}
			if ( 0 === $count ) {
				$link_header = array_map( 'strtolower', $item );
				++$count;
				continue;
			}
			$item = array_combine( $link_header, $item );

			if ( empty( $item['title'] ) ) {
				$results[] = __( 'Empty lesson data', 'academy' );
				continue;
			}

			if ( \Academy\Helper::is_lesson_slug_exists( sanitize_title( $item['title'] ) ) ) {
				$results[] = __( 'Already Exists', 'academy' ) . ' - ' . $item['title'];
				continue;
			}

			$user = get_user_by( 'login', $item['author'] );

			$allowed_tags             = wp_kses_allowed_html( 'post' );
			$allowed_tags['input']    = array(
				'type'  => true,
				'name'  => true,
				'value' => true,
				'class' => true,
			);
			$allowed_tags['form']     = array(
				'action' => true,
				'method' => true,
				'class'  => true,
			);
			$allowed_tags['iframe']   = array(
				'src'             => true,
				'width'           => true,
				'height'          => true,
				'frameborder'     => true,
				'allow'           => true,
				'allowfullscreen' => true,
			);

			$content = wp_kses( $item['content'], $allowed_tags );

			try {
				$lesson = LessonApi::create(
					array(
						'lesson_author'  => $user ? $user->ID : $user_id,
						'lesson_title'   => sanitize_text_field( $item['title'] ),
						'lesson_name'    => \Academy\Helper::generate_unique_lesson_slug( $item['title'] ),
						'lesson_content' => $content,
						'lesson_status'  => $item['status'],
					),
					array(
						'featured_media' => 0,
						'attachment'     => 0,
						'is_previewable' => sanitize_text_field( $item['is_previewable'] ),
						'video_duration' => sanitize_text_field( $item['video_duration'] ),
						'video_source'   => array(
							'type' => sanitize_text_field( $item['video_source_type'] ),
							'url'  => $this->sanitize_video_source(
								$item['video_source_type'],
								$item['video_source_url']
							),
						),
					)
				);

				$lesson->save();

				$results[] = __( 'Successfully Imported', 'academy' ) . ' - ' . $item['title'];
			} catch ( Throwable $e ) {
				$results[] = __( 'An error occurred', 'academy' ) . ' - ' . $item['title'];
			}//end try
		}//end while

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose, WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- closes the uploaded CSV opened with fopen() above
		fclose( $file_open );

		wp_send_json_success( $results );
	}

	public function lesson_slug_unique_check( $payload_data ) {
		$payload = Sanitizer::sanitize_payload(
			array(
				'ID'          => 'integer',
				'lesson_name' => 'string',
			),
			$payload_data
		);

		if (
			\Academy\Helper::is_lesson_slug_exists(
				$payload['lesson_name'] ?? '',
				$payload['ID'] ?? null
			)
		) {
			wp_send_json_error( __( 'Slug not available', 'academy' ) );
		}

		wp_send_json_success( false );
	}

	public function render_lesson( $payload_data ) {
		check_ajax_referer( 'academy_nonce', 'security' );
		$payload = Sanitizer::sanitize_payload( array(
			'course_id' => 'integer',
			'lesson_id' => 'integer',
		), $payload_data );
		$course_id = $payload_data['course_id'];
		$lesson_id = $payload['lesson_id'];
		$user_id   = (int) get_current_user_id();

		if ( \Academy\Helper::has_permission_to_access_lesson_curriculum( $course_id, $lesson_id, $user_id ) ) {

			try {
				$lesson = LessonApi::get_by_id( $lesson_id, $meta = false, $auth = null, 'publish' )->get_data();
				do_action( 'academy/frontend/before_render_lesson', $lesson, $course_id, $lesson_id );

				$lesson['lesson_title'] = stripslashes( $lesson['lesson_title'] );

				$raw_content      = stripslashes( $lesson['lesson_content'] );
				$rendered_content = \Academy\Helper::get_content_html( $raw_content );

				$lesson['lesson_content'] = [
					'raw'      => $raw_content,
					'rendered' => $rendered_content,
					// Content with a shortcode/block or inline script can't run
					// in the SPA's DOM (assets never enqueue over AJAX). Flag it
					// so the learn page renders it through the isolated
					// full-page frame endpoint instead.
					'needs_frame' => \Academy\Helper::content_needs_frame( $raw_content, $rendered_content ),
				];

				$lesson['author_name'] = get_the_author_meta( 'display_name', $lesson['lesson_author'] );

				if ( ! empty( $lesson['meta']['featured_media'] ) ) {
					$lesson['meta']['featured_media'] = wp_get_attachment_url( $lesson['meta']['featured_media'] );
				}

				// Sent as the raw attachment ID (not resolved to a URL) so the
				// frontend can route the download through the tracked
				// download-logging endpoint instead of linking straight to
				// the media URL.
				$lesson['meta']['attachment'] = ! empty( $lesson['meta']['attachment'] ?? '' ) ? (int) $lesson['meta']['attachment'] : 0;

				if ( ! empty( $lesson['meta']['video_source'] ?? '' ) ) {
					$video = $lesson['meta']['video_source'];
					if ( 'html5' === $video['type'] && isset( $video['id'] ) ) {
						$attachment_id = (int) $video['id'];
						$att_url       = wp_get_attachment_url( $attachment_id );
						$video['url']  = $att_url;
					} elseif ( 'youtube' === $video['type'] ) {
						$video['url'] = \Academy\Helper::youtube_id_from_url( $video['url'] );
					} elseif ( 'vimeo' === $video['type'] ) {
						$video['url'] = \Academy\Helper::vimeo_id_from_url( $video['url'] );
					} elseif ( 'embedded' === $video['type'] ) {
						$embedded = \Academy\Helper::parse_embedded_url( wp_unslash( $video['url'] ) );
						$resolved = $embedded['url'] ?? '';
						// A pasted embed code that resolves to a native provider
						// plays (and is watch-tracked) through the same custom
						// player as a lesson of that type. Anything else (Canva,
						// Kaltura, …) keeps the opaque iframe fallback.
						\Academy\Helper::apply_resolved_video_provider( $video, $resolved, $embedded );
					} elseif ( 'gumlet' === $video['type'] ) {
						// The field only ever holds the raw Gumlet asset ID (see
						// LessonMeta.js's "Gumlet Asset ID" placeholder) — resolve
						// it to a real (optionally signed) play.gumlet.io embed
						// URL here so GumletPlayer gets something an iframe can
						// actually load, instead of the bare ID passing straight
						// through untouched.
						if ( \Academy\Helper::get_addon_active_status( 'gumlet-video', false ) && ! empty( $video['url'] ) ) {
							$generated    = \AcademyGumletVideo\Token::generate( $video['url'], $user_id );
							$video['url'] = $generated['signed_url'];
						}
					} elseif ( 'short_code' === $video['type'] ) {
						// A shortcode in the video field (map, player, embed …) is
						// rendered on its own through the isolated full-page frame
						// endpoint (field=video). The SPA keys off this type; no
						// server-side transform is needed here.
						$video['type'] = 'short_code';
					} elseif ( 'external' === $video['type'] ) {
						// first check external URL contain html5 video or not
						if ( \Academy\Helper::is_html5_video_link( $video['url'] ) ) {
							$video['type'] = 'html5';
							$embed_url = \Academy\Helper::get_basic_url_to_embed_url( $video['url'] );
							if ( isset( $embed_url['url'] ) && ! empty( $embed_url['url'] ) ) {
								$video['url'] = $embed_url['url'];
							}
						} else {
							$embed    = \Academy\Helper::get_basic_url_to_embed_url( $video['url'] );
							$resolved = $embed['url'] ?? '';
							// Same treatment as "Embedded" above.
							\Academy\Helper::apply_resolved_video_provider( $video, $resolved, $embed );
						}
					} elseif ( in_array( $video['type'], [ 'offline', 'online' ], true ) ) {
						$video['url'] = ! empty( $video['url'] ) ? $video['url'] : \Academy\Helper::get_settings( 'lesson_offline_class_address' );
					} else {
						$video['type'] = 'external';
						$video['url'] = $video['url'];
					}//end if
					// Subtitle file (.vtt) the custom player shows as captions.
					$video['subtitle_url']          = \Academy\Helper::get_video_subtitle_url( $video );
					$lesson['meta']['video_source'] = $video;

					// Custom player: saved resume position + completion-gate config.
					// `$video['type']` is already resolved here (external mp4 -> html5).
					$trackable = \Academy\Helper::is_trackable_video_source( $video['type'], $video['url'] );
					$threshold = (int) \Academy\Helper::get_settings( 'lessons_video_completion_threshold' );

					// Opaque third-party embeds (Wistia, Vidyard, Twitch, SoundCloud,
					// Mixcloud, Facebook, Kaltura, …) report no playback position, so
					// they get a coarse dwell-time (seconds open) gate instead.
					$dwell_trackable = ! $trackable && \Academy\Helper::is_dwell_trackable_video_source( $video['type'], $video['url'] );
					$dwell_threshold = (int) \Academy\Helper::get_settings( 'external_video_min_watch_seconds' );

					$position      = 0;
					$percent       = 0;
					$dwell_seconds = 0;
					if ( ( $trackable || $dwell_trackable ) && $user_id ) {
						$saved = get_user_meta( $user_id, "academy_{$course_id}lesson_video_{$lesson_id}_progress", true );
						if ( is_array( $saved ) ) {
							$position      = isset( $saved['position'] ) ? (float) $saved['position'] : 0;
							$percent       = isset( $saved['percent'] ) ? (int) $saved['percent'] : 0;
							$dwell_seconds = isset( $saved['dwell_seconds'] ) ? (float) $saved['dwell_seconds'] : 0;
						}
					}

					$lesson['meta']['video_progress'] = [
						'position'      => $position,
						'percent'       => $percent,
						'dwell_seconds' => $dwell_seconds,
					];
					$lesson['meta']['video_gate'] = [
						'trackable' => $trackable,
						'threshold' => $threshold,
						'lock_seek' => ( $trackable && $threshold > 0 && \Academy\Helper::get_settings( 'is_disabled_lessons_video_skip' ) ),
						'dwell_trackable' => $dwell_trackable,
						'dwell_threshold_seconds' => $dwell_threshold,
					];
				}//end if
				wp_send_json_success( $lesson );
			} catch ( Throwable $e ) {
				wp_send_json_success( $e->getMessage(), 422 );
			}//end try
		}//end if
		wp_send_json_error( array( 'message' => __( 'Access Denied', 'academy' ) ) );
	}

	public function get_save_lesson_note( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
		], $payload_data );

		// Always the current user's own note, never one named in the request.
		$user_id   = get_current_user_id();
		$course_id = $payload['course_id'] ?? 0;

		$meta_key = "academy_{$course_id}lesson_note_{$user_id}";
		$previous_note = get_user_meta( $user_id, $meta_key, true );

		wp_send_json_success( $previous_note );
	}

	public function save_lesson_note( $payload_data ) {
		$payload = Sanitizer::sanitize_payload(
			array(
				'course_id' => 'integer',
			),
			$payload_data
		);

		// Always the current user's own note, never one named in the request.
		$user_id   = get_current_user_id();
		$course_id = $payload['course_id'] ?? 0;
		$note      = isset( $payload_data['note'] ) ? wp_kses_post( $payload_data['note'] ) : '';

		$meta_key = "academy_{$course_id}lesson_note_{$user_id}";
		update_user_meta( $user_id, $meta_key, $note );

		wp_send_json_success(
			esc_html__( 'Successfully saved your lesson note.', 'academy' )
		);
	}

	public function complete_lesson_video( $payload_data ) {
		$payload = Sanitizer::sanitize_payload(
			array(
				'course_id' => 'integer',
				'topic_id'  => 'integer',
			),
			$payload_data
		);

		$user_id   = (int) get_current_user_id();
		$course_id = $payload['course_id'] ?? 0;
		$topic_id  = $payload['topic_id'] ?? 0;

		if ( ! $user_id || ! $course_id || ! $topic_id ) {
			wp_send_json_error(
				__( 'Invalid data. Please try again.', 'academy' )
			);
		}

		if ( ! \Academy\Helper::is_enrolled( $course_id, $user_id ) ) {
			wp_send_json_error( __( 'You must be enrolled in this course to mark lessons as complete.', 'academy' ) );
		}

		$meta_key     = "academy_{$course_id}lesson_video_{$topic_id}_completed";
		$is_completed = (bool) update_user_meta( $user_id, $meta_key, 1 );

		wp_send_json_success(
			array(
				'completed' => $is_completed,
			)
		);
	}

	/**
	 * Persist a student's video watch progress (resume position + watched %).
	 * When the configured completion threshold is reached, records the
	 * watch-complete flag and auto-marks the lesson topic complete.
	 *
	 * @param array $payload_data
	 */
	public function save_lesson_video_progress( $payload_data ) {
		$payload = Sanitizer::sanitize_payload(
			array(
				'course_id' => 'integer',
				'topic_id'  => 'integer',
				'percent'   => 'integer',
			),
			$payload_data
		);

		$user_id   = (int) get_current_user_id();
		$course_id = $payload['course_id'] ?? 0;
		$topic_id  = $payload['topic_id'] ?? 0;

		if ( ! $user_id || ! $course_id || ! $topic_id ) {
			wp_send_json_error( __( 'Invalid data. Please try again.', 'academy' ) );
		}

		$position = max( 0, (float) ( $payload_data['position'] ?? 0 ) );
		$furthest = max( 0, (float) ( $payload_data['furthest'] ?? 0 ) );
		$duration = max( 0, (float) ( $payload_data['duration'] ?? 0 ) );
		$percent  = min( 100, max( 0, (int) ( $payload['percent'] ?? 0 ) ) );

		update_user_meta(
			$user_id,
			"academy_{$course_id}lesson_video_{$topic_id}_progress",
			array(
				'position' => $position,
				'furthest' => $furthest,
				'duration' => $duration,
				'percent'  => $percent,
				'updated'  => \Academy\Helper::get_time(),
			)
		);

		$threshold      = (int) \Academy\Helper::get_settings( 'lessons_video_completion_threshold' );
		$watch_complete = ( $threshold <= 0 ) || ( $percent >= $threshold );
		$just_completed = false;

		if ( $threshold > 0 && $percent >= $threshold ) {
			update_user_meta( $user_id, "academy_{$course_id}lesson_video_{$topic_id}_completed", 1 );
			$just_completed = $this->auto_complete_lesson_topic( $user_id, $course_id, $topic_id );
		}

		wp_send_json_success(
			array(
				'percent'        => $percent,
				'threshold'      => $threshold,
				'watch_complete' => $watch_complete,
				'just_completed' => $just_completed,
			)
		);
	}

	/**
	 * Persist dwell time on an opaque third-party video embed (Wistia,
	 * Vidyard, Twitch, SoundCloud, Mixcloud, Facebook, Kaltura, …) — the only
	 * signal available for a cross-origin iframe KodezenPlayer can't natively
	 * drive. Gates completion on elapsed seconds instead of the
	 * percent-of-duration threshold the native players use.
	 *
	 * @param array $payload_data
	 */
	public function save_external_video_dwell( $payload_data ) {
		$payload = Sanitizer::sanitize_payload(
			array(
				'course_id' => 'integer',
				'topic_id'  => 'integer',
			),
			$payload_data
		);

		$user_id   = (int) get_current_user_id();
		$course_id = $payload['course_id'] ?? 0;
		$topic_id  = $payload['topic_id'] ?? 0;

		if ( ! $user_id || ! $course_id || ! $topic_id ) {
			wp_send_json_error( __( 'Invalid data. Please try again.', 'academy' ) );
		}

		$dwell_seconds = max( 0, (float) ( $payload_data['dwell_seconds'] ?? 0 ) );

		update_user_meta(
			$user_id,
			"academy_{$course_id}lesson_video_{$topic_id}_progress",
			array(
				'dwell_seconds' => $dwell_seconds,
				'updated'       => \Academy\Helper::get_time(),
			)
		);

		$threshold      = (int) \Academy\Helper::get_settings( 'external_video_min_watch_seconds' );
		$watch_complete = ( $threshold <= 0 ) || ( $dwell_seconds >= $threshold );
		$just_completed = false;

		if ( $threshold > 0 && $dwell_seconds >= $threshold ) {
			update_user_meta( $user_id, "academy_{$course_id}lesson_video_{$topic_id}_completed", 1 );
			$just_completed = $this->auto_complete_lesson_topic( $user_id, $course_id, $topic_id );
		}

		wp_send_json_success(
			array(
				'dwell_seconds'  => $dwell_seconds,
				'threshold'      => $threshold,
				'watch_complete' => $watch_complete,
				'just_completed' => $just_completed,
			)
		);
	}

	/**
	 * Add a lesson topic to the student's completed list (idempotent) and fire
	 * the standard completion hook so certificates/progress update normally.
	 * Returns true only when this call is what completed it.
	 *
	 * @param int $user_id
	 * @param int $course_id
	 * @param int $topic_id
	 */
	private function auto_complete_lesson_topic( $user_id, $course_id, $topic_id ) {
		if ( ! \Academy\Helper::is_enrolled( $course_id, $user_id ) ) {
			return false;
		}

		$option_name = 'academy_course_' . $course_id . '_completed_topics';
		$saved       = (array) json_decode( get_user_meta( $user_id, $option_name, true ), true );

		if ( isset( $saved['lesson'][ $topic_id ] ) ) {
			return false;
		}

		$saved['lesson'][ $topic_id ] = \Academy\Helper::get_time();
		update_user_meta( $user_id, $option_name, wp_json_encode( $saved ) );
		do_action( 'academy/frontend/after_mark_topic_complete', 'lesson', $course_id, $topic_id, $user_id );

		return true;
	}

	public function sanitize_video_source( $source, $url ) {
		switch ( $source ) {
			case 'embedded':
				return filter_var( $url, FILTER_SANITIZE_URL );

			case 'short_code':
				return wp_kses_post( $url );

			default:
				return sanitize_text_field( $url );
		}
	}
}
