<?php
namespace  Academy\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes\Sanitizer;
use Academy\Classes\AbstractAjaxHandler;
use Throwable;
use Exception;
use Academy\Lesson\LessonApi\Lesson as LessonApi;
class Lesson extends AbstractAjaxHandler {
	public function __construct() {
		$this->actions = array(
			'import_lessons' => array(
				'callback' => array( $this, 'import_lessons' ),
			),
			'render_lesson' => array(
				'callback' => array( $this, 'render_lesson' ),
				'allow_visitor_action' => true
			),
			'lesson_slug_unique_check' => array(
				'callback' => array( $this, 'lesson_slug_unique_check' ),
				'capability' => 'manage_academy_instructor',
			),
			'save_lesson_note' => array(
				'callback' => array( $this, 'save_lesson_note' ),
				'capability' => 'read',
			),
			'get_lesson_note' => array(
				'callback' => array( $this, 'get_save_lesson_note' ),
				'capability' => 'read',
			)
		);
	}
	public function import_lessons() {
		if ( ! isset( $_FILES['upload_file'] ) ) {
			wp_send_json_error( __( 'Upload File is empty.', 'academy' ) );
		}

		$file = $_FILES['upload_file'];
		if ( 'csv' !== pathinfo( $file['name'] )['extension'] ) {
			wp_send_json_error( __( 'Wrong File Format! Please import csv file.', 'academy' ) );
		}

		$link_header = [];
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		$file_open = fopen( $file['tmp_name'], 'r' );
		if ( false !== $file_open ) {
			$results = [];
			$count   = 0;
			$user_id = get_current_user_id();
			// phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			while ( false !== ( $item = fgetcsv( $file_open ) ) ) {
				if ( 0 === $count ) {
					$link_header = array_map( 'strtolower', $item );
					$count++;
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

				$user                  = get_user_by( 'login', $item['author'] );
				$allowed_tags          = wp_kses_allowed_html( 'post' );
				$allowed_tags['input'] = array(
					'type'  => true,
					'name'  => true,
					'value' => true,
					'class' => true,
				);
				$allowed_tags['form']  = array(
					'action' => true,
					'method' => true,
					'class'  => true,
				);
				$allowed_tags['iframe'] = array(
					'src'             => true,
					'width'           => true,
					'height'          => true,
					'frameborder'     => true,
					'allow'           => true,
					'allowfullscreen' => true,
				);
				$content               = wp_kses( $item['content'], $allowed_tags );

	



				try {				
					$lesson = LessonApi::create( [
						'lesson_author'  => $user ? $user->ID : $user_id,
						'lesson_title'   => sanitize_text_field( $item['title'] ),
						'lesson_name'    => \Academy\Helper::generate_unique_lesson_slug( $item['title'] ),
						'lesson_content' => $content,
						'lesson_status'  => $item['status'],
					], [
						'featured_media' => 0,
						'attachment'     => 0,
						'is_previewable' => sanitize_text_field( $item['is_previewable'] ),
						'video_duration' => sanitize_text_field( $item['video_duration'] ),
						'video_source'   => [
							'type' => sanitize_text_field( $item['video_source_type'] ),
							'url'  => $this->sanitize_video_source( $item['video_source_type'], $item['video_source_url'] ),
						],
					] );
					$lesson->save();
					$results[] = __( 'Successfully Imported', 'academy' ) . ' - ' . $item['title'];
				}
				catch ( Throwable $e ) {
					$results[] = __( 'An error occured', 'academy' ) . ' - ' . $item['title'];
				}
			}//end while
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
			fclose( $file_open );

			wp_send_json_success( $results );
		}//end if
		wp_send_json_error( __( 'Failed to open the file', 'academy' ) );
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
				$lesson['lesson_content'] = [
					'raw' => stripslashes( $lesson['lesson_content'] ),
					'rendered' => \Academy\Helper::get_content_html( stripslashes( $lesson['lesson_content'] ) ),
				];
				
				$lesson['author_name'] = get_the_author_meta(  'display_name', $lesson['lesson_author'] );

				if ( ! empty( $lesson['meta']['attachment'] ?? '' ) ) {
					$lesson['meta']['attachment'] = wp_get_attachment_url( $lesson['meta']['attachment'] );
				}
				
				if ( ! empty( $lesson['meta']['video_source'] ?? '' ) ) {
					$video = $lesson['meta']['video_source'];
					if ( 'html5' === $video['type'] && isset( $video['id'] ) ) {
						$attachment_id = (int) $video['id'];
						$att_url       = wp_get_attachment_url( $attachment_id );
						$video['url']  = $att_url;
					} elseif ( 'youtube' === $video['type'] ) {
						$video['url'] = \Academy\Helper::youtube_id_from_url( $video['url'] );
					} elseif ( 'vimeo' === $video['type'] ) {
						$video['url'] = \Academy\Helper::youtube_id_from_url( $video['url'] );
					} elseif ( 'embedded' === $video['type'] ) {
						$video['url'] = \Academy\Helper::parse_embedded_url( wp_unslash( $video['url'] ) );
					} elseif ( 'external' === $video['type'] ) {
						// first check external URL contain html5 video or not
						if ( \Academy\Helper::is_html5_video_link( $video['url'] ) ) {
							$video['type'] = 'html5';
							$embed_url = \Academy\Helper::get_basic_url_to_embed_url( $video['url'] );
							if ( isset( $embed_url['url'] ) && ! empty( $embed_url['url'] ) ) {
								$video['url'] = $embed_url['url'];
							}
						} else {
							$video['url'] = \Academy\Helper::get_basic_url_to_embed_url( $video['url'] );
						}
					} else {
						$video['type'] = 'external';
						$video['url'] = $video['url'];
					}//end if
					$lesson['meta']['video_source'] = $video;
				}//end if
			
				wp_send_json_success( $lesson );
			}
			catch ( Throwable $e ) {
				wp_send_json_success( $e->getMessage(), 422 );
			}
		}//end if
		wp_send_json_error( array( 'message' => __( 'Access Denied', 'academy' ) ) );
	}
	public function lesson_slug_unique_check( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'ID' => 'integer',
			'lesson_name' => 'string',
		], $payload_data );

	
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
	public function save_lesson_note( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'user_id'   => 'integer',
			'course_id' => 'integer',
		], $payload_data );

		$user_id   = $payload['user_id'] ?? get_current_user_id();
		$course_id = $payload['course_id'] ?? 0;
		$note      = isset( $payload_data['note'] ) ? wp_kses_post( $payload_data['note'] ) : '';
		$meta_key = "academy_{$course_id}lesson_note_{$user_id}";
		update_user_meta( $user_id, $meta_key, $note );

		wp_send_json_success(
			esc_html__( 'Successfully saved your lesson note.', 'academy' )
		);
	}

	public function get_save_lesson_note( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'user_id'   => 'integer',
			'course_id' => 'integer',
		], $payload_data );

		$user_id   = $payload['user_id'] ?? get_current_user_id();
		$course_id = $payload['course_id'] ?? 0;

		$meta_key = "academy_{$course_id}lesson_note_{$user_id}";
		$previous_note = get_user_meta( $user_id, $meta_key, true );

		wp_send_json_success( $previous_note );
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
