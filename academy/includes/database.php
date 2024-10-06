<?php
namespace Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class Database {

	public static function init() {
		$self = new self();
		add_action( 'init', [ $self, 'create_academy_courses_post_type' ] );
		add_action( 'init', [ $self, 'create_academy_announcement_post_type' ] );
		add_action( 'rest_api_init', [ $self, 'register_academy_courses_meta' ] );
		add_action( 'rest_api_init', [ $self, 'register_academy_announcement_meta' ] );
	}

	public static function create_initial_custom_table() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		global $wpdb;
		$prefix          = $wpdb->prefix;
		$charset_collate = $wpdb->get_charset_collate();
		Database\CreateLessonsTable::up( $prefix, $charset_collate );
		Database\CreateLessonMetaTable::up( $prefix, $charset_collate );
	}

	public function create_academy_courses_post_type() {
		$permalinks = Helper::get_permalink_structure();
		$post_type = 'academy_courses';
		$course_page_id = Helper::get_settings( 'course_page' );
		$has_archive = get_post( $course_page_id ) ? urldecode( get_page_uri( $course_page_id ) ) : 'courses';
		register_post_type(
			$post_type,
			array(
				'labels'                => array(
					'name'                  => esc_html__( 'Courses', 'academy' ),
					'singular_name'         => esc_html__( 'Course', 'academy' ),
					'search_items'          => esc_html__( 'Search Courses', 'academy' ),
					'parent_item_colon'     => esc_html__( 'Parent Courses:', 'academy' ),
					'not_found'             => esc_html__( 'No Courses found.', 'academy' ),
					'not_found_in_trash'    => esc_html__( 'No Courses found in Trash.', 'academy' ),
					'archives'              => esc_html__( 'Course archives', 'academy' ),
				),
				'public'                => true,
				'publicly_queryable'    => true,
				'show_ui'               => true,
				'show_in_menu'          => false,
				'show_in_admin_bar'     => false,
				'show_in_nav_menus'     => false,
				'hierarchical'          => false,
				'has_archive'           => $has_archive,
				'rewrite'             => $permalinks['course_rewrite_slug'] ? array(
					'slug'       => $permalinks['course_rewrite_slug'],
					'with_front' => true,
					'feeds'      => true,
				) : false,
				'query_var'             => true,
				'delete_with_user'      => false,
				'supports'              => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'trackbacks', 'custom-fields', 'comments', 'post-formats' ),
				'show_in_rest'          => true,
				'rest_base'             => $post_type,
				'rest_namespace'        => ACADEMY_PLUGIN_SLUG . '/v1',
				'rest_controller_class' => 'WP_REST_Posts_Controller',
				'capability_type'           => 'post',
				'capabilities'              => array(
					'edit_post'             => 'edit_academy_course',
					'read_post'             => 'read_academy_course',
					'delete_post'           => 'delete_academy_course',
					'delete_posts'          => 'delete_academy_courses',
					'edit_posts'            => 'edit_academy_courses',
					'edit_others_posts'     => 'edit_others_academy_courses',
					'publish_posts'         => 'publish_academy_courses',
					'read_private_posts'    => 'read_private_academy_courses',
					'create_posts'          => 'edit_academy_courses',
				),
			)
		);

		register_taxonomy(
			$post_type . '_category',
			$post_type,
			array(
				'hierarchical'          => true,
				'query_var'             => true,
				'public'                => true,
				'show_ui'               => false,
				'show_admin_column'     => false,
				'_builtin'              => true,
				'capabilities'          => array(
					'manage_terms' => 'manage_categories',
					'edit_terms'   => 'edit_categories',
					'delete_terms' => 'delete_categories',
					'assign_terms' => 'assign_terms',
				),
				'show_in_rest'          => true,
				'rest_base'             => $post_type . '_category',
				'rest_namespace'        => ACADEMY_PLUGIN_SLUG . '/v1',
				'rest_controller_class' => 'WP_REST_Terms_Controller',
				'rewrite'               => array(
					'slug'         => $permalinks['category_rewrite_slug'],
					'with_front'   => false,
					'hierarchical' => true,
				),
			)
		);

		register_taxonomy(
			$post_type . '_tag',
			$post_type,
			array(
				'hierarchical'          => false,
				'query_var'             => true,
				'public'                => true,
				'show_ui'               => false,
				'show_admin_column'     => false,
				'_builtin'              => true,
				'capabilities'          => array(
					'manage_terms' => 'manage_post_tags',
					'edit_terms'   => 'edit_post_tags',
					'delete_terms' => 'delete_post_tags',
					'assign_terms' => 'assign_terms',
				),
				'show_in_rest'          => true,
				'rest_base'             => $post_type . '_tag',
				'rest_namespace'        => ACADEMY_PLUGIN_SLUG . '/v1',
				'rest_controller_class' => 'WP_REST_Terms_Controller',
				'rewrite'               => array(
					'slug'       => $permalinks['tag_rewrite_slug'],
					'with_front' => false,
				),
			)
		);
	}

	public function create_academy_announcement_post_type() {
		$post_type = 'academy_announcement';
		register_post_type(
			$post_type,
			array(
				'labels'                => array(
					'name'                  => esc_html__( 'Announcements', 'academy' ),
					'singular_name'         => esc_html__( 'Announcement', 'academy' ),
					'search_items'          => esc_html__( 'Search announcements', 'academy' ),
					'parent_item_colon'     => esc_html__( 'Parent announcements:', 'academy' ),
					'not_found'             => esc_html__( 'No announcements found.', 'academy' ),
					'not_found_in_trash'    => esc_html__( 'No announcements found in Trash.', 'academy' ),
					'archives'              => esc_html__( 'Announcement archives', 'academy' ),
				),
				'public'                => true,
				'publicly_queryable'    => true,
				'show_ui'               => true,
				'show_in_menu'          => false,
				'show_in_admin_bar'     => false,
				'show_in_nav_menus'     => false,
				'hierarchical'          => true,
				'rewrite'               => array( 'slug' => 'announcement' ),
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
					'edit_post'             => 'edit_academy_announcement',
					'read_post'             => 'read_academy_announcement',
					'delete_post'           => 'delete_academy_announcement',
					'delete_posts'          => 'delete_academy_announcements',
					'edit_posts'            => 'edit_academy_announcements',
					'edit_others_posts'     => 'edit_others_academy_announcements',
					'publish_posts'         => 'publish_academy_announcements',
					'read_private_posts'    => 'read_private_academy_announcements',
					'create_posts'          => 'edit_academy_announcements',
				),
			)
		);
	}

	public function register_academy_courses_meta() {
		$course_meta = [
			'academy_course_type'                       => 'string',
			'academy_course_product_id'                 => 'integer',
			'academy_course_download_id'                => 'integer',
			'academy_course_max_students'               => 'integer',
			'academy_course_language'                   => 'string',
			'academy_course_difficulty_level'           => 'string',
			'academy_course_benefits'                   => 'string',
			'academy_course_requirements'               => 'string',
			'academy_course_audience'                   => 'string',
			'academy_course_materials_included'         => 'string',
			'academy_is_enabled_course_qa'              => 'boolean',
			'academy_is_enabled_course_announcements'   => 'boolean',
			'academy_course_certificate_id'             => 'integer',
			'academy_course_enable_certificate'         => 'boolean',
		];

		foreach ( $course_meta as $meta_key => $meta_value_type ) {
			register_meta(
				'post',
				$meta_key,
				array(
					'object_subtype' => 'academy_courses',
					'type'           => $meta_value_type,
					'single'         => true,
					'show_in_rest'   => true,
				)
			);
		}

		register_meta(
			'post',
			'academy_course_duration',
			array(
				'object_subtype' => 'academy_courses',
				'type'           => 'array',
				'single'         => true,
				'show_in_rest'   => [
					'schema' => array(
						'items' => array(
							'type'       => 'integer',
							'properties' => [
								'hours'   => array(
									'type' => 'integer',
								),
								'minutes' => array(
									'type' => 'integer',
								),
								'seconds' => array(
									'type' => 'integer',
								),
							],
						),
					),
				],
			)
		);

		register_meta(
			'post',
			'academy_course_intro_video',
			array(
				'object_subtype' => 'academy_courses',
				'type'           => 'array',
				'single'         => true,
				'show_in_rest'   => [
					'schema' => array(
						'items' => array(
							'type'       => 'string',
							'properties' => [
								'type' => array(
									'type' => 'string',
								),
								'url'  => array(
									'type' => 'string',
								),
							],
						),
					),
				],
			)
		);

		register_meta(
			'post',
			'academy_course_curriculum',
			array(
				'object_subtype' => 'academy_courses',
				'type'           => 'array',
				'single'         => true,
				'show_in_rest'   => [
					'schema' => array(
						'items' => array(
							'type'       => 'object',
							'properties' => [
								'topics'  => array(
									'type'  => 'array',
									'items' => array(
										'type'       => 'object',
										'properties' => array(
											'id'   => array(
												'type' => 'integer',
											),
											'name' => array(
												'type' => 'string',
											),
											'type' => array(
												'type' => 'string',
											),
											'topics' => array(
												'type'       => 'array',
												'items' => array(
													'type'       => 'object',
													'properties' => array(
														'id'   => array(
															'type' => 'integer',
														),
														'name' => array(
															'type' => 'string',
														),
														'type' => array(
															'type' => 'string',
														),
													)
												)
											)
										),
									),
								),
								'title'   => array(
									'type' => 'string',
								),
								'content' => array(
									'type' => 'string',
								),
							],
						),
					),
				],
			)
		);
	}

	public function register_academy_announcement_meta() {
		register_meta(
			'post',
			'academy_announcements_course_ids',
			array(
				'object_subtype' => 'academy_announcement',
				'type'           => 'array',
				'single'         => true,
				'show_in_rest'   => [
					'schema' => array(
						'items' => array(
							'type'       => 'object',
							'properties' => [
								'label'   => array(
									'type' => 'string',
								),
								'value'   => array(
									'type' => 'integer',
								),
							],
						),
					),
				],
			)
		);
	}
}
