<?php
namespace AcademyWebhooks\Listeners;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyWebhooks\Classes\Payload;
use AcademyWebhooks\Interfaces\ListenersInterface;

class QuizAttemptStatusPending implements ListenersInterface {
	public static function dispatch( $deliver_callback, $webhook ) {
		add_action(
			'academy/frontend/quiz_attempt_status_pending',
			function( $attempt_quiz ) use ( $deliver_callback, $webhook ) {
				call_user_func_array(
					$deliver_callback,
					array(
						$webhook,
						self::get_payload( $attempt_quiz )
					)
				);
			}, 10
		);
	}

	public static function get_payload( $attempt_quiz ) {
		$data = array();
		if ( is_object( $attempt_quiz ) && 'pending' === $attempt_quiz->attempt_status ) {
			$data = Payload::get_quiz_attempt_object_data( $attempt_quiz );
		} elseif ( 'pending' === $attempt_quiz['attempt_status'] ) {
			$data = Payload::get_quiz_attempt_array_data( $attempt_quiz );
		}

		return apply_filters( 'academy_webhooks/quiz_attempt_status_pending', $data );
	}
}
