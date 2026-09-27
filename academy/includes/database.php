<?php
namespace Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\API\Authorization\{ CourseController, AnnouncementController };

class Database {

	public static function init() {
		$self = new self();
		add_action( 'init', [ $self, 'create_academy_courses_post_type' ], 5 );
		add_action( 'init', [ $self, 'create_academy_announcement_post_type' ], 5 );
		add_action( 'init', [ $self, 'create_academy_lesson_post_type' ], 5 );

		// Allow all post type to be registered before flushing permalink (priority 6).
		// Addons should use init with priority 5 to register post type before scheduling a permalink-flush.
		add_action( 'init', [ $self, 'maybe_flush_rewrite_rules' ], 6 );

		add_action( 'rest_api_init', [ $self, 'register_academy_courses_meta' ] );
		add_action( 'rest_api_init', [ $self, 'register_academy_announcement_meta' ] );
		add_action( 'rest_api_init', [ $self, 'register_academy_courses_category_meta' ] );

		add_filter( 'rest_pre_insert_academy_courses', [ $self, 'dedupe_course_meta' ] );
		add_action( 'rest_after_insert_academy_courses', [ $self, 'maybe_assign_sticky_priority' ], 10, 1 );
	}

	/**
	 * Collapse duplicate rows of `single` course meta before the REST layer writes.
	 *
	 * Two overlapping saves of a course that has no row yet for a key each insert
	 * their own, leaving duplicates. With more than one row WP's REST meta
	 * handler skips its "value unchanged" check, so every later save of an
	 * unchanged value fails with a 500 (rest_meta_database_error) — and aborts
	 * the metas queued after it, so those never save.
	 *
	 * @param object $prepared_post Post being prepared by the REST controller.
	 * @return object
	 */
	public function dedupe_course_meta( $prepared_post ) {
		$course_id = isset( $prepared_post->ID ) ? (int) $prepared_post->ID : 0;
		if ( ! $course_id ) {
			return $prepared_post;
		}

		foreach ( get_registered_meta_keys( 'post', 'academy_courses' ) as $key => $args ) {
			if ( empty( $args['single'] ) || empty( $args['show_in_rest'] ) ) {
				continue;
			}
			$rows = get_post_meta( $course_id, $key, false );
			if ( count( $rows ) > 1 ) {
				delete_post_meta( $course_id, $key );
				add_post_meta( $course_id, $key, end( $rows ), true );
			}
		}

		return $prepared_post;
	}

	/**
	 * Give a newly-featured course a priority so it has a stable place in the
	 * featured list instead of tying with every other unprioritised one.
	 *
	 * Priority is `menu_order` (see `Helper::apply_sticky_course_ordering()`),
	 * and a course that has never been prioritised sits at WP's default of 0.
	 * This appends such a course to the END of the featured list — the same
	 * thing `menu_order` does everywhere else in WP — rather than assigning an
	 * arbitrary value. A priority already set (by the editor's featured
	 * drag-list, or by hand through Page Attributes) is never overwritten.
	 *
	 * Hooked on `rest_after_insert_*`, not `rest_insert_*`: the REST posts
	 * controller writes meta AFTER firing `rest_insert_*`, so reading the
	 * sticky flag there returns the value from BEFORE this save and the very
	 * save that features a course would be missed.
	 *
	 * @param \WP_Post $post The course that was just created or updated.
	 */
	public function maybe_assign_sticky_priority( $post ) {
		if ( 'academy_courses' !== $post->post_type ) {
			return;
		}

		if ( ! \Academy\Helper::is_course_sticky( $post->ID ) ) {
			return;
		}

		// Re-read rather than trusting $post->menu_order: $post is the
		// snapshot taken before meta ran, and the editor may have saved a
		// priority in this same request.
		if ( 0 !== (int) get_post_field( 'menu_order', $post->ID ) ) {
			return;
		}

		wp_update_post( array(
			'ID'         => $post->ID,
			'menu_order' => \Academy\Helper::get_max_sticky_priority() + 1,
		) );
	}

