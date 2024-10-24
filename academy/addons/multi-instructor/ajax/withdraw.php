<?php
namespace AcademyMultiInstructor\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes\Sanitizer;
use Academy\Classes\AbstractAjaxHandler;

class Withdraw extends AbstractAjaxHandler {
	protected $namespace = ACADEMY_PLUGIN_SLUG . '_multi_instructor';
	public function __construct() {
		// withdraw related ajax.
		$this->actions = array(
			'get_all_withdraw_request' => array(
				'callback' => array( $this, 'get_all_withdraw_request' ),
			),
			'update_withdraw_status' => array(
				'callback' => array( $this, 'update_withdraw_status' ),
			),
		);
	}

	public function get_all_withdraw_request( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'page' => 'integer',
			'per_page' => 'integer',
			'status' => 'string'
		], $payload_data );

		$page = ( isset( $payload['page'] ) ? $payload['page'] : 1 );
		$per_page = ( isset( $payload['per_page'] ) ? $payload['per_page'] : 10 );
		$status = ( isset( $payload['status'] ) ? $payload['status'] : 'any' );
		$offset = ( $page - 1 ) * $per_page;

		$total_request = \Academy\Helper::get_total_number_of_withdraw_request();
		// Set the x-wp-total header
		header( 'x-wp-total: ' . $total_request );

		$results = \Academy\Helper::get_withdraw_request( $offset, $per_page, $status );
		wp_send_json_success( $results );
		wp_die();
	}

	public function update_withdraw_status( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'ID' => 'integer',
			'statusTo' => 'string',
		], $payload_data );

		$ID = ( isset( $payload['ID'] ) ? $payload['ID'] : 0 );
		$statusTo = ( isset( $payload['statusTo'] ) ? $payload['statusTo'] : '' );

		$is_update = \Academy\Helper::update_withdraw_status_by_withdraw_id( $ID, $statusTo );
		if ( $is_update ) {
			$results = \Academy\Helper::get_withdraw_by_withdraw_id( $ID );
			wp_send_json_success( current( $results ) );
			wp_die();
		}
		wp_send_json_error( [ 'message' => esc_html__( 'Failed to update withdraw status', 'academy' ) ] );
		wp_die();
	}

}
