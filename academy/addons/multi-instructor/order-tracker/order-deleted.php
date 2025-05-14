<?php
namespace AcademyMultiInstructor\OrderTracker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class OrderDeleted extends OrderStatusTracker {
	public static function init(): void {
		add_action( 'woocommerce_delete_order', function( $id ) {
			$ins = new static( $id, 'deleted' );
			$ins->update();
		}, 10, 1);
	}
}
