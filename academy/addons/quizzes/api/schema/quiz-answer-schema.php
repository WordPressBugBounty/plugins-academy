<?php
namespace AcademyQuizzes\API\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait QuizAnswerSchema {

	public function get_public_item_schema() {
		$schema = array(
			'$schema'              => 'http://json-schema.org/draft-04/schema#',
			'title'                => 'answer',
			'type'                 => 'object',
			'properties'           => array(
				'answer_id' => array(
					'description'  => esc_html__( 'Unique identifier for the answer.', 'academy' ),
					'type'         => 'integer',
					'context'      => array( 'view', 'edit', 'embed' ),
					'readonly'     => true,
				),
				'quiz_id' => array(
					'description'  => esc_html__( 'The id of the academy_quizzes post_type', 'academy' ),
					'type'         => 'integer',
				),
				'answer_title' => array(
					'description'  => esc_html__( 'The title for the answer.', 'academy' ),
					'type'         => 'string',
				),
				'answer_content' => array(
					'description'  => esc_html__( 'The content for the answer.', 'academy' ),
					'type'         => 'string',
				),
				'is_correct' => array(
					'description'  => esc_html__( 'True/False Flag for the answer.', 'academy' ),
					'type'         => 'boolean',
				),
				'image_id' => array(
					'description'  => esc_html__( 'The media image id for the answer.', 'academy' ),
					'type'         => 'number',
				),
				'view_format' => array(
					'description'  => esc_html__( 'Answer view Format.', 'academy' ),
					'type'         => 'string',
				),
				'answer_order' => array(
					'description'  => esc_html__( 'The order for the answer.', 'academy' ),
					'type'         => 'integer',
				),
				'answer_created_at' => array(
					'description'  => esc_html__( 'The creation time for the answer.', 'academy' ),
					'type'         => 'string',
				),
				'answer_updated_at' => array(
					'description'  => esc_html__( 'The updated time for the answer.', 'academy' ),
					'type'         => 'string',
				),
			),
		);
		return $schema;
	}
	public function get_item_schema() {
		return [
			'answer_id'           => [
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
			'question_id'               => [
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'question_type'         => [
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'answer_title'         => [
				'type'   => 'string',
				'required'          => true,
				'sanitize_callback' => 'wp_kses_post',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'answer_content'         => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'is_correct'         => [
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'image_id'         => [
				'type'              => 'number',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'view_format'         => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'answer_order'         => [
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'answer_created_at'     => [
				'type'   => 'string',
				'format' => 'date-time',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'answer_updated_at'     => [
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
