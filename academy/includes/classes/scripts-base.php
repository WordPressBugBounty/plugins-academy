<?php
namespace Academy\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Admin\Settings\Base as BaseSettings;
use Academy\Helper;
use Academy\Admin\Menu;
use Academy\Admin\Notices;

class ScriptsBase {

	public function get_scripts_data() {
		global $academy_addons;
		$backend_settings = BaseSettings::get_saved_data();
		$menu = new Menu();
		$return_array = array(
			'nonce'                 => wp_create_nonce( 'wp_rest' ),
			'academy_nonce'         => wp_create_nonce( 'academy_nonce' ),
			'rest_url'              => esc_url_raw( rest_url() ),
			'namespace'             => ACADEMY_PLUGIN_SLUG . '/v1/',
			'plugin_root_url'       => ACADEMY_PLUGIN_ROOT_URI,
			'plugin_root_path'      => ACADEMY_ROOT_DIR_PATH,
			'ajaxurl'               => esc_url( admin_url( 'admin-ajax.php' ) ),
			'admin_url'             => admin_url(),
			'site_url'              => site_url(),
			'route_path'            => wp_parse_url( admin_url(), PHP_URL_PATH ),
			'is_plain_permalink'    => $this->is_plain_permalink(),
			'menu'                  => wp_json_encode( Helper::get_admin_menu_list() ),
			'native_submenu_items'  => wp_json_encode( $this->get_native_submenu_items() ),
			'woocommerce_is_active' => Helper::is_active_woocommerce(),
			'ecm_is_active'         => ( Helper::is_active_ecm() ),
			'current_user_id'       => get_current_user_id(),
			'is_rtl'                => is_rtl(),
			'is_admin'              => is_admin(),
			'is_pro'                => Helper::is_active_academy_pro(),
			'addons'                => $academy_addons,
			'active_plugins'        => [
				'loco_is_active'        => Helper::is_plugin_active( 'loco-translate/loco.php' ),
				'ae_addons_is_active'    => Helper::is_plugin_active( 'academy-elementor-addons/academy-elementor-addons.php' ),
				'gemcrm_is_active'       => Helper::is_plugin_active( 'gemcrm/gemcrm.php' ),
				'zencommunity_is_active' => Helper::is_plugin_active( 'zencommunity/zencommunity.php' ),
				'ablocks_is_active'       => Helper::is_plugin_active( 'ablocks/ablocks.php' ),
				'divi_modules_is_active'   => Helper::is_plugin_active( 'academy-divi-modules/academy-divi-modules.php' ),
				'bricks_is_active'       => Helper::is_plugin_active( 'academy-bricks-addons/academy-bricks-addons.php' ),
				'storeengine_is_active'       => Helper::is_plugin_active( 'storeengine/storeengine.php' ),
				'gameengine_is_active'       => Helper::is_plugin_active( 'gameengine/gameengine.php' ),
				'gameengine_pro_is_active'   => Helper::is_plugin_active( 'gameengine-pro/gameengine-pro.php' ),
				'quizpress_is_active'        => Helper::is_plugin_active( 'quizpress/quizpress.php' ),
				'academy_digital_campus_is_active' => Helper::is_plugin_active( 'academy-digital-campus/academy-digital-campus.php' ),
				'trueplayer_is_active'  => Helper::is_plugin_active( 'trueplayer/trueplayer.php' ),
				'ecm_is_active'         => ( Helper::is_active_ecm() ),
				'buddypress_is_active'  => Helper::is_plugin_active( 'buddypress/bp-loader.php' ),
				'buddyboss_is_active'   => Helper::is_plugin_active( 'buddyboss-platform/bp-loader.php' ) || Helper::is_plugin_active( 'buddyboss-platform-release/bp-loader.php' ),
			],
			// Course Settings' BuddyPress/BuddyBoss tabs need to explain — inline,
			// where the user is actually looking — why the group picker is empty
			// when the plugin is active but its Groups/Activity components
			// (BuddyPress Settings > Components) aren't, instead of leaving the
			// select silently empty (see academy-pro Buddypress/Buddyboss::load(),
			// which gates the group-fetch AJAX handler on those same components).
			'bp_components_status'  => [
				// `bp_is_active()` reflects whichever of BuddyPress/BuddyBoss is
				// actually running (they share the same component registry and
				// are mutually exclusive), so these two flags apply to either.
				'groups_active'   => function_exists( 'bp_is_active' ) && bp_is_active( 'groups' ),
				'activity_active' => function_exists( 'bp_is_active' ) && bp_is_active( 'activity' ),
				'buddypress_settings_url' => admin_url( 'options-general.php?page=bp-components' ),
				'buddyboss_settings_url'  => admin_url( 'admin.php?page=bp-components' ),
			],
			'current_user_can'      => [
				'manage_options'            => current_user_can( 'manage_options' ),
				'manage_academy_instructor' => current_user_can( 'manage_academy_instructor' ),
				'publish_academy_courses'   => current_user_can( 'publish_academy_courses' ),
				'manage_categories'   => current_user_can( 'manage_categories' ),
				// Every resource table's "scope the list to my own author ID
				// unless I can manage everything" check (courseSlice.js and
				// friends) only ever looked at manage_options — it had no idea
				// the academy_manager role (academy/openspec/changes/academy-manager-role/)
				// exists, so a manager granted "Courses" saw zero courses:
				// the request itself was silently scoped to their own author
				// ID. This is the same "can see across every author" signal
				// as manage_options, just also true for a manager.
				'manage_academy_manager'    => current_user_can( 'manage_academy_manager' ),
			],
			// Isolated block-editor settings are disabled for now: the isolated editor isn't supported yet.
			'toplevel_menu_icon_url'    => $menu->get_toplevel_menu_icon_url(),
			'toplevel_menu_title'   => $menu->get_toplevel_menu_title(),
			'logo_url' => $menu->get_logo_url(),
			'youtube_api_key' => sanitize_text_field( get_user_meta( get_current_user_id(), 'academy_youtube_api_key', true ) ),
			'academy_is_hp_lesson_active' => (bool) Helper::get_settings( 'academy_is_hp_lesson_active' ),
		);

		if ( ! empty( $backend_settings['is_enabled_earning'] ) ) {
			$return_array['is_enabled_earning'] = $backend_settings['is_enabled_earning'];
		}

		return $return_array;
	}

