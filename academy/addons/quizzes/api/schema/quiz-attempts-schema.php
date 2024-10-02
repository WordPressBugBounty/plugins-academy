<?php
namespace AcademyQuizzes\API\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait QuizAttemptsSchema {

	public function get_public_item_schema() {
		$schema = array(
			'$schema'              => 'http://json-schema.org/draft-04/schema#',
			'title'                => 'attempt',
			'type'                 => 'object',
			'properties'           => array(
				'attempt_id' => array(
					'description'  => esc_html__( 'Unique identifier for the quiz attempt.', 'academy' ),
					'type'         => 'integer',
					'context'      => array( 'view', 'edit', 'embed' ),
					'readonly'     => true,
				),
				'course_id' => array(
					'description'  => esc_html__( 'The id of the academy_courses post_type', 'academy' ),
					'type'         => 'integer',
				),
				'quiz_id' => array(
					'description'  => esc_html__( 'The id of the academy_quizzes post_type', 'academy' ),
					'type'         => 'integer',
				),
				'user_id' => array(
					'description'  => esc_html__( 'The id of the user ID', 'academy' ),
					'type'         => 'integer',
				),
				'total_questions' => array(
					'description'  => esc_html__( 'Total Questions', 'academy' ),
					'type'         => 'integer',
				),
				'total_answered_questions' => array(
					'description'  => esc_html__( 'Total Answered Questions', 'academy' ),
					'type'         => 'integer',
				),
				'total_marks' => array(
					'description'  => esc_html__( 'Total Marks', 'academy' ),
					'type'         => 'decimal',
				),
				'earned_marks' => array(
					'description'  => esc_html__( 'Total Earned Marks', 'academy' ),
					'type'         => 'decimal',
				),
				'attempt_info' => array(
					'description'  => esc_html__( 'Attempt Info.', 'academy' ),
					'type'         => 'string',
				),
				'attempt_status' => array(
					'description'  => esc_html__( 'Attempt Status.', 'academy' ),
					'type'         => 'string',
				),
				'attempt_ip' => array(
					'description'  => esc_html__( 'Attempt IP.', 'academy' ),
					'type'         => 'string',
				),
				'attempt_started_at' => array(
					'description'  => esc_html__( 'The creation time for the attempt.', 'academy' ),
					'type'         => 'string',
				),
				'attempt_ended_at' => array(
					'description'  => esc_html__( 'The updated time for the attempt.', 'academy' ),
					'type'         => 'string',
				),
			),
		);
		return $schema;
	}
	public function get_item_schema() {
		return [
			'attempt_id'           => [
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'course_id'             => [
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'quiz_id'               => [
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'user_id'               => [
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'total_questions'               => [
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'total_answered_questions'              => [
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'total_marks'               => [
				'type'              => 'decimal',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'earned_marks'              => [
				'type'              => 'decimal',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'attempt_info'         => [
				'type'   => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'attempt_status'         => [
				'type'   => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'attempt_ip'         => [
				'type'   => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'attempt_started_at'     => [
				'type'   => 'string',
				'format' => 'date-time',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'answer_ended_at'     => [
				'type'   => 'string',
				'format' => 'date-time',
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}
	public function get_collection_params() {
		return array(
			'page'     => array(
				'description'       => __( 'Current page of the collection.', 'academy' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'minimum'           => 1,
			),
			'per_page' => array(
				'description'       => __( 'Maximum number of items to be returned in result set.', 'academy' ),
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'quiz_id'   => array(
				'description'       => __( 'Get attemps by quiz_id', 'academy' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'course_id'   => array(
				'description'       => __( 'Cureent User already enrolled or not by course_id', 'academy' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'search'   => array(
				'description'       => __( 'Limit results to those matching a string.', 'academy' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}
}
