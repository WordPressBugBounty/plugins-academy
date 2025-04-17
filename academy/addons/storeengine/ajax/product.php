<?php

namespace AcademyStoreEngine\ajax;

use Academy\Classes\AbstractAjaxHandler;
use StoreEngine\Utils\Helper;
use StoreEngine\Integrations\IntegrationTrait;

class Product extends AbstractAjaxHandler {
	use IntegrationTrait;

	protected $namespace = ACADEMY_PLUGIN_SLUG . '_storeengine';

	public function __construct() {
		$this->init_integration();

		$this->actions = array(
			'get_product' => array(
				'callback' => array( $this, 'get_product' ),
				'capability' => 'manage_academy_instructor',
			),
			'save_product' => array(
				'callback' => array( $this, 'save_product' ),
				'capability' => 'manage_academy_instructor',
			),
		);
	}

	protected function set_integration_config():void {
		$this->integration_name = 'storeengine/academylms';
		$this->item_meta = 'academy_store_product';
		$this->product_meta = '_academy_course_id';
	}

	public function get_product( array $payload ) {
		if ( ! isset( $payload['course_id'] ) ) {
			wp_send_json_error([
				'message' => esc_html__( 'course_id is required', 'academy' )
			]);
		}

		$this->item_id = absint( sanitize_text_field( $payload['course_id'] ) );
		$this->get_product_integrations();
	}

	public function save_product( array $payload ) {
		if ( ! isset( $payload['course_id'] ) ) {
			wp_send_json_error([
				'message' => esc_html__( 'course_id is required', 'academy' )
			]);
		}

		if ( isset( $payload['prices'] ) ) {
			$payload['prices'] = json_decode( $payload['prices'], true );
			if ( ! is_array( $payload['prices'] ) ) {
				wp_send_json_error([
					'message' => 'prices are required'
				]);
			}
		}

		// set the basic payload data
		$this->item_id = absint( sanitize_text_field( $payload['course_id'] ) );
		$this->item_title = isset( $payload['course_title'] ) ? sanitize_text_field( $payload['course_title'] ) : 'Untitled product for Academy LMS';
		$this->prices = $payload['prices'] ?? [];

		// handle integrations
		$this->handle_integrations();

		wp_send_json_success([
			'product' => [
				'id' => $this->product->get_id(),
				'name' => $this->product->get_name(),
				'prices' => array_map(function ( $integration ) {
					return $this->format_price( $integration->price );
				}, $this->saved_integrations)
			]
		]);
	}
}
