<?php

namespace AcademyStoreEngine\hooks;

use StoreEngine\Classes\CartItem;
use StoreEngine\Classes\Order\OrderItemProduct;

class Order {

	public static function init() {
		$self = new self();
		// order
		if ( version_compare( STOREENGINE_VERSION, '1.6.7', '<' ) ) {
			add_action( 'storeengine/order/create_order_line_item', [ $self, 'save_course_id_in_order_line_item' ], 10, 2 );
		} else {
			add_action( 'storeengine/checkout/create_order_line_item', [ $self, 'save_course_id_in_order_line_item' ], 10, 2 );
		}
		add_filter( 'storeengine/order/item_image_post_id', [ $self, 'replace_image_post_id' ], 10, 2 );
		add_filter( 'storeengine/order/item_name', [ $self, 'replace_item_name' ], 10, 2 );
		add_filter( 'storeengine/order/item_permalink', [ $self, 'replace_permalink' ], 10, 2 );
	}

	public function save_course_id_in_order_line_item( OrderItemProduct $item, CartItem $cart_item ) {
		if ( ! is_array( $cart_item->item_data ) || ! isset( $cart_item->item_data['academy_course_id'] ) ) {
			return;
		}

		$item->add_meta_data( '_academy_course_id', $cart_item->item_data['academy_course_id'] );
	}

	public function replace_image_post_id( int $product_id, OrderItemProduct $order_item ): int {
		if ( ! $order_item->get_meta( '_academy_course_id' ) ) {
			return $product_id;
		}

		return (int) $order_item->get_meta( '_academy_course_id' );
	}

	public function replace_item_name( string $name, OrderItemProduct $order_item ): string {
		if ( ! $order_item->get_meta( '_academy_course_id' ) ) {
			return $name;
		}

		return get_the_title( (int) $order_item->get_meta( '_academy_course_id' ) );
	}

	public function replace_permalink( string $permalink, OrderItemProduct $order_item ) {
		if ( ! $order_item->get_meta( '_academy_course_id' ) ) {
			return $permalink;
		}

		return get_permalink( (int) $order_item->get_meta( '_academy_course_id' ) );
	}

}
