<?php
namespace AcademyWebhooks\Listeners;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

use AcademyWebhooks\Classes\Payload;
use AcademyWebhooks\Interfaces\ListenersInterface;

class QuizCompleted implements ListenersInterface {

	public static function dispatch( $deliver_callback, $webhook ) {
		add_action(
			'academy/frontend/after_mark_topic_complete',
			function ( $topic_type, $course_id, $topic_id, $user_id ) use (
				$deliver_callback,
				$webhook
			) {
				call_user_func_array($deliver_callback, [
					$webhook,
					self::get_payload(
						$topic_type,
						$course_id,
						$topic_id,
						$user_id
					),
				]);
			},
			10,
			4
		);
	}

	public static function get_payload(
		$topic_type,
		$course_id,
		$topic_id,
		$user_id
	) {
		if ( 'quiz' === $topic_type ) {
			$quiz = get_post( $topic_id );
			$quiz_data = array_merge(
				[
					'is_completed' => 1,
				],
				Payload::get_quiz_data( $quiz )
			);
			$course_data = array_merge(
				[ '_course' => Payload::get_course_data( $course_id ) ],
				[ '_user' => Payload::get_user_data( $user_id ) ]
			);
			$quiz_completed = array_merge( $quiz_data, $course_data );
			return apply_filters(
				'academy_webhooks/quiz_completed_payload',
				$quiz_completed
			);
		}
		return [];
	}
}
