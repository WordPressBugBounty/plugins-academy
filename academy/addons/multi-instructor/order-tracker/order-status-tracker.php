<?php
namespace AcademyMultiInstructor\OrderTracker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Helper;
abstract class OrderStatusTracker {
	public int $order_id;
	public string $status;

	public function __construct( int $order_id, string $status ) {
		$this->order_id = $order_id;
		$this->status   = $status;
	}

	public function update(): void {
		Helper::update_earning_status_by_order_id( $this->order_id, $this->status );
	}

	abstract public static function init(): void;
}
