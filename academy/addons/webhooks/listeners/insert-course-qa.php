<?php
namespace AcademyWebhooks\Listeners;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyWebhooks\Interfaces\ListenersInterface;
use AcademyWebhooks\Classes\Payload;


class InsertCourseQA implements ListenersInterface {
	public static function dispatch( $deliver_callback, $webhook ) {
		add_action(
			'academy/frontend/insert_course_qa',
			function( $comment ) use ( $deliver_callback, $webhook ) {
				call_user_func_array(
					$deliver_callback,
					array(
						$webhook,
						self::get_payload( $comment )
					)
				);
			},
		);
	}

	public static function get_payload( $comment ) {

		if ( 'waiting_for_answer' === $comment['status'] ) {
			$comment_data = get_comment( $comment['id'] );
			$data = array_merge( Payload::get_question_data( $comment_data ), array(
				'_course'            => Payload::get_course_data( $comment['post'] ),
			) );

			return apply_filters( 'academy_webhooks/new_question_in_course_payload', $data );
		}
	}
}
