<?php
namespace AcademyWebhooks\Listeners;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyWebhooks\Classes\Payload;
use AcademyWebhooks\Interfaces\ListenersInterface;

class AssignmentPublish implements ListenersInterface {
	public static function dispatch( $deliver_callback, $webhook ) {
		add_action(
			'rest_after_insert_academy_assignments',
			function( $assignment ) use ( $deliver_callback, $webhook ) {
				call_user_func_array(
					$deliver_callback,
					array(
						$webhook,
						self::get_payload( $assignment )
					)
				);
			}, 10
		);

	}

	public static function get_payload( $assignment ) {

		$data = Payload::get_assignment_data( $assignment );

		return apply_filters( 'academy_webhooks/assignment_published_payload', $data );
	}
}
