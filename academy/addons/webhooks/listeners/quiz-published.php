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
			'transition_post_status',
			function ( $new_status, $old_status, $post ) use ( $deliver_callback, $webhook ) {
				if ( 'academy_quiz' !== $post->post_type || 'publish' !== $new_status || 'publish' === $old_status ) {
					return;
				}

				call_user_func_array(
					$deliver_callback,
					array(
						$webhook,
						self::get_payload( $post )
					)
				);
			}, 10, 3
		);
	}

	public static function get_payload( $quiz ) {
		$data = Payload::get_quiz_data( $quiz );

		return apply_filters( 'academy_webhooks/quiz_published_payload', $data );
	}
}
