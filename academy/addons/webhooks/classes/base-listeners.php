<?php
namespace AcademyWebhooks\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Exception;

class BaseListeners {
	public function get_all_webhooks() {
		$args = array(
			'post_type'   => 'academy_webhook',
			'numberposts' => -1,
			'post_status' => 'publish'
		);

		return get_posts( apply_filters( 'academy_webhooks_query_args', $args ) );
	}

	public function get_webhook_events( int $webhook_id ) {
		$events = get_post_meta( $webhook_id, '_academy_webhook_events', true );
		if ( is_array( $events ) && count( $events ) ) {
			return wp_list_pluck( $events, 'value' );
		}
		return false;
	}

	public function get_webhook_secret( int $webhook_id ) {
		return get_post_meta( $webhook_id, '_academy_webhook_secret', true );
	}

	public function get_webhook_delivery_url( $webhook_id ) {
		return get_post_meta( $webhook_id, '_academy_webhook_delivery_url', true );
	}

	public function get_signature( $payload, $secret ) {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return base64_encode( hash_hmac( 'sha256', trim( wp_json_encode( $payload ) ), wp_specialchars_decode( $secret, ENT_QUOTES ), true ) );
	}

	public function get_delivery_id( $webhook_id ) {
		return hash_hmac( 'sha256', $webhook_id . strtotime( 'now' ), wp_salt( 'auth' ) );
	}

	public function get_webhook_headers( $event_name, $webhook_id, $payload ) {
		$secret = $this->get_webhook_secret( $webhook_id ) ?? '';

		return array(
			'Content-Type'                  => 'application/json',
			'X-ACADEMY-Webhook-Source'      => home_url( '/' ),
			'X-ACADEMY-Webhook-Signature'   => $this->get_signature( $payload, $secret ),
			'X-ACADEMY-Webhook-ID'          => $webhook_id,
			'X-ACADEMY-Webhook-Delivery-ID' => $this->get_delivery_id( $webhook_id ),
			'X-ACADEMY-Webhook-EVENT'       => $event_name,
		);
	}

	public function dispatch_webhook( $event_name, $webhook, $payload ) {
		$webhook_id   = (int) $webhook['ID'];
		$delivery_url = trim( $this->get_webhook_delivery_url( $webhook_id ) );

		if ( empty( $delivery_url ) ) {
			throw new Exception( esc_html__( 'Delivery URL must be empty.', 'academy' ) );
		}

		if ( ! wp_http_validate_url( $delivery_url ) ) {
			throw new Exception( esc_html__( 'Invalid Delivery URL', 'academy' ) );
		}

		$response = wp_safe_remote_request(
			$delivery_url,
			array(
				'redirection' => 0,
				'method'      => 'POST',
				'timeout'     => MINUTE_IN_SECONDS,
				'blocking'    => true,
				'httpversion' => '1.0',
				'body'        => trim( wp_json_encode( $payload ) ),
				'cookies'     => array(),
				'user-agent'  => sprintf( 'Academy/%s Webhook shot (WordPress/%s)', ACADEMY_VERSION, $GLOBALS['wp_version'] ),
				'headers'     => $this->get_webhook_headers( $event_name, $webhook_id, $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new Exception( esc_html( $response->get_error_message() ), esc_html( $response->get_error_code() ) );
		}
	}
}
