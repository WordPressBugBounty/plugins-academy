<?php
namespace  Academy\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes\Sanitizer;
use Academy\Classes\AbstractAjaxHandler;

class Student extends AbstractAjaxHandler {
	public function __construct() {
		$this->actions = array(
			'get_enrolled_students' => array(
				'callback' => array( $this, 'get_enrolled_students' ),
				'allow_visitor_action' => true
			),
			'get_all_students' => array(
				'callback' => array( $this, 'get_all_students' )
			),
			'remove_student' => array(
				'callback' => array( $this, 'remove_student' )
			),
			'frontend/get_students' => array(
				'callback' => array( $this, 'get_students' ),
				'capability' => 'manage_academy_instructor'
			),
			'update_enrollment_status' => array(
				'callback' => array( $this, 'update_enrollment_status' ),
				'capability' => 'manage_academy_instructor',
			)
		);
	}

	public function get_enrolled_students() {
		$total_enrolled_students = \Academy\Helper::get_total_number_of_students();
		wp_send_json_success( array( 'total_enrolled_students' => $total_enrolled_students ) );
	}

	public function get_all_students( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'page' => 'integer',
			'per_page' => 'integer',
			'search' => 'string',
		], $payload_data );

		$page     = ( isset( $payload['page'] ) ? $payload['page'] : 1 );
		$per_page = ( isset( $payload['per_page'] ) ? $payload['per_page'] : 10 );
		$search   = ( isset( $payload['search'] ) ? $payload['search'] : '' );
		$offset   = ( $page - 1 ) * $per_page;

		$Analytics      = new \Academy\Classes\Analytics();
		$total_students = $Analytics->get_total_number_of_students();

		// Set the x-wp-total header
		header( 'x-wp-total: ' . $total_students );

		$students = \Academy\Helper::get_all_students( $offset, $per_page, $search );
		$students = \Academy\Helper::prepare_get_all_students_response( $students );
		wp_send_json_success( $students );
		wp_die();
	}

	public function remove_student( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'student_id' => 'integer',
		], $payload_data );

		$student_id       = ( isset( $payload['student_id'] ) ? $payload['student_id'] : 0 );
		$enrolled_courses = \Academy\Helper::get_enrolled_courses_ids_by_user( $student_id );

		if ( get_current_user_id() === $student_id ) {
			wp_send_json_error( __( 'Sorry, You can\'t remove yourself.', 'academy' ) );
		} elseif ( count( $enrolled_courses ) ) {
			wp_send_json_error( __( 'Sorry, You need to cancel all enrollment before remove student', 'academy' ) );
		}

		$has_removed = \Academy\Helper::remove_student( $student_id );
		if ( $has_removed ) {
			wp_send_json_success( $student_id );
		}
		wp_send_json_error( __( 'Something Wrong! Try again', 'academy' ) );
	}

	public function get_students( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'page' => 'integer',
			'per_page' => 'integer',
			'search' => 'string'
		], $payload_data );

		$page     = ( isset( $payload['page'] ) ? $payload['page'] : 1 );
		$per_page = ( isset( $payload['per_page'] ) ? $payload['per_page'] : 10 );
		$search   = ( isset( $payload['search'] ) ? $payload['search'] : '' );
		$offset   = ( $page - 1 ) * $per_page;

		$student_ids = \Academy\Helper::get_total_students_by_instructor( get_current_user_id(), $offset, $per_page, $search );
		$student_data = \Academy\Helper::prepare_get_all_students_response( $student_ids, get_current_user_id() );
		// Set the x-wp-total header
		header( 'x-wp-total: ' . count( $student_ids ) );

		if ( $search ) {
			$results = array_filter( $student_data, function( $student ) use ( $search ) {
				return stripos( $student->display_name, $search ) !== false;
			});
			foreach ( $results as $result ) {
				$data[] = $result;
			}
			wp_send_json_success( array_slice( $data, $offset, $per_page ) );
		}
		wp_send_json_success( array_slice( $student_data, $offset, $per_page ) );
	}

	public static function update_enrollment_status( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
			'enrolled_id' => 'integer',
			'student_id' => 'integer',
			'status' => 'string',
		], $payload_data );

		$course_id = $payload['course_id'] ?? 0;
		$enrolled_id = $payload['enrolled_id'] ?? 0;
		$student_id = $payload['student_id'] ?? 0;
		$status = $payload['status'] ?? '';
		$updated_status = 'approved' === $status ? 'completed' : $status;
		if ( ! $course_id || ! $student_id || ! $enrolled_id ) {
			wp_send_json_error( __( 'Course ID, Enrolled ID and Student ID is Required', 'academy' ) );
		}

		$is_updated = \Academy\Helper::update_enrollment_status( $course_id, $enrolled_id, $student_id, $updated_status );
		if ( $is_updated ) {
			wp_send_json_success( __( 'Successfully change Enrollment status!', 'academy' ) );
		}
		wp_send_json_error( __( 'Failed: Enrollment status was not changed.', 'academy' ) );
	}
}
