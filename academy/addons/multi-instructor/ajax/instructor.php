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

}
