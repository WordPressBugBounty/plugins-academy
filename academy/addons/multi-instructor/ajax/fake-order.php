<?php

namespace AcademyMultiInstructor\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy;
use Academy\Helper;
use Academy\Classes\Sanitizer;
use Academy\Classes\AbstractAjaxHandler;

class FakeOrder extends AbstractAjaxHandler {
	protected $namespace = ACADEMY_PLUGIN_SLUG . '_multi_instructor';
	public function __construct() {
		$this->actions = array(
			'fake_earning_orders' => array(
				'callback' => array( $this, 'fake_earning_orders' ),
				'capability' => 'manage_academy_instructor',
			)
		);
	}

	public function fake_earning_orders( $payload_data ) {
		$payload = Sanitizer::sanitize_payload(
			[
				'status'    => 'string',
				'id'        => 'string',
			],
			$payload_data
		);

		$status    = isset( $payload['status'] ) ? sanitize_text_field( $payload['status'] ) : '';
		$order_ids = isset( $payload['id'] ) ? $payload['id'] : '';

		$monetization_engine   = \Academy\Helper::get_settings( 'monetization_engine' );
		$is_woocommerce_active = \Academy\Helper::get_addon_active_status( 'woocommerce' );

		if ( 'woocommerce' !== $monetization_engine || ! $is_woocommerce_active ) {
			wp_send_json_error(
				__( 'WooCommerce engine is not set or not activated.', 'academy' )
			);
		}

		if ( 'get' === $status ) {
			$orders = \Academy\Helper::get_academy_fake_earning_orders( $status );
			if ( ! empty( $orders ) ) {
				foreach ( $orders as $key => $order ) {
					$orders[ $key ]->title = get_the_title( $order->course_id );
					$orders[ $key ]->course_permalink = get_the_permalink( $order->course_id );
				}
				wp_send_json_success( $orders );
			}

			wp_send_json_success(
				esc_html__( 'No fake earning orders found.', 'academy' )
			);
		}

		if ( 'delete' === $status ) {
			preg_match_all( '/\d+/', $order_ids, $matches );
			$log = [];
			$order_ids = array_map( 'intval', $matches[0] );
			$is_deleted = \Academy\Helper::delete_academy_fake_earning_orders( $order_ids );

			if ( $is_deleted ) {
				wp_send_json_success(
					esc_html__( 'Fake earning orders deleted successfully.', 'academy' )
				);
			}
		}

		wp_send_json_error(
			esc_html__( 'Something went wrong.', 'academy' )
		);
	}

}
