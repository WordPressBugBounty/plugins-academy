<?php
namespace  Academy\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes\Sanitizer;
use Academy\Classes\AbstractAjaxHandler;

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
				'manage_academy_instructor' => true
			),
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
					$count ++;
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
				$content               = wp_kses( $item['content'], $allowed_tags );

				$lesson_id = \Academy\Classes\Query::lesson_insert( array(
					'lesson_author'  => $user ? $user->ID : $user_id,
					'lesson_title'   => sanitize_text_field( $item['title'] ),
					'lesson_name'    => \Academy\Helper::generate_unique_lesson_slug( $item['title'] ),
					'lesson_content' => $content,
					'lesson_status'  => $item['status'],
				) );

				if ( $lesson_id ) {
					\Academy\Classes\Query::lesson_meta_insert( $lesson_id, array(
						'featured_media' => 0,
						'attachment'     => 0,
						'is_previewable' => sanitize_text_field( $item['is_previewable'] ),
						'video_duration' => sanitize_text_field( $item['video_duration'] ),
						'video_source'   => wp_json_encode( array(
							'type' => sanitize_text_field( $item['video_source_type'] ),
							'url'  => $this->sanitize_video_source( $item['video_source_type'], $item['video_source_url'] ),
						) ),
					) );
					$results[] = __( 'Successfully Imported', 'academy' ) . ' - ' . $item['title'];
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
			$lesson = \Academy\Helper::get_lesson( $lesson_id );
			$lesson->lesson_title  = stripslashes( $lesson->lesson_title );
			$lesson->lesson_content  = [
				'raw' => stripslashes( $lesson->lesson_content ),
				'rendered' => \Academy\Helper::get_content_html( stripslashes( $lesson->lesson_content ) ),
			];
			$lesson->author_name = get_the_author_meta( 'display_name', $lesson->lesson_author );
			$lesson->meta            = \Academy\Helper::get_lesson_meta_data( $lesson_id );

			if ( empty( $lesson ) ) {
				wp_send_json_error( array( 'message' => __( 'Sorry, something went wrong!', 'academy' ) ) );
				wp_die();
			}

			do_action( 'academy/frontend/before_render_lesson', $lesson, $course_id, $lesson_id );

			if ( count( $lesson->meta ) > 0 ) {
				if ( isset( $lesson->meta['attachment'] ) && ! empty( $lesson->meta['attachment'] ) ) {
					$lesson->meta['attachment'] = wp_get_attachment_url( $lesson->meta['attachment'] );
				}
				if ( isset( $lesson->meta['video_source'] ) && ! empty( $lesson->meta['video_source'] ) ) {
					$video = $lesson->meta['video_source'];
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
					$lesson->meta['video_source'] = $video;
				}//end if
			}//end if
			wp_send_json_success( $lesson );
		}//end if
		wp_send_json_error( array( 'message' => __( 'Access Denied', 'academy' ) ) );
		wp_die();
	}
	public function lesson_slug_unique_check( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'ID' => 'integer',
			'lesson_name' => 'string',
		], $payload_data );

		// Updating
		if ( isset( $payload['ID'] ) && ! empty( $payload['ID'] ) ) {
			$existing_lesson = \Academy\Helper::get_lesson( $payload['ID'] );
			if ( $payload['lesson_name'] !== $existing_lesson->lesson_name ) {
				$is_slug_exists = \Academy\Helper::is_lesson_slug_exists( $payload['lesson_name'] );
				if ( $is_slug_exists ) {
					wp_send_json_error( esc_html__( 'Slug already exists. Please try a different one.', 'academy' ) );
				}
			}
		} else {
			$is_slug_exists = \Academy\Helper::is_lesson_slug_exists( $payload['lesson_name'] );
			if ( $is_slug_exists ) {
				wp_send_json_error( esc_html__( 'Slug already exists. Please try a different one.', 'academy' ) );
			}
		}
		wp_send_json_success( false );
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
