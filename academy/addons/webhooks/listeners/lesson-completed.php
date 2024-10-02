<?php
namespace AcademyWebhooks\Listeners;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyWebhooks\Interfaces\ListenersInterface;
use AcademyWebhooks\Classes\Payload;


class LessonCompleted implements ListenersInterface {
	public static function dispatch( $deliver_callback, $webhook ) {
		add_action(
			'academy/frontend/after_mark_topic_complete',
			function( $topic_type, $course_id, $topic_id, $user_id ) use ( $deliver_callback, $webhook ) {
				call_user_func_array(
					$deliver_callback,
					array(
						$webhook,
						self::get_payload( $topic_type, $course_id, $topic_id, $user_id )
					)
				);
			}, 10, 4
		);
	}

	public static function get_payload( $topic_type, $course_id, $topic_id, $user_id ) {
		if ( 'lesson' === $topic_type ) {
			$lesson = \Academy\Helper::get_lesson( $topic_id );
			$lesson_meta = \Academy\Helper::get_lesson_meta_data( $topic_id );
			$data = array(
				'is_completed'      => 1,
				'ID'             => (int) $lesson->ID,
				'title'           => html_entity_decode( $lesson->lesson_title ),
				'date_created'    => $lesson->lesson_date,
				'date_modified'  => $lesson->lesson_modified,
				'content'        => $lesson->lesson_content,
				'excerpt'        => $lesson->lesson_excerpt,
				'featured_image' => (int) $lesson_meta['featured_media'],
				'featured_image_url' => get_permalink( $lesson_meta['featured_media'] ),
				'attachment'     => (int) $lesson_meta['attachment'],
				'attachment_url' => wp_get_attachment_url( $lesson_meta['attachment'] ),
				'previewable'    => (bool) $lesson_meta['is_previewable'],
				'duration'       => $lesson_meta['video_duration'],
				'video_source'   => $lesson_meta['video_source'],
				'_course'         => Payload::get_course_data( $course_id ),
				'_user'           => Payload::get_user_data( $user_id ),
			);
			return apply_filters( 'academy_webhooks/lessons_completed_payload', $data );
		}//end if
	}
}