	/**
	 * Submenu pages a THIRD PARTY plugin registered under Academy's own menus
	 * — e.g. Easy Content Manager attaching a taxonomy to "Courses" by
	 * setting its Admin Menu Parent to `admin.php?page=academy-courses`.
	 *
	 * `Admin\Menu::admin_menu()` re-renders the whole `academy` menu
	 * client-side from `menu` above (see `AdminMenu/index.js`), so anything a
	 * different plugin adds via `add_submenu_page()` — whether under the
	 * `academy` top level or under one of Academy's OWN submenu pages, like
	 * `academy-courses` — is server-rendered correctly but wiped out the
	 * moment React mounts, because Academy's own menu list has never heard of
	 * it. `academy-courses` itself is never a real WordPress "parent" (core
	 * only supports two menu levels), so `$submenu['academy-courses']` can
	 * only ever hold entries other plugins put there directly — there's
	 * nothing of Academy's own to exclude, unlike the top-level bucket.
	 *
	 * Grouped by which Academy menu key each item targeted, so the frontend
	 * can render top-level entries as sidebar items and per-page entries
	 * (e.g. under `academy-courses`) inside that page's own "Category /
	 * Tags"-style flyout instead.
	 *
	 * @return array<string, array[]> Menu key => list of ['title' => string, 'url' => string].
	 */
	private function get_native_submenu_items() {
		global $submenu;

		$known_menu_keys = array_keys( Helper::get_admin_menu_list() );
		$result          = array();

		$top_level_exclusions   = $known_menu_keys;
		$top_level_exclusions[] = ACADEMY_PLUGIN_SLUG . '-about';
		$result[ ACADEMY_PLUGIN_SLUG ] = $this->extract_foreign_submenu_items(
			$submenu[ ACADEMY_PLUGIN_SLUG ] ?? array(),
			$top_level_exclusions,
			ACADEMY_PLUGIN_SLUG
		);

		foreach ( $known_menu_keys as $menu_key ) {
			if ( ACADEMY_PLUGIN_SLUG === $menu_key || empty( $submenu[ $menu_key ] ) ) {
				continue;
			}

			$result[ $menu_key ] = $this->extract_foreign_submenu_items( $submenu[ $menu_key ], array(), $menu_key );
		}

		return array_filter( $result );
	}

