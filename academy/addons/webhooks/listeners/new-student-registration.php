<?php
namespace AcademyWebhooks\Listeners;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyWebhooks\Interfaces\ListenersInterface;
use AcademyWebhooks\Classes\Payload;


class NewStudentRegistration implements ListenersInterface {
	public static function dispatch( $deliver_callback, $webhook ) {
		add_action(
			'academy/shortcode/after_student_registration',
			function( $student_id ) use ( $deliver_callback, $webhook ) {
				call_user_func_array(
					$deliver_callback,
					array(
						$webhook,
						self::get_payload( $student_id )
					)
				);
			}, 10, 2
		);

		add_action(
			'academy/admin/after_student_registration',
			function( $student_id ) use ( $deliver_callback, $webhook ) {
				call_user_func_array(
					$deliver_callback,
					array(
						$webhook,
						self::get_payload( $student_id )
					)
				);
			}, 10, 2
		);
	}

	public static function get_payload( $student_id ) {
		$data = Payload::get_user_data( $student_id );

		return apply_filters( 'academy_webhooks/new_student_registration_payload', $data );
	}
}
