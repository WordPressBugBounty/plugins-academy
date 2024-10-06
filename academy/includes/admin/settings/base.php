<?php
namespace Academy\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Base {
	public static function get_saved_data() {
		$settings = get_option( ACADEMY_SETTINGS_NAME );
		if ( $settings ) {
			return json_decode( $settings, true );
		}
		return [];
	}
	public static function get_default_data() {
		return apply_filters('academy/admin/settings/base_default_data', [
			// global style
			'primary_color' => '#7b68ee',
			'secondary_color' => '#eae8fa',
			'text_color' => '#111',
			'border_color' => '#E5E4E6',
			'gray_color' => '#f6f7f9',
			// general
			'frontend_dashboard_page' => '',
			'is_enabled_academy_login' => true,
			'is_enabled_academy_web_font' => true,
			'frontend_student_reg_page' => '',
			'is_student_can_upload_files' => true,
			'monetization_engine' => '',
			// Password Reset
			'password_reset_page' => '',
			// Learn Page
			'lessons_page'                              => '',
			'is_enabled_lessons_php_render'             => false,
			'lessons_topbar_logo'                       => '',
			'is_enabled_lessons_theme_header_footer'    => false,
			'is_disabled_lessons_right_click'   => true,
			'is_enabled_lessons_content_title'   => false,
			'lessons_topic_length'   => 0,
			'is_enabled_academy_player'   => false,
			// Course Archive
			'course_page' => '',
			'is_enabled_course_share' => true,
			'is_enabled_course_wishlist' => true,
			'is_enabled_course_review' => true,
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
			// WooCommerce
			'woo_force_login_before_enroll' => true,
			'hide_course_product_from_shop_page' => false,
			'woo_order_auto_complete' => false,
			'is_enabled_fd_link_inside_woo_dashboard' => true,
			'woo_dashboard_fd_link_label' => esc_html__( 'Courses Dashboard', 'academy' ),
			'is_enabled_fd_link_inside_woo_order_page' => true,
			'woo_order_page_fd_link_label' => esc_html__( 'Courses Dashboard', 'academy' ),
			'store_link_inside_frontend_dashboard' => true,
			'store_link_label_inside_frontend_dashboard' => esc_html__( 'Store Dashboard', 'academy' ),
		]);
	}

	public static function save_settings( $form_data = false ) {
		$default_data = self::get_default_data();
		$saved_data = self::get_saved_data();
		$settings_data = wp_parse_args( $saved_data, $default_data );
		if ( $form_data ) {
			$settings_data = wp_parse_args( $form_data, $settings_data );
		}
		// if settings already saved, then update it
		if ( count( $saved_data ) ) {
			return update_option( ACADEMY_SETTINGS_NAME, wp_json_encode( $settings_data ) );
		}
		return add_option( ACADEMY_SETTINGS_NAME, wp_json_encode( $settings_data ) );
	}
}
