<?php
namespace AcademyWebhooks\Listeners;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyWebhooks\Classes\Payload;
use AcademyWebhooks\Interfaces\ListenersInterface;


class QuizPublished implements ListenersInterface {
	public static function dispatch( $deliver_callback, $webhook ) {
		add_action(
			'rest_after_insert_academy_quiz',
			function( $quiz ) use ( $deliver_callback, $webhook ) {
				call_user_func_array(
					$deliver_callback,
					array(
						$webhook,
						self::get_payload( $quiz )
					)
				);
			}, 10
		);

	}

	public static function get_payload( $quiz ) {
		$data = Payload::get_quiz_data( $quiz );

		return apply_filters( 'academy_webhooks/quiz_published_payload', $data );
	}
}
