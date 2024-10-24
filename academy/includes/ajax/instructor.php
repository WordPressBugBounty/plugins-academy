<?php
namespace  Academy\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes\Sanitizer;
use Academy\Classes\AbstractAjaxHandler;

class Instructor extends AbstractAjaxHandler {
	public function __construct() {
		$this->actions = array(
			'get_all_instructors' => array(
				'callback' => array( $this, 'get_all_instructors' )
			),
			'update_instructor_status' => array(
				'callback' => array( $this, 'update_instructor_status' )
			),
			'get_approved_instructors_for_select' => array(
				'callback' => array( $this, 'get_approved_instructors_for_select' )
			),
		);
	}

	public function get_all_instructors( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'page' => 'integer',
			'per_page' => 'integer',
			'search' => 'string',
			'status' => 'string',
		], $payload_data );

		$page     = ( isset( $payload['page'] ) ? $payload['page'] : 1 );
		$per_page = ( isset( $payload['per_page'] ) && ! empty( $payload['per_page'] ) ? $payload['per_page'] : 10 );
		$offset   = ( $page - 1 ) * $per_page;
		$search   = ( isset( $payload['search'] ) ? $payload['search'] : '' );
		$status   = ( isset( $payload['status'] ) ? $payload['status'] : 'any' );

		$Analytics         = new \Academy\Classes\Analytics();
		$total_instructors = $Analytics->get_total_number_of_instructors();

		// Set the x-wp-total header
		header( 'x-wp-total: ' . $total_instructors );

		if ( 'any' === $status ) {
			$instructors = \Academy\Helper::get_all_instructors( $offset, $per_page, $search );
		} else {
			$instructors = \Academy\Helper::get_all_instructors_by_status( $status );
		}
		$results = \Academy\Helper::prepare_all_instructors_response( $instructors );
		wp_send_json_success( $results );
	}

	public function update_instructor_status( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'ID' => 'integer',
			'status' => 'string',
		], $payload_data );

		$ID     = $payload['ID'];
		$status = ( isset( $payload['status'] ) ? $payload['status'] : '' );

		if ( get_current_user_id() === $ID ) {
			wp_send_json_error( __( 'Same user will be not able to update status', 'academy' ) );
		}

		if ( 'approved' === $status ) {
			\Academy\Helper::set_instructor_role( $ID );
		} elseif ( 'pending' === $status ) {
			\Academy\Helper::pending_instructor_role( $ID );
		} elseif ( 'remove' === $status ) {
			\Academy\Helper::remove_instructor_role( $ID );
		}

		do_action( 'academy/admin/update_instructor_status', $ID, $status );

		$instructor = \Academy\Helper::get_instructor( $ID );
		$results     = \Academy\Helper::prepare_all_instructors_response( [ $instructor ] );

		wp_send_json_success( current( $results ) );
	}

	public function get_approved_instructors_for_select() {
		$results     = [];
		$instructors = \Academy\Helper::get_all_approved_instructors();
		foreach ( $instructors as $instructor ) {
			$instructor_id        = (int) $instructor->ID;
			$instructor_full_name = \Academy\Helper::get_the_author_name( $instructor_id );
			$results[]            = array(
				'label' => $instructor_full_name,
				'value' => $instructor_id
			);
		}
		wp_send_json_success( $results );
		wp_die();
	}
}
