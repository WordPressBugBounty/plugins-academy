<?php
namespace AcademyWebhooks\Listeners;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyWebhooks\Classes\Payload;
use AcademyWebhooks\Interfaces\ListenersInterface;

class AssignmentCompleted implements ListenersInterface {
	public static function dispatch( $deliver_callback, $webhook ) {
		add_action(
			'academy/frontend/after_mark_topic_complete',
			function( $topic_type, $course_id, $assignment_id, $user_id ) use ( $deliver_callback, $webhook ) {
				call_user_func_array(
					$deliver_callback,
					array(
						$webhook,
						self::get_payload( $topic_type, $course_id, $assignment_id, $user_id )
					)
				);
			}, 10, 4
		);

	}

	public static function get_payload( $topic_type, $course_id, $assignment_id, $user_id ) {

		if ( 'assignment' === $topic_type ) {
			$assignment = get_post( $assignment_id );
			$assignment_data = array_merge(
				[
					'is_completed' => 1
				],
				Payload::get_assignment_data( $assignment ),
			);
			$data = array_merge( $assignment_data,
				[ '_course' => Payload::get_course_data( $course_id ) ],
				[ '_user' => Payload::get_user_data( $user_id ) ],
			);

			return apply_filters( 'academy_webhooks/assignment_published_payload', $data );
		}
	}
}
