<?php

namespace AcademyStoreEngine\ajax;

use Academy\Classes\AbstractAjaxHandler;
use StoreEngine\Classes\Price;
use StoreEngine\classes\SimpleProduct;
use StoreEngine\Utils\Helper;

class Product extends AbstractAjaxHandler {



	protected $namespace = ACADEMY_PLUGIN_SLUG . '_storeengine';

	public function __construct() {
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

	public function get_product( array $payload ) {
		if ( ! isset( $payload['course_id'] ) ) {
			wp_send_json_error([
				'message' => 'course_id is required'
			]);
		}

		$course_id = absint( sanitize_text_field( $payload['course_id'] ) );
		$integrations = Helper::get_integration_repository_by_id( 'storeengine/academylms', $course_id );

		if ( empty( $integrations ) ) {
			$product_id = get_post_meta( $course_id, 'academy_course_product_id', true );
			wp_send_json_success([
				'product' => (int) $product_id,
			]);
		}

		$product_object = Helper::get_product( $integrations[0]->price->get_product_id() );
		if ( ! $product_object ) {
			wp_send_json_success([
				'product' => null
			]);
		}
		$prices = [];
		foreach ( $integrations as $integration ) {
			$prices[] = $this->format_price( $integration->price );
		}

		$product = [];
		$product['id'] = $product_object->get_id();
		$product['name'] = $product_object->get_name();
		$product['prices'] = $prices;

		wp_send_json_success([
			'product' => $product
		]);
	}

	public function save_product( array $payload ) {
		if ( ! isset( $payload['course_id'] ) ) {
			wp_send_json_error([
				'message' => 'course_id is required'
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

		$prices = [];
		if ( isset( $payload['prices'] ) ) {
			foreach ( $payload['prices'] as $price ) {
				if ( ! isset( $price['price_type'] ) ) {
					wp_send_json_error([
						'message' => 'price_type is required'
					]);
				}

				if ( ! isset( $price['regular_price'] ) ) {
					wp_send_json_error([
						'message' => 'regular_price is required'
					]);
				}

				if ( ! isset( $price['price_name'] ) ) {
					wp_send_json_error([
						'message' => 'price_name is required'
					]);
				}

				$price_name = sanitize_text_field( $price['price_name'] );
				$price_type = sanitize_text_field( $price['price_type'] );
				$regular_price = (float) sanitize_text_field( $price['regular_price'] );
				$sale_price = isset( $price['sale_price'] ) ? (float) sanitize_text_field( $price['sale_price'] ) : null;
				if ( $sale_price && $regular_price <= $sale_price ) {
					wp_send_json_error([
						'message' => 'Sale price cannot be greater than or equals to regular_price'
					]);
				}

				$price_id = isset( $price['price_id'] ) ? (int) sanitize_text_field( $price['price_id'] ) : 0;
				$order = isset( $price['order'] ) ? (int) sanitize_text_field( $price['order'] ) : 0;
				$prices[] = [
					'price_id' => $price_id,
					'price_name' => $price_name,
					'price_type' => $price_type,
					'regular_price' => $regular_price,
					'sale_price' => $sale_price,
					'order' => $order,
				];
			}//end foreach
		}//end if

		$course_id = absint( sanitize_text_field( $payload['course_id'] ) );
		$course_title = isset( $payload['course_title'] ) ? sanitize_text_field( $payload['course_title'] ) : 'Untitled product for Academy LMS';

		$product_id = get_post_meta( $course_id, 'academy_course_product_id', true );
		$product_id = empty( $product_id ) ? 0 : (int) $product_id;
		$product = new SimpleProduct( $product_id );
		$product->set_name( $course_title );
		$product->set_author_id( get_current_user_id() );
		$product->save();

		if ( ! $product->get_id() ) {
			wp_send_json_error([
				'message' => 'could not create product!'
			]);
		}

		if ( 0 === $product_id ) {
			update_post_meta( $course_id, 'academy_course_product_id', $product->get_id() );
			$product->update_metadata( '_academy_course_id', $course_id );
		}

		foreach ( $prices as &$price_data ) {
			$price = new Price( $price_data['price_id'] );
			$price->set_name( $price_data['price_name'] );
			$price->set_product_id( $product->get_id() );
			$price->set_type( $price_data['price_type'] );
			if ( $price_data['sale_price'] ) {
				$price->set_price( $price_data['sale_price'] );
				$price->set_compare_price( $price_data['regular_price'] );
			} else {
				$price->set_price( $price_data['regular_price'] );
				$price->set_compare_price( null );
			}
			$price->set_order( $price_data['order'] );
			$price->save();
			if ( 0 === $price_data['price_id'] ) {
				$price_data['price_id'] = $price->get_id();
				$price->add_integration( 'storeengine/academylms', $course_id );
			}
		}

		$price_ids = array_map(function ( $price ) {
			return $price['price_id'];
		}, $prices);

		$integrations = Helper::get_integration_repository_by_id( 'storeengine/academylms', $course_id );
		$product_price_ids = array_map(function ( $integration ) {
			return $integration->price->get_id();
		}, $integrations);
		$saved_integrations = array_filter($integrations, function ( $integration ) use ( $price_ids ) {
			return in_array( $integration->price->get_id(), $price_ids, true );
		});

		$missing_price_ids = array_diff( $product_price_ids, $price_ids );
		if ( ! empty( $missing_price_ids ) ) {
			foreach ( $missing_price_ids as $price_id ) {
				$price = new Price( $price_id );
				$price->set_product_id( $product->get_id() );
				$price->remove_integration( 'storeengine/academylms', $course_id );
				$price->delete();
			}
		}

		wp_send_json_success([
			'product' => [
				'id' => $product->get_id(),
				'name' => $product->get_name(),
				'prices' => array_map(function ( $integration ) {
					return $this->format_price( $integration->price );
				}, $saved_integrations)
			]
		]);
	}

	private function format_price( Price $price ): array {
		return [
			'price_id' => $price->get_id(),
			'price_name' => $price->get_name(),
			'price_type' => $price->get_type(),
			'regular_price' => $price->get_compare_price() > 0 ? $price->get_compare_price() : $price->get_price(),
			'sale_price' => $price->get_compare_price() > 0 ? $price->get_price() : null,
			'order' => $price->get_order_no()
		];
	}
}
