<?php
namespace AcademyQuizzes\API\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait QuizQuestionSchema {

	/**
	 * Runs $sanitize() with `safecss_filter_attr_allow_css` temporarily
	 * tolerant of `rgb()`/`rgba()`/`hsl()`/`hsla()` color function values in
	 * `style` attributes.
	 *
	 * By default `safecss_filter_attr()` (which `wp_kses_post()`/`wp_kses()`
	 * use to sanitize `style` attributes) rejects any CSS value containing a
	 * literal `(` character, since it only recognizes `var()`/`calc()`/etc.
	 * as safe function calls. Browsers serialize the rich-text editor's
	 * text-color picker as `style="color: rgb(r, g, b);"`, so without this
	 * allowance the color is silently stripped on every save. The extra
	 * check is scoped to this exact "property: rgb(numbers, %, spaces only)"
	 * shape, so it can't be used to smuggle anything kses wouldn't otherwise
	 * allow.
	 *
	 * @param callable $sanitize
	 */
	private static function with_color_function_allowance( callable $sanitize ) {
		$allow_color_functions = static function ( $allow_css, $css_test_string ) {
			if ( $allow_css ) {
				return $allow_css;
			}
			$is_color_function = preg_match(
				'/^[a-zA-Z-]+\s*:\s*(rgb|rgba|hsl|hsla)\([0-9.,%\s]+\)\s*$/i',
				trim( $css_test_string )
			);
			return $is_color_function ? true : $allow_css;
		};

		add_filter( 'safecss_filter_attr_allow_css', $allow_color_functions, 10, 2 );
		$sanitized = $sanitize();
		remove_filter( 'safecss_filter_attr_allow_css', $allow_color_functions, 10 );

		return $sanitized;
	}

	public static function sanitize_rich_title( $value ) {
		return self::with_color_function_allowance(
			static function () use ( $value ) {
				return wp_kses_post( $value );
			}
		);
	}

	/**
	 * Sanitizes the question Description field: the same rich formatting
	 * `sanitize_rich_title()` allows (bold/italic/lists/links/color/etc, via
	 * `wp_kses_post()`'s tag set), plus a single embedded `<audio>` player
	 * (optionally with `<source>` children) inserted via the "Insert Audio"
	 * button — `<audio>`/`<source>` aren't in `wp_kses_post()`'s default
	 * allowed tags, so they're added explicitly.
	 *
	 * @param string $value
	 */
	public static function sanitize_description( $value ) {
		$allowed_tags = wp_kses_allowed_html( 'post' );

		$allowed_tags['audio'] = [
			'controls' => true,
			'src'      => true,
			'preload'  => true,
		];

		$allowed_tags['source'] = [
			'src'  => true,
			'type' => true,
		];

		return self::with_color_function_allowance(
			static function () use ( $value, $allowed_tags ) {
				return wp_kses( $value, $allowed_tags );
			}
		);
	}

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
				'question_title_type' => array(
					'description'  => esc_html__( 'The format of the question title: plain or rich.', 'academy' ),
					'type'         => 'string',
					'enum'         => array( 'plain', 'rich' ),
				),
				'question_name' => array(
					'description'  => esc_html__( 'The slug for the question.', 'academy' ),
					'type'         => 'string',
				),
				'question_content' => array(
					'description'  => esc_html__( 'The content for the question.', 'academy' ),
					'type'         => 'string',
				),
				'question_explanation' => array(
					'description'  => esc_html__( 'The explanation for the question.', 'academy' ),
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
				'question_negative_score' => array(
					'description'  => esc_html__( 'The score for the question.', 'academy' ),
					'type'         => 'number',
				),
				'question_image_id' => array(
					'description' => esc_html__( 'Then image id for the question', 'academy' ),
					'type'        => 'number'
				),
				'question_audio_id' => array(
					'description' => esc_html__( 'The audio attachment id for the question.', 'academy' ),
					'type'        => 'number'
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
				'sanitize_callback' => [ self::class, 'sanitize_rich_title' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'question_title_type'    => [
				'type'              => 'string',
				'enum'              => [ 'plain', 'rich' ],
				'default'           => 'plain',
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'question_name'         => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'question_content'      => [
				'type'              => 'string',
				'sanitize_callback' => [ self::class, 'sanitize_description' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'question_explanation'  => [
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
			'question_negative_score' => [
				'type'              => 'number',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'question_image_id' => [
				'type'              => 'number',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'question_audio_id' => [
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
					'image'            => array(
						'type'       => 'object',
						'properties' => array(
							'size'      => array(
								'type' => 'string',
								'enum' => array( 'small', 'medium', 'large', 'full' ),
							),
							'alignment' => array(
								'type' => 'string',
								'enum' => array( 'none', 'left', 'center', 'right' ),
							),
							'alt'       => array(
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_text_field',
							),
							'caption'   => array(
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_text_field',
							),
						),
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
			'used_in_count'           => [
				'description' => esc_html__( 'Number of quizzes that reference this question.', 'academy' ),
				'type'        => 'integer',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'question_updated_at'     => [
				'type'   => 'string',
				'format' => 'date-time',
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}
	public function get_collection_params() {
		return [
			'page'           => [
				'description'       => __( 'Current page of the collection.', 'academy' ),
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'per_page'       => [
				'description'       => __( 'Maximum number of items to be returned in result set.', 'academy' ),
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'search'         => [
				'description'       => __( 'Limit results to those matching a string.', 'academy' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'question_type'  => [
				'description'       => __( 'Filter by question type.', 'academy' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'question_level' => [
				'description'       => __( 'Filter by difficulty level.', 'academy' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'orderby'        => [
				'description'       => __( 'Sort by field.', 'academy' ),
				'type'              => 'string',
				'default'           => 'question_created_at',
				'enum'              => [ 'question_created_at', 'question_title', 'question_id' ],
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'order'          => [
				'description'       => __( 'Sort direction.', 'academy' ),
				'type'              => 'string',
				'default'           => 'DESC',
				'enum'              => [ 'ASC', 'DESC' ],
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}
}
