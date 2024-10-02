<?php
namespace AcademyWebhooks\Listeners;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyWebhooks\Interfaces\ListenersInterface;
use AcademyWebhooks\Classes\Payload;


class NewEnrollment implements ListenersInterface {
	public static function dispatch( $deliver_callback, $webhook ) {
		add_action(
			'academy/course/after_enroll',
			function( $course_id, $enroll_id, $user_id ) use ( $deliver_callback, $webhook ) {
				call_user_func_array(
					$deliver_callback,
					array(
						$webhook,
						self::get_payload( $course_id, $enroll_id, $user_id )
					)
				);
			}, 10, 3
		);
	}

	public static function get_payload( $course_id, $enroll_id, $user_id ) {
		$image_url = get_avatar_url( $user_id );
		$data = array_merge( Payload::get_course_data( $course_id ), array(
			'enroll_date'        => self::enroll_date( $enroll_id ),
			'_user'               => Payload::get_user_data( $user_id ),
			'user_profile_image_id' => attachment_url_to_postid( $image_url ),
			'user_profile_image_url' => $image_url,
		) );

		return apply_filters( 'academy_webhooks/new_enrolled_payload', $data );
	}

	public static function enroll_date( $enroll_id ) {
		$enroll_course = get_post( $enroll_id );

		return $enroll_course->post_date;
	}
}
