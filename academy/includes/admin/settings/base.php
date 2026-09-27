<?php
namespace Academy\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Base {
	public static function get_saved_data() {
		$settings = get_option( ACADEMY_SETTINGS_NAME );
		if ( $settings ) {
			return apply_filters( 'academy/admin/settings/saved_data', json_decode( $settings, true ) );
		}
		return [];
	}
	public static function get_default_data() {
		return apply_filters('academy/admin/settings/base_default_data', [
			// global style
			'primary_color' => '#7b68ee',
			'secondary_color' => '#f2f0fd',
			'text_color' => '#131d2b',
			'border_color' => '#e5e7eb',
			'gray_color' => '#f6f7f8',
			'surface_color' => '#ffffff',
			// global style — dark mode palette
			'dark_primary_color' => '#7b68ee',
			'dark_secondary_color' => '#252140',
			'dark_text_color' => '#e6e9ef',
			'dark_border_color' => '#333d4b',
			'dark_gray_color' => '#1f2632',
			'dark_surface_color' => '#161c26',
			// general
			'frontend_dashboard_page' => '',
			'is_enabled_academy_login' => true,
			'frontend_student_reg_page' => '',
			'is_student_can_upload_files' => true,
			'is_reset_academy_enrolled_course_progress' => false,
			'monetization_engine' => '',
			// Password Reset
			'password_reset_page' => '',
			// Learn Page
			'lessons_page'                      => '',
			'lesson_content_width'              => 100,
			'lesson_content_width_unit'         => '%',
			'is_enabled_lessons_php_render'     => false,
			'lesson_self_hosted_video_autoplay' => true,
			'is_enabled_lessons_theme_header_footer'    => false,
			'is_disabled_lessons_right_click'   => true,
			'is_enabled_lessons_content_title'  => false,
			'lessons_topic_length'   => 0,
			'is_enabled_academy_lessons_comment' => false,
			'is_disabled_lessons_video_skip' => true,
			// Video completion gate: 0 = off, otherwise the % of the video a
			// student must watch before the lesson can be marked complete.
			'lessons_video_completion_threshold' => 0,
			// Same idea for videos KodezenPlayer can't natively track (opaque
			// third-party embeds) — 0 = off, otherwise seconds the lesson must
			// stay open before it can be marked complete.
			'external_video_min_watch_seconds' => 0,
			// Text Reader (TTS)
			'is_enabled_lesson_text_reader'  => false,
			'lesson_tts_rate'                => '1',
			'lesson_tts_pitch'               => '1',
			'lesson_tts_highlight'           => true,
			'lesson_tts_auto_scroll'         => true,
			'lesson_tts_hide_text'           => false,
			// Learn Page — Layout (admin-global, composable)
			// Which learn page students use: the React player or the block page.
			'learn_page_engine'              => 'react',
			'learn_page_sidebar_width'       => 340,
			'learn_page_learner_theme_toggle' => true,
			'learn_page_learner_width_toggle' => true,
			'learn_page_colors'              => array(
				'light' => array(),
				'dark'  => array(),
			),
			// Default 'right' preserves Academy's existing sidebar-on-the-right learn page.
			'learn_page_nav_placement'       => 'right',    // left | right | top | hidden | bottom
			'learn_page_layout_width'        => 'standard', // standard | wide | focused
			'learn_page_show_side_panel'     => false,
			'learn_page_sidebar_default_open' => true,      // start with the curriculum docked open
			'learn_page_sidebar_position'    => 'left',     // left | right (curriculum drawer side)
			'learn_page_content_load'        => 'ajax',     // ajax (SPA in-place) | reload (full page reload per topic — max third-party compatibility)
			'learn_page_show_content_header' => true,
			'learn_page_show_mark_complete'  => true,
			'learn_page_theme_mode'          => 'light',    // light | dark | system (learner toggle can always override)
			// Ordered top-bar action items (drag to reorder, toggle to hide).
			'learn_page_topbar_items'        => array(
				array(
					'key' => 'progress',
					'enabled' => true
				),
				array(
					'key' => 'review',
					'enabled' => true
				),
				array(
					'key' => 'notes',
					'enabled' => true
				),
				array(
					'key' => 'announcements',
					'enabled' => true
				),
				array(
					'key' => 'qa',
					'enabled' => true
				),
			),
			// Ordered ⋯ dropdown menu items.
			'learn_page_menu_items'          => array(
				array(
					'key' => 'favorite',
					'enabled' => true
				),
				array(
					'key' => 'share',
					'enabled' => true
				),
				array(
					'key' => 'exit',
					'enabled' => true
				),
			),
			// Quiz Proctoring — global on/off, applies to every quiz on the site.
			'quiz_proctoring_browser_lock' => false,
			// Course Archive
			'course_page' => '',
			'is_enabled_course_share' => true,
			'is_enabled_course_wishlist' => true,
			'is_enabled_course_review' => true,
			'is_enabled_course_popup_review' => false,
			'minimum_course_completion_on_review' => 0,
			'is_enabled_course_coming_soon' => false,
			'is_show_course_excerpt' => false,
			'is_enable_course_review_edit' => false,
			'course_archive_sidebar_position' => 'right',
			'course_archive_filters' => [
				[
					'search'   => true,
				],
				[
					'category'   => true,
				],
				[
					'tags'   => true,
				],
				[
					'levels'   => true,
				],
				[
					'type'   => true,
				],
			],
			'course_archive_courses_per_row' => array(
				'desktop' => 3,
				'tablet'  => 2,
				'mobile'  => 1,
			),
			'course_archive_courses_per_page' => 12,
			'course_archive_courses_order' => 'DESC',
			'course_archive_category_filter_dropdown' => false,
			'course_card_style' => 'default',

			// Course Single/Details
			'is_enabled_course_single_enroll_count' => true,
			'is_opened_course_single_first_topic' => true,

			// Course Certificate
			'academy_primary_certificate_id'    => 0,

			// instructor
			'frontend_instructor_reg_page'      => '',
			'is_show_public_profile'            => true,
			'is_instructor_can_publish_course'  => false,
			'is_instructor_update_course_price' => true,
			'is_enabled_instructor_review' => true,
			// Frontend dashboard
			'academy_frontend_dashboard_redirect_login_page'  => 'academy_login',
			'academy_frontend_dashboard_redirect_login_url' => '',
			// WooCommerce
			'woo_force_login_before_enroll' => true,
			'hide_course_product_from_shop_page' => false,
			'woo_order_auto_complete' => false,
			'woo_order_auto_complete_status' =>
			[
				'on-hold',
				'pending',
				'processing',
				'completed'
			],
			'is_enabled_fd_link_inside_woo_order_page' => true,
			'woo_order_page_fd_link_label' => esc_html__( 'Courses Dashboard', 'academy' ),
			'store_link_inside_frontend_dashboard' => true,
			'store_link_label_inside_frontend_dashboard' => esc_html__( 'Store Dashboard', 'academy' ),
			'store_force_login_before_enroll' => true,
			// lesson migration
			'academy_is_hp_lesson_active'     => true,
			// chatgpt integration
			'chatgpt_api_key'   => '',
			'chatgpt_model'     => 'gpt-3.5-turbo',
			'chatgpt_img_model' => 'dall-e-2',
			'allow_instructor_to_use_chatgpt' => false,
			// after registration course enroll settings
			'enable_auto_enroll_after_registration' => false,
			'after_registration_auto_enroll_courses_id' => [],
			'user_roles_for_auto_enroll' => [],
			// Maintenance mode — frontend-only lockout (wp-admin is never
			// gated; admins/instructors always bypass). 'course' scope uses
			// academy_maintenance_course_ids to scope the lock to specific
			// courses instead of every course-archive/single/learn page.
			'academy_maintenance_scope' => 'off', // off | full_site | course
			'academy_maintenance_course_ids' => [],
			// The archive listing isn't specific to any one course, so it's
			// a standalone toggle rather than one of the per-course page types below.
			'academy_maintenance_lock_archive' => false,
			// Which of the selected courses' own pages get locked under the
			// 'course' scope. 'full_site' scope ignores this and locks both.
			'academy_maintenance_locked_pages' => [ 'single', 'learn' ],
			'academy_maintenance_message' => esc_html__( "We're currently making improvements to our course platform. Please check back soon.", 'academy' ),
			'academy_maintenance_end_time' => '',
		]);
	}

	public static function save_settings( $form_data = false ) {
		$default_data = self::get_default_data();
		$saved_data = self::get_saved_data();
		$settings_data = wp_parse_args( $saved_data, $default_data );

		if ( $form_data ) {
			$settings_data = wp_parse_args( $form_data, $settings_data );
		}
		// update_option() creates the option itself if it doesn't exist yet,
		// so a single call covers both cases. The old count( $saved_data )
		// branch on add_option() looked like it handled "first save", but
		// add_option() is a no-op once the option row exists — and it always
		// does, since install seeds it as "{}" (an empty, but present, JSON
		// object). count() on that decodes to 0, so every save after install
		// silently discarded itself forever.
		return update_option( ACADEMY_SETTINGS_NAME, wp_json_encode( $settings_data ) );
	}
}
