<?php
namespace AcademyWebhooks\Listeners;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyWebhooks\Interfaces\ListenersInterface;
use AcademyWebhooks\Classes\Payload;


class ReplyCourseQA implements ListenersInterface {
	public static function dispatch( $deliver_callback, $webhook ) {
		add_action(
			'academy/frontend/insert_course_qa_answered',
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

		if ( 'answered' === $comment['status'] ) {
			$instructor = '';
			$status = get_user_meta( $comment['author'], 'academy_instructor_status', true );
			$comment_data = get_comment( $comment['id'] );
			$new_comment = Payload::get_question_data( $comment_data );

			// unset question title
			unset( $new_comment['title'] );

			if ( 'approved' === $status ) {
				$instructor = 'academy_instructor';
			}

			$parent_comment = get_comment( $comment['parent'] );
			$new_comment['sender'] = $instructor;

			$update_reply_comment = array_merge( $new_comment,
				[ '_question' => Payload::get_question_data( $parent_comment ) ],
				[ '_course' => Payload::get_course_data( $comment['post'] ) ]
			);

			return apply_filters( 'academy_webhooks/new_reply_to_question_payload', $update_reply_comment );
		}//end if
	}
}