	/**
	 * @param array    $submenu_items      A `$submenu[...]` bucket.
	 * @param string[] $exclude_page_slugs Page slugs to skip (Academy's own).
	 * @param string   $parent_slug        The menu the bucket belongs to.
	 * @return array[] List of ['title' => string, 'url' => string].
	 */
	private function extract_foreign_submenu_items( array $submenu_items, array $exclude_page_slugs, $parent_slug ) {
		$items = array();

		foreach ( $submenu_items as $item ) {
			$page_slug  = $item[2] ?? '';
			$capability = $item[1] ?? 'manage_options';

			if ( '' === $page_slug || in_array( $page_slug, $exclude_page_slugs, true ) || ! current_user_can( $capability ) ) {
				continue;
			}

			$items[] = array(
				'title' => wp_strip_all_tags( $item[0] ?? '' ),
				'url'   => $this->submenu_item_url( $page_slug, $parent_slug ),
			);
		}

		return $items;
	}

	/**
	 * The admin URL for a submenu entry, resolved the way WordPress's own menu
	 * does it (wp-admin/menu-header.php): a page registered with a callback
	 * (add_submenu_page( …, 'my-page', $callback )) lives at
	 * admin.php?page=my-page, while a file slug such as
	 * 'edit-tags.php?taxonomy=x' is a path under wp-admin. Full URLs are kept.
	 *
	 * @param string $page_slug   Submenu slug.
	 * @param string $parent_slug Menu the entry is registered under.
	 * @return string
	 */
	private function submenu_item_url( $page_slug, $parent_slug ) {
		if ( preg_match( '#^https?://#i', $page_slug ) ) {
			return $page_slug;
		}
		if ( get_plugin_page_hook( $page_slug, $parent_slug ) ) {
			return admin_url( 'admin.php?page=' . $page_slug );
		}
		return admin_url( $page_slug );
	}

	public function get_backend_scripts_data() {
		$args = array(
			'users_can_register'    => get_option( 'users_can_register' ),
			'admin_notices'         => Notices::get_notices(),
			// Lets the addon-manager card and the Social Login settings
			// screen escalate their deprecation copy consistently with the
			// admin notice, without each independently re-deriving it.
			'gemsecurity_social_login_active' => Helper::is_gemsecurity_social_login_active(),
			// "Extensions & Integrations" catalog for the Add-ons screen.
			'integrations'          => \Academy\Admin\Integrations::get_teaser_data(),
		);
		return apply_filters(
			'academy/assets/backend_scripts_data',
			array_merge(
				$this->get_scripts_data(),
				$args
			)
		);
	}

