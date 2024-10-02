<?php
namespace AcademyWebhooks\Listeners;

use AcademyWebhooks\Classes\Payload;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyWebhooks\Interfaces\ListenersInterface;


class LessonPublished implements ListenersInterface {
	public static function dispatch( $deliver_callback, $webhook ) {
		add_action(
			'academy_new_lesson_published',
			function( $lesson ) use ( $deliver_callback, $webhook ) {
				call_user_func_array(
					$deliver_callback,
					array(
						$webhook,
						self::get_payload( $lesson )
					)
				);
			}, 10
		);
	}

	public static function get_payload( $lesson ) {

		$data = array_merge( Payload::get_lesson_data( $lesson ), array(
			'author_ID' => $lesson['lesson_author'],
			'author_name' => $lesson['author_name'],
			'author_avatar_url' => get_avatar_url( $lesson['lesson_author'] ),
		) );

		return apply_filters( 'academy_webhooks/lessons_published_payload', $data );
	}
}
