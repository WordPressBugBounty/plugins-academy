<?php
namespace AcademyWebhooks\Listeners;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyWebhooks\Classes\Payload;
use AcademyWebhooks\Interfaces\ListenersInterface;


class SubmittedAssignment implements ListenersInterface {
	public static function dispatch( $deliver_callback, $webhook ) {
		add_action(
			'academy_pro/frontend/submitted_assignment',
			function( $response ) use ( $deliver_callback, $webhook ) {
				call_user_func_array(
					$deliver_callback,
					array(
						$webhook,
						self::get_payload( $response )
					)
				);
			}, 10
		);
	}

	public static function get_payload( $response ) {
		$assignment = get_post( $response->comment_post_ID );
		$data = array(
			'ID'                => (int) $response->comment_ID,
			'user_ID'           => (int) $response->user_id,
			'user_name'         => $response->comment_author,
			'user_email'        => sanitize_email( $response->comment_author_email ),
			'user_avatar_url'   => get_avatar_url( $response->user_id ),
			'content'           => (string) $response->comment_content,
			'status'            => $response->comment_approved,
			'attachment'        => $response->meta['academy_pro_assignment_attachment'],
			'attachment_url'    => wp_get_attachment_url( $response->meta['academy_pro_assignment_attachment'] ),
			'start_time'        => $response->meta['academy_pro_assignment_start_time'],
			'end_time'          => $response->meta['academy_pro_assignment_end_time'],
			'_assignment'       => Payload::get_assignment_data( $assignment ),
			'_course'           => Payload::get_course_data( $response->comment_parent ),
		);

		return apply_filters( 'academy_webhooks/submitted_assignment_payload', $data );
	}
}
