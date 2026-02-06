<?php

namespace AcademyStoreEngine\hooks;

use StoreEngine\Classes\CartItem;

class Cart {

	public static function init() {
		$self = new self();

		add_filter( 'storeengine/cart/add_item_data', [ $self, 'add_cart_item_data' ] );
		add_filter( 'storeengine/cart/item_image_post_id', [ $self, 'replace_image_post_id' ], 10, 2 );
		add_filter( 'storeengine/cart/item_name', [ $self, 'update_cart_item_name' ], 10, 2 );
		add_filter( 'storeengine/cart/item_permalink', [ $self, 'update_cart_item_permalink' ], 10, 2 );
	}

	public function add_cart_item_data( array $item_data ): array {
		if ( ! isset( $_POST['academy_course_id'] ) ) {// phpcs:ignore WordPress.Security.NonceVerification.Missing
			return $item_data;
		}

		$item_data['academy_course_id'] = absint( wp_unslash( $_POST['academy_course_id'] ) );// phpcs:ignore WordPress.Security.NonceVerification.Missing

		return $item_data;
	}

	public function replace_image_post_id( int $product_id, CartItem $cart_item ) {
		if ( ! is_array( $cart_item->item_data ) || ! isset( $cart_item->item_data['academy_course_id'] ) ) {
			return $product_id;
		}

		return $cart_item->item_data['academy_course_id'];
	}

	public function update_cart_item_name( string $name, CartItem $cart_item ): string {
		if ( ! is_array( $cart_item->item_data ) || ! isset( $cart_item->item_data['academy_course_id'] ) ) {
			return $name;
		}

		return get_the_title( $cart_item->item_data['academy_course_id'] );
	}

	public function update_cart_item_permalink( string $permalink, CartItem $cart_item ): string {
		if ( ! is_array( $cart_item->item_data ) || ! isset( $cart_item->item_data['academy_course_id'] ) ) {
			return $permalink;
		}

		return get_the_permalink( $cart_item->item_data['academy_course_id'] );
	}
}
