<?php
namespace AcademyQuizzes\API\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait QuizQuestionSchema {

	public function get_public_item_schema() {
		$schema = array(
			'$schema'              => 'http://json-schema.org/draft-04/schema#',
			'title'                => 'question',
			'type'                 => 'object',
			'properties'           => array(
				'question_id' => array(
					'description'  => esc_html__( 'Unique identifier for the question.', 'academy' ),
					'type'         => 'integer',
					'context'      => array( 'view', 'edit', 'embed' ),
					'readonly'     => true,
				),
				'quiz_id' => array(
					'description'  => esc_html__( 'The id of the academy_quizzes post_type', 'academy' ),
					'type'         => 'integer',
				),
				'question_title' => array(
					'description'  => esc_html__( 'The title for the question.', 'academy' ),
					'type'         => 'string',
				),
				'question_name' => array(
					'description'  => esc_html__( 'The slug for the question.', 'academy' ),
					'type'         => 'string',
				),
				'question_content' => array(
					'description'  => esc_html__( 'The content for the question.', 'academy' ),
					'type'         => 'string',
				),
				'question_status' => array(
					'description'  => esc_html__( 'The status for the question.', 'academy' ),
					'type'         => 'string',
				),
				'question_level' => array(
					'description'  => esc_html__( 'The label for the question.', 'academy' ),
					'type'         => 'string',
				),
				'question_type' => array(
					'description'  => esc_html__( 'The type for the question.', 'academy' ),
					'type'         => 'string',
				),
				'question_score' => array(
					'description'  => esc_html__( 'The score for the question.', 'academy' ),
					'type'         => 'number',
				),
				'question_order' => array(
					'description'  => esc_html__( 'The order for the question.', 'academy' ),
					'type'         => 'integer',
				),
				'question_created_at' => array(
					'description'  => esc_html__( 'The creation time for the question.', 'academy' ),
					'type'         => 'string',
				),
				'question_updated_at' => array(
					'description'  => esc_html__( 'The updated time for the question.', 'academy' ),
					'type'         => 'string',
				),
			),
		);
		return $schema;
	}
	public function get_item_schema() {
		return [
			'question_id'           => [
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'quiz_id'               => [
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'question_title'         => [
				'type'   => 'string',
				'required'          => true,
				'sanitize_callback' => 'wp_kses_post',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'question_name'         => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'question_content'         => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'question_status'         => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'question_level'         => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'question_type'         => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'question_score'         => [
				'type'              => 'number',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'question_settings'         => [
				'type'              => 'object',
				'validate_callback' => 'rest_validate_request_arg',
				'properties' => array(
					'display_points'   => array(
						'type' => 'boolean',
						'sanitize_callback' => 'absint',
						'validate_callback' => 'rest_validate_request_arg',
					),
					'answer_required' => array(
						'type' => 'boolean',
						'sanitize_callback' => 'absint',
						'validate_callback' => 'rest_validate_request_arg',
					),
				),
			],
			'question_order'         => [
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'question_created_at'     => [
				'type'   => 'string',
				'format' => 'date-time',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'question_updated_at'     => [
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
			'search'   => array(
				'description'       => __( 'Limit results to those matching a string.', 'academy' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}
}
