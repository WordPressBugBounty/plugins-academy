<?php
namespace Academy\API\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait LessonSchema {
	public function get_public_item_schema() {
		$schema = array(
			'$schema'              => 'http://json-schema.org/draft-04/schema#',
			'title'                => 'attempt',
			'type'                 => 'object',
			'properties'           => array(
				'ID' => array(
					'description'  => esc_html__( 'Unique identifier for the lesson.', 'academy' ),
					'type'         => 'integer',
					'context'      => array( 'view', 'edit', 'embed' ),
					'readonly'     => true,
				),
				'lesson_author' => array(
					'description'  => esc_html__( 'Lesson Author.', 'academy' ),
					'type'         => 'string',
				),
				'lesson_date' => array(
					'description'  => esc_html__( 'Lesson Date.', 'academy' ),
					'type'         => 'string',
				),
				'lesson_date_gmt' => array(
					'description'  => esc_html__( 'Lesson Date GMT.', 'academy' ),
					'type'         => 'string',
				),
				'lesson_title' => array(
					'description'  => esc_html__( 'Lesson Title.', 'academy' ),
					'type'         => 'string',
				),
				'lesson_name' => array(
					'description'  => esc_html__( 'Lesson Name.', 'academy' ),
					'type'         => 'string',
				),
				'lesson_content' => array(
					'description'  => esc_html__( 'Lesson Content.', 'academy' ),
					'type'         => 'string',
				),
				'lesson_excerpt' => array(
					'description'  => esc_html__( 'Lesson Exerpt.', 'academy' ),
					'type'         => 'string',
				),
				'lesson_status' => array(
					'description'  => esc_html__( 'Lesson Status.', 'academy' ),
					'type'         => 'string',
				),
				'comment_status' => array(
					'description'  => esc_html__( 'Lesson Comment Status', 'academy' ),
					'type'         => 'status',
				),
				'comment_count' => array(
					'description'  => esc_html__( 'Lesson Comment Count Number', 'academy' ),
					'type'         => 'integer',
				),
				'lesson_modified' => array(
					'description'  => esc_html__( 'Lesson Modified Date.', 'academy' ),
					'type'         => 'string',
				),
				'lesson_modified_gmt' => array(
					'description'  => esc_html__( 'Lesson Modified Date GMT.', 'academy' ),
					'type'         => 'string',
				),
				'meta'     => [
					'type'   => 'object',
					'description'  => esc_html__( 'Lesson Meta.', 'academy' ),
					'properties' => [
						'featured_media' => [
							'type'          => 'integer',
						],
						'attachment' => [
							'type'          => 'integer',
						],
						'is_previewable' => [
							'type'          => 'boolean',
						],
						'video_duration' => [
							'type'   => 'object',
						],
						'video_source' => [
							'type'          => 'object',
						],
					]
				],
			),
		);

		return apply_filters( 'academy/api/lesson/public_item_schema', $schema );
	}

	public function get_item_schema() {
		$schema = [
			'ID'           => [
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'lesson_author'               => [
				'type'   => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'lesson_date'     => [
				'type'   => 'string',
				'format' => 'date-time',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'lesson_date_gmt'     => [
				'type'   => 'string',
				'format' => 'date-time',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'lesson_title'     => [
				'type'   => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'lesson_name'     => [
				'type'   => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'lesson_content'     => [
				'type'   => 'string',
				'sanitize_callback' => function ( $content ) {
					$allowed_tags = wp_kses_allowed_html( 'post' );
					$allowed_tags['input'] = array(
						'type'              => true,
						'name'              => true,
						'value'             => true,
						'class'             => true,
					);
					$allowed_tags['form'] = array(
						'action'            => true,
						'method'            => true,
						'class'             => true,
					);
					$allowed_tags['iframe'] = array(
						'src'             => true,
						'width'           => true,
						'height'          => true,
						'frameborder'     => true,
						'allow'           => true,
						'allowfullscreen' => true,
					);
					return wp_kses( $content, $allowed_tags );
				},
			],
			'lesson_excerpt'     => [
				'type'   => 'string',
				'sanitize_callback' => function ( $content ) {
					$allowed_tags = wp_kses_allowed_html( 'post' );
					$allowed_tags['input'] = array(
						'type'              => true,
						'name'              => true,
						'value'             => true,
						'class'             => true,
					);
					$allowed_tags['form'] = array(
						'action'            => true,
						'method'            => true,
						'class'             => true,
					);
					return wp_kses( $content, $allowed_tags );
				},
			],
			'lesson_status'     => [
				'type'   => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'comment_status'     => [
				'type'   => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'comment_count'           => [
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			],

			'lesson_modified'     => [
				'type'   => 'string',
				'format' => 'date-time',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'lesson_modified_gmt'     => [
				'type'   => 'string',
				'format' => 'date-time',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'meta'     => [
				'type'   => 'object',
				'properties' => [
					'featured_media' => [
						'type'          => 'integer',
						'sanitize_callback' => 'absint',
					],
					'attachment' => [
						'type'          => 'integer',
						'sanitize_callback' => 'absint',
					],
					'is_previewable' => [
						'type'          => 'boolean',
						'sanitize_callback' => 'rest_sanitize_boolean',
					],
					'video_duration' => [
						'type'   => 'object',
						'properties' => [
							'hours' => [
								'type'          => 'integer',
								'sanitize_callback' => 'absint',
							],
							'minutes' => [
								'type'          => 'integer',
								'sanitize_callback' => 'absint',
							],
							'seconds' => [
								'type'          => 'integer',
								'sanitize_callback' => 'absint',
							],
						]
					],
					'video_source' => [
						'type'          => 'object',
						'properties' => [
							'type' => [
								'type'          => 'string',
								'sanitize_callback' => 'sanitize_text_field',
							],
							'url' => [
								'type'          => 'string',
								'sanitize_callback' => 'sanitize_text_field',
							],
						]
					],
				]
			],
		];
		return apply_filters( 'academy/api/lesson/item_schema', $schema );
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
				'description'       => __( 'Current User already enrolled or not by course_id', 'academy' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'author'   => array(
				'description'       => __( 'Query by author id', 'academy' ),
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
