<?php
namespace AcademyWebhooks\Listeners;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

use AcademyWebhooks\Classes\Payload;
use AcademyWebhooks\Interfaces\ListenersInterface;

class TutorBookingBooked implements ListenersInterface {

	public static function dispatch( $deliver_callback, $webhook ) {
		add_action(
			'academy_pro/booking/after_booked',
			function ( $booking_id, $booked_id, $user_id ) use (
				$deliver_callback,
				$webhook
			) {
				call_user_func_array($deliver_callback, [
					$webhook,
					self::get_payload( $booking_id, $booked_id, $user_id ),
				]);
			},
			10,
			3
		);
	}

	public static function get_payload( $booking_id, $booked_id, $user_id ) {
		$booking = get_post( $booking_id );
		$booked = get_post( $booked_id );
		$data = array_merge(
			[
				'ID' => $booked_id,
				'title' => 'Booking Booked',
				'status' => (string) $booked->post_status,
				'description' => $booked->post_content,
				'parent_id' => (int) $booked->post_parent,
				'menu_order' => (int) $booked->menu_order,
				'booked_time' => get_post_meta(
					$booked_id,
					'_academy_booked_schedule_time',
					true
				),
			],
			[
				'tutor_booking' => Payload::get_tutor_booking_data( $booking ),
				'user' => Payload::get_user_data( $user_id ),
			]
		);
		return apply_filters( 'academy_webhooks/booking_booked_payload', $data );
	}
}
