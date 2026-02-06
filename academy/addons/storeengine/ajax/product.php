<?php

namespace AcademyStoreEngine\ajax;

use Academy\Classes\AbstractAjaxHandler;
use StoreEngine\Classes\Product\SimpleProduct;
use StoreEngine\Integrations\IntegrationTrait;
use StoreEngine\Utils\Helper;

class Product extends AbstractAjaxHandler {

	use IntegrationTrait;

	protected $namespace = ACADEMY_PLUGIN_SLUG . '_storeengine';

	public function __construct() {
		$this->init_integration();

		$this->actions = array(
			'get_product_list' => array(
				'callback'   => array( $this, 'get_product_list' ),
				'capability' => 'manage_academy_instructor',
			),
			'get_product'      => array(
				'callback'   => array( $this, 'get_product' ),
				'capability' => 'manage_academy_instructor',
			),
			'save_product'     => array(
				'callback'   => array( $this, 'save_product' ),
				'capability' => 'manage_academy_instructor',
			),
		);
	}

	public function get_product_list( array $payload ) {
		if ( ! isset( $payload['integration_id'] ) ) {
			wp_send_json_error( [
				'message' => __( 'integration_id is required', 'academy' )
			] );
		}

		$search         = isset( $payload['search'] ) ? sanitize_text_field( wp_unslash( $payload['search'] ) ) : '';
		$integration_id = absint( $payload['integration_id'] );
		$provider       = isset( $payload['provider'] ) ? sanitize_text_field( $payload['provider'] ) : 'storeengine/academylms';
		wp_send_json_success( Helper::get_product_list( $integration_id, $provider, $search ) );
	}

	protected function set_integration_config(): void {
		$this->integration_name = 'storeengine/academylms';
	}

	public function get_product( array $payload ) {
		if ( ! isset( $payload['course_id'] ) ) {
			wp_send_json_error( [
				'message' => esc_html__( 'course_id is required', 'academy' )
			] );
		}

		$this->item_id = absint( sanitize_text_field( $payload['course_id'] ) );
		$this->get_integrations();
	}

	public function create_product(): SimpleProduct {
		$product = new SimpleProduct();
		$product->set_name( $this->item_title );
		$product->set_author_id( get_current_user_id() );
		$product->set_hide( true );
		$product->set_shipping_type( 'digital' );
		$product->set_digital_auto_complete( true );
		$product->save();

		if ( ! $product->get_id() ) {
			wp_send_json_error( [ 'message' => __( 'Could not create product!', 'academy' ) ] );
		}

		return $product;
	}

	public function save_product( array $payload ) {
		if ( ! isset( $payload['course_id'] ) ) {
			wp_send_json_error( [
				'message' => esc_html__( 'course_id is required', 'academy' )
			] );
		}

		if ( isset( $payload['prices'] ) ) {
			$payload['prices'] = json_decode( $payload['prices'], true );
			if ( ! is_array( $payload['prices'] ) ) {
				wp_send_json_error( [
					'message' => 'prices are required'
				] );
			}
		}

		// set the basic payload data
		$this->item_id    = absint( sanitize_text_field( $payload['course_id'] ) );
		$this->item_title = isset( $payload['course_title'] ) ? sanitize_text_field( $payload['course_title'] ) : 'Untitled product for Academy LMS';
		$this->prices     = $payload['prices'] ?? [];

		// handle integrations
		$this->handle_integrations();

		$this->get_integrations();
	}
}
