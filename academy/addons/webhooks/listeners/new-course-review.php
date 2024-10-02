<?php
namespace AcademyWebhooks\Listeners;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyWebhooks\Interfaces\ListenersInterface;
use AcademyWebhooks\Classes\Payload;


class NewCourseReview implements ListenersInterface {
	public static function dispatch( $deliver_callback, $webhook ) {
		add_action(
			'academy/frontend/after_course_rating',
			function( $comment_id, $comment_post_id, $rating ) use ( $deliver_callback, $webhook ) {
				call_user_func_array(
					$deliver_callback,
					array(
						$webhook,
						self::get_payload( $comment_id, $comment_post_id, $rating )
					)
				);
			}, 10, 3
		);
	}

	public static function get_payload( $comment_id, $comment_post_id, $rating ) {
		$comment = get_comment( $comment_id );
		$data = array(
			'ID'                => (int) $comment_id,
			'user_ID'           => (int) $comment->user_id,
			'user_name'         => $comment->comment_author,
			'user_email'        => sanitize_email( $comment->comment_author_email ),
			'user_avatar_url'   => get_avatar_url( $comment->user_id ),
			'date_created'      => $comment->comment_date,
			'IP_address'        => $comment->comment_author_IP,
			'content'           => (string) $comment->comment_content,
			'approved'          => $comment->comment_approved,
			'agent'             => $comment->comment_agent,
			'type'              => (string) $comment->comment_type,
			'parent'            => (int) $comment->comment_parent,
			'rating'            => (int) $rating,
			'_course'            => Payload::get_course_data( $comment_post_id ),
		);
		return apply_filters( 'academy_webhooks/course_review_payload', $data );
	}
}
