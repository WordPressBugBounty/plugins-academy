<?php
namespace AcademyWebhooks\Listeners;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyWebhooks\Classes\Payload;
use AcademyWebhooks\Interfaces\ListenersInterface;


class ZoomCompleted implements ListenersInterface {
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
		if ( 'zoom' === $topic_type ) {
			$zoom = Payload::get_zoom_data( $topic_id );
			unset( $zoom['host_email'] );
			unset( $zoom['start_url'] );
			unset( $zoom['join_url'] );
			unset( $zoom['status'] );
			$data = array_merge(
				[ 'is_completed' => 1 ],
				$zoom,
				[ '_course' => Payload::get_course_data( $course_id ) ],
				[ '_user' => Payload::get_user_data( $user_id ) ],
			);

			return apply_filters( 'academy_webhooks/zoom_completed_payload', $data );
		}

	}
}