	/**
	 * Executes scheduled rewrite rule flushing.
	 *
	 * This function intended to be used with `init` action hook
	 * after registering all cpt and taxonomies.
	 *
	 * To schedule rewrite rule flush call the static helper method.
	 *
	 * Example
	 *  `\Academy\Helper::flush_rewrite_rules();`
	 *
	 * @return void
	 * @since 3.3.8
	 */
	public function maybe_flush_rewrite_rules() {
		if ( 'yes' === get_option( 'academy_required_rewrite_flush' ) ) {
			update_option( 'academy_required_rewrite_flush', 'no' );
			flush_rewrite_rules(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- one-shot flush after a permalink-affecting settings change
		}
	}

	public static function create_initial_custom_table() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		global $wpdb;
		$prefix          = $wpdb->prefix;
		$charset_collate = $wpdb->get_charset_collate();
		Database\CreateLessonsTable::up( $prefix, $charset_collate );
		Database\CreateLessonMetaTable::up( $prefix, $charset_collate );
		Database\CreateGuardianMapTable::up( $prefix, $charset_collate );
		Database\CreateAttachmentDownloadsTable::up( $prefix, $charset_collate );
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
				'supports'              => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'trackbacks', 'custom-fields', 'comments', 'post-formats', 'sticky', 'page-attributes' ),
				'show_in_rest'          => true,
				'rest_base'             => $post_type,
				'rest_namespace'        => ACADEMY_PLUGIN_SLUG . '/v1',
				'rest_controller_class' => CourseController::class,
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
				'rest_controller_class' => AnnouncementController::class,
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
			// Canonical price for the price-driven Free/Paid derivation in the
			// Course Type UI — always writable regardless of which (if any)
			// monetization engine is configured; a linked WooCommerce/EDD
			// product's own price is kept in sync with this when an engine
			// is active, but this value is the source of truth for whether
			// the course is 'free' or 'paid'.
			'academy_course_price'                      => 'number',
			'academy_course_product_id'                 => 'integer',
			'academy_course_download_id'                => 'integer',
			'academy_store_membership'                  => 'integer',
			'academy_course_max_students'               => 'integer',
			'academy_course_language'                   => 'string',
			'academy_course_difficulty_level'           => 'string',
			'academy_course_benefits'                   => 'string',
			'academy_course_requirements'               => 'string',
			'academy_course_audience'                   => 'string',
			'academy_course_materials_included'         => 'string',
			'academy_is_enabled_course_qa'              => 'boolean',
			'academy_is_enabled_course_announcements'   => 'boolean',
			'academy_is_disabled_course_review'         => 'boolean',
			'academy_course_certificate_id'             => 'integer',
			'academy_course_enable_certificate'         => 'boolean',
			'academy_courses_mempr_membership_id'       => 'integer',
			'academy_course_timer_enabled'              => 'boolean',
			'academy_course_timer_duration_value'       => 'integer',
			'academy_course_timer_duration_unit'        => 'string',
			'academy_course_timer_expiry_action'        => 'string',
			'academy_course_is_sticky'                  => 'boolean',
		];

		if ( \Academy\Helper::get_settings( 'is_enabled_course_coming_soon' ) ) {
			$course_meta['academy_course_coming_soon_end_date']  = 'string';
		}

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

		register_meta( 'post',
			'academy_rcp_membership_levels',
			array(
				'object_subtype' => 'academy_courses',
				'type'           => 'array',
				'single'         => true,
				'show_in_rest'   => [
					'schema' => array(
						'items' => array(
							'type'       => 'integer',
						),
					),
				],
			)
		);

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

		$this->register_academy_meta();
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

	public function register_academy_courses_category_meta() {
		register_meta(
			'term',
			'academy_category_image',
			array(
				'object_subtype' => 'academy_courses_category',
				'type'           => 'integer',
				'single'         => true,
				'show_in_rest'   => true,
				'auth_callback'  => function () {
					return current_user_can( 'manage_categories' );
				},
			)
		);

		register_rest_field(
			'academy_courses_category',
			'category_image_url',
			array(
				'get_callback' => function ( $term ) {
					return Helper::get_the_course_category_image_url( $term['id'] );
				},
			)
		);
	}

	public function create_academy_lesson_post_type() {
		$post_type = 'academy_lessons';
		register_post_type(
			$post_type,
			array(
				'labels'                => array(
					'name'                  => esc_html__( 'Lessons', 'academy' ),
					'singular_name'         => esc_html__( 'Lesson', 'academy' ),
					'search_items'          => esc_html__( 'Search lessons', 'academy' ),
					'parent_item_colon'     => esc_html__( 'Parent lessons:', 'academy' ),
					'not_found'             => esc_html__( 'No lessons found.', 'academy' ),
					'not_found_in_trash'    => esc_html__( 'No lessons found in Trash.', 'academy' ),
					'archives'              => esc_html__( 'Lesson archives', 'academy' ),
					'view_item'            => esc_html__( 'View Lesson', 'academy' ),
				),
				'public'                => true,
				'publicly_queryable'    => true,
				'show_ui'               => true,
				'show_in_menu'          => false,
				'show_in_admin_bar'     => false,
				'show_in_nav_menus'     => false,
				'hierarchical'          => true,
				'rewrite'               => array( 'slug' => 'lesson' ),
				'query_var'             => true,
				'has_archive'           => true,
				'delete_with_user'      => false,
				'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'comments', 'post-formats' ),
				'show_in_rest' => true,
				'capability_type'           => 'post',
				'capabilities'              => array(
					'edit_post'             => 'edit_academy_lesson',
					'read_post'             => 'read_academy_lesson',
					'delete_post'           => 'delete_academy_lesson',
					'delete_posts'          => 'delete_academy_lessons',
					'edit_posts'            => 'edit_academy_lessons',
					'edit_others_posts'     => 'edit_others_academy_lessons',
					'publish_posts'         => 'publish_academy_lessons',
					'read_private_posts'    => 'read_private_academy_lessons',
					'create_posts'          => 'edit_academy_lessons',
				),
			)
		);
	}

	private function register_academy_meta() {
		/**
		 * Extension point so other plugins register their own course meta
		 * without Academy hardcoding the keys here. Each entry is
		 * `'<meta_key>' => array( ...register_meta() args... )`; missing
		 * `object_subtype` / `single` / `sanitize_callback` fall back to the
		 * course defaults below. Contributors add to this filter only when
		 * their plugin is loaded, so nothing here carries weight when that
		 * plugin is absent.
		 *
		 * SECURITY NOTE: `show_in_rest` defaults to `false`. Non-protected
		 * meta keys (i.e. not prefixed with `_`) registered with
		 * `show_in_rest => true` are exposed to the REST API for *any* client
		 * that can read the post — `auth_callback` only gates writes, not
		 * reads. Plugins that need their field in the REST response must opt
		 * in explicitly via `'show_in_rest' => true` in their own args.
		 *
		 * @param array $fields Map of meta key => register_meta() args.
		 */
		$extra_course_meta = apply_filters( 'academy/course/register_meta_fields', array() );
		if ( is_array( $extra_course_meta ) ) {
			foreach ( $extra_course_meta as $meta_key => $meta_args ) {
				if ( ! is_string( $meta_key ) || '' === $meta_key || ! is_array( $meta_args ) ) {
					continue;
				}

				// Prevent silent collisions: if this key is already registered
				// for this subtype (by Academy core or another plugin), skip it
				// rather than letting a later call silently overwrite the first.
				if ( registered_meta_key_exists( 'post', $meta_key, 'academy_courses' ) ) {
					_doing_it_wrong(
						'academy/course/register_meta_fields',
						esc_html(
							sprintf(
								'Academy: meta key "%s" is already registered for "academy_courses"; skipping duplicate registration from academy/course/register_meta_fields.',
								$meta_key
							)
						),
						esc_html( ACADEMY_VERSION )
					);
					continue;
				}

				$meta_args = wp_parse_args(
					$meta_args,
					array(
						'object_subtype'    => 'academy_courses',
						'single'            => true,
						'show_in_rest'      => false, // opt-in, not opt-out — see docblock above.
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					)
				);

				// register_meta() requires a valid type; guard against a plugin
				// passing something bogus so we don't fail silently with no signal.
				$valid_types = array( 'string', 'boolean', 'integer', 'number', 'array', 'object' );
				if ( ! in_array( $meta_args['type'], $valid_types, true ) ) {
					_doing_it_wrong(
						'academy/course/register_meta_fields',
						esc_html(
							sprintf(
								'Academy: meta key "%s" registered with invalid type "%s"; skipping.',
								$meta_key,
								is_scalar( $meta_args['type'] ) ? (string) $meta_args['type'] : gettype( $meta_args['type'] )
							)
						),
						esc_html( ACADEMY_VERSION )
					);
					continue;
				}

				// If a plugin declares a structured type but didn't supply its own
				// sanitize_callback, don't silently sanitize an array/object as a
				// string — better to require them to be explicit than to mangle data.
				if ( in_array( $meta_args['type'], array( 'array', 'object' ), true )
					&& 'sanitize_text_field' === $meta_args['sanitize_callback']
				) {
					_doing_it_wrong(
						'academy/course/register_meta_fields',
						esc_html(
							sprintf(
								'Academy: meta key "%s" has type "%s" but no explicit sanitize_callback; skipping registration.',
								$meta_key,
								$meta_args['type']
							)
						),
						esc_html( ACADEMY_VERSION )
					);
					continue;
				}

				$registered = register_meta( 'post', $meta_key, $meta_args );

				if ( false === $registered ) {
					_doing_it_wrong(
						'academy/course/register_meta_fields',
						esc_html( sprintf( 'Academy: register_meta() failed for key "%s".', $meta_key ) ),
						esc_html( ACADEMY_VERSION )
					);
				}
			}//end foreach
		}//end if
	}
}
