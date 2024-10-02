<?php
namespace AcademyWebhooks\Listeners;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyWebhooks\Classes\Payload;
use AcademyWebhooks\Interfaces\ListenersInterface;


class ZoomPublish implements ListenersInterface {
	public static function dispatch( $deliver_callback, $webhook ) {
		add_action(
			'academy_pro/frontend/after_zoom_publish',
			function( $zoom_id ) use ( $deliver_callback, $webhook ) {
				call_user_func_array(
					$deliver_callback,
					array(
						$webhook,
						self::get_payload( $zoom_id )
					)
				);
			}, 10
		);

	}

	public static function get_payload( $zoom_id ) {

		$data = Payload::get_zoom_data( $zoom_id );

		return apply_filters( 'academy_webhooks/zoom_publish_payload', $data );
	}
}