	public function get_frontend_scripts_data() {
		global $wp;
		$site_url = site_url();
		$image_id = get_post_meta( get_the_ID(), '_thumbnail_id', true );

		// Normalize an ordered {key, enabled} list: keep order, coerce enabled
		// to a real bool (json storage may return "1"/"" strings).
		$normalize_items = static function ( $items ) {
			if ( ! is_array( $items ) ) {
				return array();
			}
			$out = array();
			foreach ( $items as $item ) {
				// Stored items may come back as objects or plain key strings.
				if ( is_string( $item ) ) {
					$item = array(
						'key' => $item,
						'enabled' => true
					);
				} else {
					$item = (array) $item;
				}
				if ( empty( $item['key'] ) ) {
					continue;
				}
				$enabled = $item['enabled'] ?? true;
				$out[] = array(
					'key'     => sanitize_key( $item['key'] ),
					'enabled' => filter_var( $enabled, FILTER_VALIDATE_BOOLEAN ),
				);
			}
			return $out;
		};

		$args = array(
			'route_path' => wp_parse_url( $site_url, PHP_URL_PATH ),
			'dashboard'  => trim( get_permalink( Helper::get_settings( 'frontend_dashboard_page' ) ), $site_url ),
			'login_url' => wp_login_url( add_query_arg( $wp->query_vars, home_url( $wp->request ) ) ),
			// Raw URL for scripts: wp_logout_url() escapes & as &amp;, which would drop the nonce.
			'logout_url' => html_entity_decode( Helper::get_logout_url() ),
			'is_block_theme_active'  => Helper::is_fse_theme(),
			'current_permalink' => esc_url( get_permalink() ),
			'is_enabled_academy_login' => (bool) \Academy\Helper::get_settings( 'is_enabled_academy_login', false ),
			'is_disabled_lessons_right_click' => \Academy\Helper::get_settings( 'is_disabled_lessons_right_click', true ),
			'is_enabled_course_share'   => (bool) \Academy\Helper::get_settings( 'is_enabled_course_share', true ),
			'is_enabled_course_review'  => (bool) \Academy\Helper::get_settings( 'is_enabled_course_review', true ),
			'is_enabled_course_wishlist'    => (bool) \Academy\Helper::get_settings( 'is_enabled_course_wishlist', true ),
			'is_enabled_lessons_content_title'    => (bool) \Academy\Helper::get_settings( 'is_enabled_lessons_content_title', true ),
			'lessons_topic_length'    => (int) \Academy\Helper::get_settings( 'lessons_topic_length', 0 ),
			'is_course_single'    => is_singular( 'academy_courses' ) ? get_the_ID() : false,
			'is_notes_addon_active' => \Academy\Helper::get_addon_active_status( 'notes' ),
			// Learn-page top bar / ⋯ menu order + visibility (admin-global).
			// Ordered [{key, enabled}] arrays; the topbar + dropdown render from
			// these, so every component reads from one source.
			'academy_learn_topbar' => array(
				'topbar' => $normalize_items( \Academy\Helper::get_settings( 'learn_page_topbar_items', array() ) ),
				'menu'   => $normalize_items( \Academy\Helper::get_settings( 'learn_page_menu_items', array() ) ),
			),
			'auto_load_next_lesson' => \Academy\Helper::get_settings( 'auto_load_next_lesson' ),
			'auto_complete_topic' => \Academy\Helper::get_settings( 'auto_complete_topic' ),
			'academy_learn_page_theme_mode' => \Academy\Helper::get_settings( 'learn_page_theme_mode', 'light' ),
			'featured_image_link' => $image_id ? wp_get_attachment_image_url( $image_id ) : '',
		);

		return apply_filters(
			'academy/assets/frontend_scripts_data',
			array_merge(
				$this->get_scripts_data(),
				$args
			)
		);
	}

	public function add_backend_inline_style() {
		$custom_css = '
		.academy-blue-color {
				color: #27e527 !important;
		}';
		wp_add_inline_style( 'admin-bar', $custom_css );
	}

