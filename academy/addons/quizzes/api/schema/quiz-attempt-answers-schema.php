<?php
namespace AcademyQuizzes\API\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait QuizAttemptAnswersSchema {

	public function get_public_item_schema() {
		$schema = array(
			'$schema'              => 'http://json-schema.org/draft-04/schema#',
			'title'                => 'attempt-answer',
			'type'                 => 'object',
			'properties'           => array(
				'attempt_answer_id' => array(
					'description'  => esc_html__( 'Unique identifier for the quiz attempt answer.', 'academy' ),
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
				'attempt_id' => array(
					'description'  => esc_html__( 'The id of the quiz attempt', 'academy' ),
					'type'         => 'integer',
				),
				'answer' => array(
					'description'  => esc_html__( 'Answer.', 'academy' ),
					'type'         => 'string',
				),
				'question_mark'             => [
					'type'              => 'decimal',
					'description'  => esc_html__( 'Quiz Question Mark.', 'academy' ),
				],
				'achieved_mark'             => [
					'type'              => 'decimal',
					'description'  => esc_html__( 'Quiz Question Achieved Mark.', 'academy' ),
				],
				'minus_mark'            => [
					'type'              => 'decimal',
					'description'  => esc_html__( 'Quiz Question Answer Minus Mark.', 'academy' ),
				],
				'is_correct' => array(
					'description'  => esc_html__( 'Answer flag for true/false', 'academy' ),
					'type'         => 'boolean',
				),
			),
		);
		return $schema;
	}
	public function get_item_schema() {
		return [
			'attempt_answer_id'           => [
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
			'attempt_id'            => [
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'answer'         => [
				'type'   => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'question_mark'             => [
				'type'              => 'decimal',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'achieved_mark'             => [
				'type'              => 'decimal',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'minus_mark'            => [
				'type'              => 'decimal',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'is_correct'            => [
				'type'              => 'boolean',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
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
			'search'   => array(
				'description'       => __( 'Limit results to those matching a string.', 'academy' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}
}
