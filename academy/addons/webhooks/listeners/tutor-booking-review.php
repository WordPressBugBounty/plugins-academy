<?php
namespace AcademyWebhooks\Listeners;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyWebhooks\Classes\Payload;
use AcademyWebhooks\Interfaces\ListenersInterface;


class TutorBookingReview implements ListenersInterface {
	public static function dispatch( $deliver_callback, $webhook ) {
		add_action(
			'academy_pro/fronted/academy_booking_review',
			function( $comment_id, $rating ) use ( $deliver_callback, $webhook ) {
				call_user_func_array(
					$deliver_callback,
					array(
						$webhook,
						self::get_payload( $comment_id, $rating )
					)
				);
			}, 10, 2
		);

	}

	public static function get_payload( $comment_id, $rating ) {
		$comment = get_comment( $comment_id );
		$booking = get_post( $comment->comment_post_ID );
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
			'_booking'          => Payload::get_tutor_booking_data( $booking ),
		);

		return apply_filters( 'academy_webhooks/tutor_booking_review_payload', $data );
	}
}
