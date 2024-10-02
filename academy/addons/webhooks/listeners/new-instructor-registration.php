<?php
namespace AcademyWebhooks\Listeners;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyWebhooks\Interfaces\ListenersInterface;
use AcademyWebhooks\Classes\Payload;

class NewInstructorRegistration implements ListenersInterface {
	public static function dispatch( $deliver_callback, $webhook ) {
		add_action(
			'academy/shortcode/after_instructor_registration',
			function( $instructor_id ) use ( $deliver_callback, $webhook ) {
				call_user_func_array(
					$deliver_callback,
					array(
						$webhook,
						self::get_payload( $instructor_id )
					)
				);
			}
		);

		add_action(
			'academy/admin/after_register_instructor',
			function( $instructor_id ) use ( $deliver_callback, $webhook ) {
				call_user_func_array(
					$deliver_callback,
					array(
						$webhook,
						self::get_payload( $instructor_id )
					)
				);
			}
		);
	}

	public static function get_payload( $instructor_id ) {
		$user_data = Payload::get_user_data( $instructor_id );
		$user_data['roles'] = 'academy_instructor';
		$user_data['status'] = get_user_meta( $instructor_id, 'academy_instructor_status', true );

		return apply_filters( 'academy_webhooks/new_instructor_registration_payload', $user_data );
	}
}