	public function is_course_lesson_page() {
		global $post;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['source'] ) && $post && get_post_type( $post->ID ) === 'academy_courses' ) {
			return true;
		}
		return false;
	}

	public function is_course_php_render_lesson_page() {
		global $wp_query;
		if ( ! empty( $wp_query->query_vars['curriculum_type'] ) && Helper::is_server_learn_page() ) {
			return true;
		}
		return false;
	}

	public function is_academy_common_pages() {
		global $post;
		global $wp_query;
		$flag = false;
		if (
			is_post_type_archive( 'academy_courses' ) ||
			is_post_type_archive( 'academy_booking' ) ||
			is_tax( 'academy_courses_category' ) ||
			is_tax( 'academy_courses_tag' ) ||
			is_tax( 'academy_booking_category' ) ||
			is_tax( 'academy_booking_tag' ) ||
			( ! empty( $wp_query->query['author_name'] ) && \Academy\Helper::get_settings( 'is_show_public_profile' ) )
		) {
			$flag = true;
		} elseif (
			$post &&
			(
				get_post_type( $post->ID ) === 'academy_courses' ||
				get_post_type( $post->ID ) === 'academy_booking' ||
				(int) \Academy\Helper::get_settings( 'course_page' ) === $post->ID ||
				(int) \Academy\Helper::get_settings( 'tutor_booking_page' ) === $post->ID ||
				(int) \Academy\Helper::get_settings( 'frontend_instructor_reg_page' ) === $post->ID ||
				(int) \Academy\Helper::get_settings( 'frontend_student_reg_page' ) === $post->ID ||
				(int) \Academy\Helper::get_settings( 'password_reset_page' ) === $post->ID ||
				has_shortcode( $post->post_content, 'academy_courses' ) ||
				has_shortcode( $post->post_content, 'academy_enrolled_courses' ) ||
				has_shortcode( $post->post_content, 'academy_dashboard' ) ||
				has_shortcode( $post->post_content, 'academy_instructor_registration_form' ) ||
				has_shortcode( $post->post_content, 'academy_student_registration_form' ) ||
				has_shortcode( $post->post_content, 'academy_login_form' ) ||
				has_shortcode( $post->post_content, 'academy_course_search' ) ||
				has_shortcode( $post->post_content, 'academy_enroll_form' ) ||
				has_shortcode( $post->post_content, 'academy_password_reset_form' )
			)
		) {
			$flag = true;
		}//end if
		return apply_filters( 'academy/is_common_pages', $flag );
	}

	public function is_frontend_dashboard_page() {
		global $post;
		$flag = false;
		if ( $post && (
			(int) \Academy\Helper::get_settings( 'frontend_dashboard_page' ) === $post->ID ||
			has_shortcode( $post->post_content, 'academy_dashboard' ) ||
			// The dashboard as a block, on any page.
			has_block( 'academy/student-dashboard', $post ) ||
			has_block( 'academy/dashboard', $post )
		) ) {
			$flag = true;
		}
		return apply_filters( 'academy/is_frontend_dashboard_page', $flag );
	}

	public function is_course_single_page() {
		$flag = false;
		if ( is_singular( 'academy_courses' ) ) {
			$flag = true;
		}
		return apply_filters( 'academy/is_course_single_page', $flag );
	}



	public function is_plain_permalink() {
		$permalink_structure = get_option( 'permalink_structure' );
		if ( empty( $permalink_structure ) ) {
			return true;
		}
		return false;
	}

	public function get_isolated_gutenberg_settings() {
		global $post;

		$align_wide    = get_theme_support( 'align-wide' );

		$max_upload_size = wp_max_upload_size();
		if ( ! $max_upload_size ) {
			$max_upload_size = 0;
		}

		$image_size_names = apply_filters(
			'image_size_names_choose',
			array(
				'thumbnail' => __( 'Thumbnail', 'academy' ),
				'medium'    => __( 'Medium', 'academy' ),
				'large'     => __( 'Large', 'academy' ),
				'full'      => __( 'Full Size', 'academy' ),
			)
		);

		$available_image_sizes = array();
		foreach ( $image_size_names as $image_size_slug => $image_size_name ) {
			$available_image_sizes[] = array(
				'slug' => $image_size_slug,
				'name' => $image_size_name,
			);
		}

		/**
		 * @psalm-suppress TooManyArguments
		 */
		$body_placeholder = apply_filters( 'write_your_story', __( 'Start writing or type / to choose a block', 'academy' ), $post );
		$allowed_block_types = apply_filters( 'allowed_block_types', true, $post );

		return array(
			'editor'               => array(
				'alignWide'              => $align_wide,
				'disableCustomColors'    => true,
				'disableCustomFontSizes' => true,
				'disablePostFormats'     => ! current_theme_supports( 'post-formats' ),
				/** This filter is documented in wp-admin/edit-form-advanced.php */
				'titlePlaceholder'       => __( 'Add title', 'academy' ),
				'bodyPlaceholder'        => $body_placeholder,
				'isRTL'                  => is_rtl(),
				'autosaveInterval'       => AUTOSAVE_INTERVAL,
				'maxUploadFileSize'      => $max_upload_size,
				'allowedMimeTypes'       => [],
				'styles'                 => function_exists( 'get_block_editor_theme_styles' ) ? get_block_editor_theme_styles() : array(),
				'imageSizes'             => $available_image_sizes,
				'imageDefaultSize'      => 'large',
				'imageEditing'          => true,
				'richEditingEnabled'     => user_can_richedit(),
				'codeEditingEnabled'     => false,
				'allowedBlockTypes'      => $allowed_block_types,
				'__experimentalCanUserUseUnfilteredHTML' => false,
				'__experimentalBlockPatterns' => [],
				'__experimentalBlockPatternCategories' => [],
				'availableTemplates'                   => array(),
				'postLock'                             => false,
				'supportsLayout'                       => false,
				'enableCustomFields'                   => false,
				'generateAnchors'                      => true,
				'canLockBlocks'                        => true,
				'hasFixedToolbar' => false,
				'hasInlineToolbar' => true,
			),
			'iso'                  => array(
				'blocks'      => array(
					'allowBlocks' => array(
						'core/paragraph',
						'core/image',
						'core/heading',
						'core/separator',
						'core/spacer',
						'core/columns',
						'core/column',
						'core/quote',
						'core/code',
						'core/shortcode',
						'core/group',
						'core/list',
						'core/list-item',
						'core/html',
						'core/audio',
						'core/freeform',
						'core/buttons',
						'core/button',
					),
				),
				'moreMenu'    => false,
				'sidebar'     => array(
					'inserter'  => true,
					'inspector' => true,
					'navigation' => true,
				),
				'toolbar'     => array(
					'navigation' => true,
					'inspector'  => true,
				),
				'allowEmbeds' => array(),
			),
			'saveTextarea'         => '',
			'container'            => '',
			'editorType'           => 'core',
			'allowUrlEmbed'        => true,
			'pastePlainText'       => true,
			'replaceParagraphCode' => false,
			'pluginsUrl'           => plugins_url( '', __DIR__ ),
			'version'              => '1.0.0',
		);
	}

	public function load_block_editor_scripts() {
		if ( apply_filters( 'academy/allow_block_editor_scripts', is_admin() || in_array( get_query_var( 'academy_dashboard_page' ), array( 'courses', 'lessons', 'assignments', 'bookings', 'announcements' ), true ) ) ) {
			// Gutenberg scripts
			wp_enqueue_script( 'wp-block-library' );
			wp_enqueue_script( 'wp-format-library' );
			wp_enqueue_script( 'wp-editor' );

			wp_enqueue_style( 'wp-edit-post' );
			wp_enqueue_style( 'wp-format-library' );

			wp_tinymce_inline_scripts();
			wp_enqueue_editor();
		}
	}
}
