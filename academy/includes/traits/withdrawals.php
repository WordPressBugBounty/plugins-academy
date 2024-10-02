<?php
namespace Academy\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Withdrawals {

	public static function insert_withdraw( $args ) {
		global $wpdb;
		$defaults = array(
			'user_id'     => '',
			'amount'      => '',
			'method_data' => '',
			'status'      => '',
			'updated_at'  => current_time( 'mysql' ),
			'created_at'  => current_time( 'mysql' ),
		);
		$args     = wp_parse_args( $args, $defaults );
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->prefix}academy_withdraws ( user_id, amount, method_data, status, updated_at, created_at)
                VALUES ( %d, %f, %s, %s, %s, %s )",
				$args['user_id'],
				$args['amount'],
				$args['method_data'],
				$args['status'],
				$args['updated_at'],
				$args['created_at']
			)
		);
		return $wpdb->insert_id;
	}

	public static function update_withdraw_status_by_user_id( $user_id, $status_to ) {
		global $wpdb;
		$is_update = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}academy_withdraws SET status=%s WHERE user_id= %d", $status_to, $user_id ) );
		return $is_update;
	}

	public static function update_withdraw_status_by_withdraw_id( $ID, $status_to ) {
		global $wpdb;
		$is_update = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}academy_withdraws SET status=%s WHERE ID= %d", $status_to, $ID ) );
		return $is_update;
	}

	public static function get_withdraw_history_by_user_id( $user_id ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}academy_withdraws WHERE user_id=%d ORDER BY created_at DESC",
				$user_id
			)
		);
		return $results;
	}

	public static function get_withdraw_request( $offset = 0, $per_page = 10, $status = 'any' ) {
		global $wpdb;

		$query = "SELECT {$wpdb->prefix}academy_withdraws.ID, user_id, amount, method_data, status, created_at, {$wpdb->prefix}users.user_login, {$wpdb->prefix}users.user_email FROM {$wpdb->prefix}academy_withdraws LEFT JOIN {$wpdb->prefix}users ON {$wpdb->prefix}academy_withdraws.user_id = {$wpdb->prefix}users.ID";
		if ( 'any' !== $status ) {
			$query .= $wpdb->prepare( ' WHERE status = %s', $status );
		}

		$query .= ' ORDER BY created_at DESC LIMIT %d, %d';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $wpdb->prepare( $query, $offset, $per_page ) );
	}
	public static function get_withdraw_by_withdraw_id( $ID ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
                {$wpdb->prefix}academy_withdraws.ID, 
                    user_id, 
                    amount, 
                    method_data, 
                    status, 
                    created_at, 
                    {$wpdb->prefix}users.user_login, 
                    {$wpdb->prefix}users.user_email
                FROM {$wpdb->prefix}academy_withdraws LEFT JOIN {$wpdb->prefix}users 
                    ON {$wpdb->prefix}academy_withdraws.user_id = {$wpdb->prefix}users.ID 
                WHERE {$wpdb->prefix}academy_withdraws.ID=%d",
				$ID
			)
		);
		return $results;
	}

	public static function get_user_withdraw_saved_info( $user_id, $type ) {
		if ( 'paypal' === $type ) {
			return [
				'withdraw_method_type' => $type,
				'paypal_email_address' => get_user_meta( $user_id, 'academy_instructor_withdraw_paypal_email', true ),
			];
		} elseif ( 'echeck' === $type ) {
			return [
				'withdraw_method_type' => $type,
				'echeckAddress'        => get_user_meta( $user_id, 'academy_instructor_withdraw_echeck_address', true ),
			];
		} elseif ( 'bank' === $type ) {
			return [
				'withdraw_method_type' => $type,
				'bank_account_name'    => get_user_meta( $user_id, 'academy_instructor_withdraw_bank_acocunt_name', true ),
				'bank_account_number'  => get_user_meta( $user_id, 'academy_instructor_withdraw_bank_acocunt_number', true ),
				'bank_name'            => get_user_meta( $user_id, 'academy_instructor_withdraw_bank_name', true ),
				'bank_IBAN'            => get_user_meta( $user_id, 'academy_instructor_withdraw_bank_iban', true ),
				'bank_SWIFTCode'       => get_user_meta( $user_id, 'academy_instructor_withdraw_bank_swiftcode', true ),
			];
		}
		return [];
	}
	public static function get_total_number_of_withdraw_request() {
		global $wpdb;
		return $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}academy_withdraws" );
	}
}
