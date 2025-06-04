<?php
namespace AcademyMultiInstructor\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes\Sanitizer;
use Academy\Classes\AbstractAjaxHandler;

class Instructor extends AbstractAjaxHandler {
	protected $namespace = ACADEMY_PLUGIN_SLUG . '_multi_instructor';
	public function __construct() {
		// instructor related ajax.
		$this->actions = array(
			'get_instructors_by_course_id' => array(
				'callback' => array( $this, 'get_instructors_by_course_id' ),
			),
			'get_active_instructors' => array(
				'callback' => array( $this, 'get_active_instructors' ),
			),
			'remove_instructor_from_course' => array(
				'callback' => array( $this, 'remove_instructor_from_course' ),
			),
			'save_instructor_earning_percentage' => array(
				'callback' => array( $this, 'save_instructor_earning_percentage' ),
				'capability' => 'manage_academy_instructor',
			),
			'get_instructor_earning_percentage' => array(
				'callback' => array( $this, 'get_instructor_earning_percentage' ),
				'capability' => 'manage_academy_instructor',
			)
		);
	}

	public function get_instructors_by_course_id( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
		], $payload_data );

		$course_id = $payload['course_id'];
		$results   = [];
		if ( $course_id ) {
			$results = \Academy\Helper::get_instructors_by_course_id( $course_id );
		} else {
			$results = \Academy\Helper::get_current_instructor();
			$results = \Academy\Helper::prepare_all_instructors_response( $results );
		}
		if ( $results ) {
			wp_send_json_success( $results );
			wp_die();
		}
		wp_send_json_error( $results );
		wp_die();
	}

	public function get_active_instructors() {
		$instructors = \Academy\Helper::get_all_approved_instructors();
		$results     = \Academy\Helper::prepare_all_instructors_response( $instructors );
		wp_send_json_success( $results );
		wp_die();
	}

	public function remove_instructor_from_course( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
			'instructor_id' => 'integer',
		], $payload_data );

		$course_id     = $payload['course_id'];
		$instructor_id = $payload['instructor_id'];
		$is_delete     = delete_user_meta( $instructor_id, 'academy_instructor_course_id', $course_id );
		if ( $is_delete ) {
			wp_send_json_success( $is_delete );
			wp_die();
		}
		wp_send_json_error( $is_delete );
		wp_die();
	}

	public function save_instructor_earning_percentage( $payload_data ) {
		$payload = Sanitizer::sanitize_payload( [
			'instructor_id' => 'integer',
			'percentage'    => 'integer',
		], $payload_data );

		$instructor_id   = $payload['instructor_id'] ?? 0;
		$instructor_rate = $payload['percentage'] ?? 0;

		if ( ! $instructor_id ) {
			wp_send_json_error( __( 'Invalid instructor ID.', 'academy' ) );
		}

		$updated = update_user_meta( $instructor_id, 'academy_instructor_earning_percentage', $instructor_rate );

		if ( $updated ) {
			wp_send_json_success( __( 'Successfully saved the instructors earning percentage.', 'academy' ) );
		}

		wp_send_json_error( __( 'Failed to save the instructors earning percentage.', 'academy' ) );
	}

	public function get_instructor_earning_percentage( $payload_data ) {
		$payload = Sanitizer::sanitize_payload( [
			'instructor_id' => 'integer',
		], $payload_data );

		$instructor_id = $payload['instructor_id'] ?? 0;

		if ( ! $instructor_id ) {
			wp_send_json_error( __( 'Invalid Instructor ID.', 'academy' ) );
		}

		$instructor_rate = (int) get_user_meta( $instructor_id, 'academy_instructor_earning_percentage', true );
		if ( empty( $instructor_rate ) ) {
			$instructor_rate = (int) \Academy\Helper::get_settings( 'instructor_commission_percentage' );
		}

		if ( ! empty( $instructor_rate ) ) {
			wp_send_json_success( $instructor_rate );
		}

		wp_send_json_error( __( 'Sorry, Instructor earning percentage not available. Please set Instructor earning percentage in settings.', 'academy' ) );
	}

}
