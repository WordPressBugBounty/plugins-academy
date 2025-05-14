<?php
namespace AcademyMultiInstructor\OrderTracker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class OrderTrashed extends OrderStatusTracker {
	public static function init(): void {
		add_action( 'woocommerce_trash_order', function( $id ) {
			$ins = new static( $id, 'trashed' );
			$ins->update();
		}, 10, 1);
	}
}
