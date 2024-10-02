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
			'woocommerce_is_active' => Helper::is_active_woocommerce(),
			'current_user_id'       => get_current_user_id(),
			'is_rtl'                => is_rtl(),
			'is_admin'              => is_admin(),
			'is_pro'                => Helper::is_active_academy_pro(),
			'addons'                => $academy_addons,
			'current_user_can'      => [
				'manage_options'            => current_user_can( 'manage_options' ),
				'manage_academy_instructor' => current_user_can( 'manage_academy_instructor' ),
				'publish_academy_courses'   => current_user_can( 'publish_academy_courses' ),
				'manage_categories'   => current_user_can( 'manage_categories' ),
			],
			'editor_settings' => $this->get_isolated_gutenberg_settings(),
			'toplevel_menu_icon_url'    => $menu->get_toplevel_menu_icon_url(),
			'toplevel_menu_title'   => $menu->get_toplevel_menu_title(),
			'logo_url' => $menu->get_logo_url(),
		);

		if ( ! empty( $backend_settings['is_enabled_earning'] ) ) {
			$return_array['is_enabled_earning'] = $backend_settings['is_enabled_earning'];
		}

		return $return_array;
	}

	public function get_backend_scripts_data() {
		$args = array(
			'users_can_register'    => get_option( 'users_can_register' ),
			'admin_notices'         => Notices::get_notices(),
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
		$args = array(
			'route_path' => wp_parse_url( $site_url, PHP_URL_PATH ),
			'dashboard'  => trim( get_permalink( Helper::get_settings( 'frontend_dashboard_page' ) ), $site_url ),
			'login_url' => wp_login_url( add_query_arg( $wp->query_vars, home_url( $wp->request ) ) ),
			'logout_url' => wp_logout_url( home_url( '/' ) ),
			'current_permalink' => esc_url( get_permalink() ),
			'is_enabled_academy_login' => (bool) \Academy\Helper::get_settings( 'is_enabled_academy_login', false ),
			'is_disabled_lessons_right_click' => \Academy\Helper::get_settings( 'is_disabled_lessons_right_click', true ),
			'is_enabled_course_share'   => (bool) \Academy\Helper::get_settings( 'is_enabled_course_share', true ),
			'is_enabled_course_review'  => (bool) \Academy\Helper::get_settings( 'is_enabled_course_review', true ),
			'is_enabled_course_wishlist'    => (bool) \Academy\Helper::get_settings( 'is_enabled_course_wishlist', true ),
			'is_enabled_lessons_content_title'    => (bool) \Academy\Helper::get_settings( 'is_enabled_lessons_content_title', true ),
			'lessons_topic_length'    => (int) \Academy\Helper::get_settings( 'lessons_topic_length', true ),
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
		if ( ! empty( $wp_query->query_vars['curriculum_type'] ) && Helper::get_settings( 'is_enabled_lessons_php_render' ) ) {
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
			is_tax( 'academy_courses_category' ) ||
			is_tax( 'academy_courses_tag' ) ||
			( ! empty( $wp_query->query['author_name'] ) && \Academy\Helper::get_settings( 'is_show_public_profile' ) )
		) {
			$flag = true;
		} elseif (
			$post &&
			(
				get_post_type( $post->ID ) === 'academy_courses' ||
				(int) \Academy\Helper::get_settings( 'course_page' ) === $post->ID ||
				(int) \Academy\Helper::get_settings( 'frontend_instructor_reg_page' ) === $post->ID ||
				(int) \Academy\Helper::get_settings( 'frontend_student_reg_page' ) === $post->ID ||
				(int) \Academy\Helper::get_settings( 'password_reset_page' ) === $post->ID ||
				has_shortcode( $post->post_content, 'academy_courses' ) ||
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
		if ( $post && ( (int) \Academy\Helper::get_settings( 'frontend_dashboard_page' ) === $post->ID || has_shortcode( $post->post_content, 'academy_dashboard' ) ) ) {
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


	public function web_fonts_url( $font ) {
		$font_url = '';
		if ( 'off' !== _x( 'on', 'Google font: on or off', 'academy' ) ) {
			$font_url = add_query_arg( 'family', rawurlencode( $font ), '//fonts.googleapis.com/css' );
		}
		return $font_url;
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
