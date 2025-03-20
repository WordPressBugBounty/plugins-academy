<?php

namespace AcademyStoreEngine;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Academy\Helper;
use Academy\Traits\Earning;
use StoreEngine\Classes\Order;
use StoreEngine\classes\order\OrderItem;

class Integration {


	use Earning;

	public static function init() {
		$self = new self();
		add_filter( 'academy/frontend_dashboard_menu_items', array( $self, 'add_store_dashboard_menu' ), 10, 1 );
		add_action( 'storeengine/order/during_add_product', [ $self, 'add_course_line_meta' ] );
	}

	public function add_course_line_meta( OrderItem $item ) {
		$item->update_metadata( 'academy_course_id', '' );
	}

	public function add_store_dashboard_menu( $menu ) {
		$menu['store-dashboard'] = array(
			'label' => Helper::get_settings( 'store_link_label_inside_frontend_dashboard', __( 'Store Dashboard', 'academy' ) ),
			'icon'  => 'academy-icon academy-icon--calender',
			'permalink' => \StoreEngine\Utils\Helper::get_page_permalink( 'dashboard_page' ),
			'public' => true,
			'priority' => 27,
		);

		return $menu;
	}
}
