<?php
namespace AcademyQuizzes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class Database {

	public static function init() {
		$self = new self();
		add_action( 'init', [ $self, 'create_academy_quiz_post_type' ] );
		add_action( 'rest_api_init', [ $self, 'register_academy_quiz_meta' ] );
	}

	public function create_academy_quiz_post_type() {
		$post_type = 'academy_quiz';
		register_post_type(
			$post_type,
			array(
				'labels'                => array(
					'name'                  => esc_html__( 'Quizzes', 'academy' ),
					'singular_name'         => esc_html__( 'quiz', 'academy' ),
					'search_items'          => esc_html__( 'Search quizzes', 'academy' ),
					'parent_item_colon'     => esc_html__( 'Parent quizzes:', 'academy' ),
					'not_found'             => esc_html__( 'No quizzes found.', 'academy' ),
					'not_found_in_trash'    => esc_html__( 'No quizzes found in Trash.', 'academy' ),
					'archives'              => esc_html__( 'quiz archives', 'academy' ),
				),
				'public'                => true,
				'publicly_queryable'    => true,
				'show_ui'               => false,
				'show_in_menu'          => false,
				'hierarchical'          => false,
				'rewrite'               => array( 'slug' => 'quiz' ),
				'query_var'             => true,
				'has_archive'           => true,
				'delete_with_user'      => false,
				'supports'              => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'comments', 'post-formats' ),
				'show_in_rest'          => true,
				'rest_base'             => $post_type,
				'rest_namespace'        => ACADEMY_PLUGIN_SLUG . '/v1',
				'rest_controller_class' => 'WP_REST_Posts_Controller',
				'capability_type'           => 'post',
				'capabilities'              => array(
					'edit_post'             => 'edit_academy_quiz',
					'read_post'             => 'read_academy_quiz',
					'delete_post'           => 'delete_academy_quiz',
					'delete_posts'          => 'delete_academy_quizzes',
					'edit_posts'            => 'edit_academy_quizzes',
					'edit_others_posts'     => 'edit_others_academy_quizzes',
					'publish_posts'         => 'publish_academy_quizzes',
					'read_private_posts'    => 'read_private_academy_quizzes',
					'create_posts'          => 'edit_academy_quizzes',
				),
			)
		);
	}

	public function register_academy_quiz_meta() {
		$course_meta = [
			'academy_quiz_time'                         => 'integer',
			'academy_quiz_time_unit'                    => 'string',
			'academy_quiz_hide_quiz_time'               => 'boolean',
			'academy_quiz_feedback_mode'                => 'string',
			'academy_quiz_passing_grade'                => 'integer',
			'academy_quiz_max_questions_for_answer'     => 'integer',
			'academy_quiz_max_attempts_allowed'         => 'integer',
			'academy_quiz_auto_start'                   => 'boolean',
			'academy_quiz_questions_order'              => 'string',
			'academy_quiz_hide_question_number'         => 'boolean',
			'academy_quiz_short_answer_characters_limit' => 'integer',
		];

		foreach ( $course_meta as $meta_key => $meta_value_type ) {
			register_meta(
				'post',
				$meta_key,
				array(
					'object_subtype' => 'academy_quiz',
					'type'           => $meta_value_type,
					'single'         => true,
					'show_in_rest'   => true,
				)
			);
		}
		register_meta(
			'post',
			'academy_quiz_questions',
			array(
				'object_subtype' => 'academy_quiz',
				'type'           => 'array',
				'single'         => true,
				'show_in_rest'   => [
					'schema' => array(
						'items' => array(
							'type'       => 'object',
							'properties' => [
								'id'   => array(
									'type' => 'integer',
								),
								'title' => array(
									'type' => 'string',
								),
							],
						),
					),
				],
			)
		);
	}

	public static function create_initial_custom_table() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		global $wpdb;
		$prefix          = $wpdb->prefix;
		$charset_collate = $wpdb->get_charset_collate();
		Database\CreateQuizQuestionsTable::up( $prefix, $charset_collate );
		Database\CreateQuizAnswersTable::up( $prefix, $charset_collate );
		Database\CreateQuizAttemptsTable::up( $prefix, $charset_collate );
		Database\CreateQuizAttemptAnswersTable::up( $prefix, $charset_collate );
	}

	public function permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden_context',
				esc_html__( 'Sorry, you are not allowed to get quiz attempt answers.', 'academy' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
	}
}
