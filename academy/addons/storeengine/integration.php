<?php

namespace AcademyStoreEngine;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Academy\Helper;
use Academy\Traits\Earning;
use StoreEngine\classes\order\OrderItemProduct;

class Integration {

	use Earning;

	public static function init() {
		$self = new self();
		add_filter( 'academy/frontend_dashboard_menu_items', array( $self, 'add_store_dashboard_menu' ), 10, 1 );
		add_action( 'storeengine/order/during_add_product', [ $self, 'add_course_line_meta' ] );
		add_filter( 'storeengine/frontend_dashboard_menu_items', [ $self, 'add_academy_dashboard_menu' ] );

		add_action( 'storeengine/checkout/after_place_order', [ $self, 'save_store_earning_data' ] );
		add_action( 'storeengine/order/status_changed', [ $self, 'save_store_earning_data_status_changed' ], 10, 3 );
	}

	public function add_course_line_meta( OrderItemProduct $item ) {
		$item->update_metadata( 'academy_course_id', '' );
	}

	public function add_store_dashboard_menu( $menu ) {
		$menu['store-dashboard'] = array(
			'label' => Helper::get_settings( 'store_link_label_inside_frontend_dashboard', __( 'Store Dashboard', 'academy' ) ),
			'icon'  => 'academy-icon academy-icon--calender',
			'permalink' => \StoreEngine\Utils\Helper::get_page_permalink( 'dashboard_page' ),
			'public' => true,
			'priority' => 7,
		);

		return $menu;
	}

	public function add_academy_dashboard_menu( $items ) {
		$items['academy-dashboard'] = [
			'label'    => __( 'Academy Dashboard', 'academy' ),
			'icon'     => 'storeengine-icon storeengine-icon--layout',
			'permalink' => Helper::get_page_permalink( 'frontend_dashboard_page' ),
			'public'   => true,
			'priority' => 5,
		];
		return $items;
	}

	public function save_store_earning_data( $order ) {
		global $wpdb;
		$is_enabled_earning = (bool) Helper::get_settings( 'is_enabled_earning' );
		if ( ! Helper::get_addon_active_status( 'multi_instructor' ) || ! $is_enabled_earning ) {
			return;
		}

		$item = current( $order->get_items() );
		$product_id = $item ? $item->get_product_id() : 0;
		if ( ! $product_id ) {
			return;
		}
		$table = $wpdb->prefix . 'storeengine_integrations';
		$integration_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT integration_id FROM $table WHERE provider = %s AND product_id = %d",
				'storeengine/academylms',
				$product_id
			)
		);

		if ( ! empty( $integration_id ) ) {
			Helper::save_instructor_earnings( $integration_id, $order, $order->get_id() );
		}//end if
	}

	public function save_store_earning_data_status_changed( $order_id, $old_status, $new_status ) {
		$is_enabled_earning = (bool) Helper::get_settings( 'is_enabled_earning' );
		if ( ! Helper::get_addon_active_status( 'multi_instructor' ) || ! $is_enabled_earning ) {
			return;
		}
		if ( count( Helper::get_earning_by_order_id( $order_id ) ) ) {
			Helper::update_earning_status_by_order_id( $order_id, $new_status );
		}
	}
}
