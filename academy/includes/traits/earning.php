<?php
namespace Academy\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Earning {

	public static function insert_earning( $args ) {
		global $wpdb;
		$defaults = array(
			'user_id'                  => '',
			'course_id'                => '',
			'order_id'                 => '',
			'order_status'             => '',
			'course_price_total'       => '',
			'course_price_grand_total' => '',
			'instructor_amount'        => '',
			'instructor_rate'          => '',
			'admin_amount'             => '',
			'admin_rate'               => '',
			'commission_type'          => '',
			'deduct_fees_amount'       => '',
			'deduct_fees_name'         => '',
			'deduct_fees_type'         => '',
			'process_by'               => 'woocommerce',
			'created_at'               => gmdate( 'Y-m-d H:i:s', \Academy\Helper::get_time() ),
		);
		$args     = wp_parse_args( $args, $defaults );

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->prefix}academy_earnings ( user_id, course_id, order_id, order_status,  course_price_total, course_price_grand_total, instructor_amount, instructor_rate, admin_amount, admin_rate, commission_type, deduct_fees_amount, deduct_fees_name, deduct_fees_type, process_by, created_at)
                VALUES ( %d, %d, %d, %s, %f, %f, %f, %f, %f, %f, %s, %f, %s, %s, %s, %s )",
				$args['user_id'],
				$args['course_id'],
				$args['order_id'],
				$args['order_status'],
				$args['course_price_total'],
				$args['course_price_grand_total'],
				$args['instructor_amount'],
				$args['instructor_rate'],
				$args['admin_amount'],
				$args['admin_rate'],
				$args['commission_type'],
				$args['deduct_fees_amount'],
				$args['deduct_fees_name'],
				$args['deduct_fees_type'],
				$args['process_by'],
				$args['created_at']
			)
		);
		return $wpdb->insert_id;
	}
	public static function get_earning_by_order_id( $order_id ) {
		global $wpdb;
		$results = $wpdb->get_var( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}academy_earnings WHERE order_id = %d", $order_id ) );
		return (array) $results;
	}
	public static function update_earning_status_by_order_id( $order_id, $status_to ) {
		global $wpdb;
		$is_update = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}academy_earnings SET order_status=%s WHERE order_id= %d", $status_to, $order_id ) );
		return $is_update;
	}
	public static function is_exists_user_earning_by_order( $course_id, $order_id, $user_id ) {
		global $wpdb;
		$results = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(ID)
            FROM {$wpdb->prefix}academy_earnings
            WHERE course_id=%d
                AND order_id=%d
                AND user_id=%d",
				$course_id,
				$order_id,
				$user_id
			)
		);
		return (bool) $results;
	}

	public static function get_earning_by_user_id( $user_id ) {
		global $wpdb;
		$results = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT SUM(course_price_total) AS course_price_total, 
                    SUM(course_price_grand_total) AS course_price_grand_total, 
                    SUM(instructor_amount) AS instructor_amount, 
                    SUM(admin_amount) AS admin_amount, 
                    SUM(deduct_fees_amount)  AS deduct_fees_amount,
                    (SELECT SUM(amount)
					FROM 	{$wpdb->prefix}academy_withdraws
					WHERE 	user_id = %d
							AND status != 'rejected'
					) AS withdraws_amount
            FROM 	{$wpdb->prefix}academy_earnings 
            WHERE 	user_id = %d
					AND order_status = %s;
			",
				$user_id,
				$user_id,
				'completed'
			)
		);

		if ( $results->course_price_total ) {
			$results->balance = $results->instructor_amount - $results->withdraws_amount;
		}
		return $results;
	}
}
