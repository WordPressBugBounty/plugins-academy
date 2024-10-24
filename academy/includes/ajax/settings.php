<?php
namespace  Academy\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes\AbstractAjaxHandler;
use Academy\Admin\Settings\Base as BaseSettings;
use Academy\Classes\Sanitizer;

class Settings extends AbstractAjaxHandler {
	public function __construct() {
		$this->actions = array(
			'update_base_settings' => array(
				'callback' => array( $this, 'update_base_settings' ),
			),
		);
	}

	public function update_base_settings( $payload_data ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		do_action( 'academy/admin/before_save_settings', $payload_data, 'base' );
		$payload = Sanitizer::sanitize_payload([
			'is_enabled_academy_web_font' => 'boolean',
			'is_enabled_academy_login' => 'boolean',
			'primary_color' => 'string',
			'secondary_color' => 'string',
			'text_color' => 'string',
			'gray_color' => 'string',
			'border_color' => 'string',
			'course_page' => 'integer',
			'is_enabled_course_review' => 'boolean',
			'is_enabled_course_share' => 'boolean',
			'is_enabled_course_wishlist' => 'boolean',
			'course_archive_courses_per_page' => 'integer',
			'course_archive_courses_per_row' => 'array',
			'course_archive_filters' => 'json',
			'course_archive_sidebar_position' => 'string',
			'course_archive_courses_order' => 'string',
			'course_card_style' => 'string',
			'is_enabled_course_single_enroll_count' => 'boolean',
			'is_opened_course_single_first_topic' => 'boolean',
			// Course Certificate
			'academy_primary_certificate_id' => 'integer',
			// dashboard
			'is_enable_apply_instructor_menu' => 'boolean',
			// Lesson
			'lessons_page' => 'integer',
			'is_enabled_lessons_php_render' => 'boolean',
			'lessons_topbar_logo' => 'string',
			'is_enabled_lessons_theme_header_footer' => 'boolean',
			'is_enabled_lessons_content_title' => 'boolean',
			'lessons_topic_length' => 'integer',
			'is_disabled_lessons_right_click' => 'boolean',
			'is_enabled_academy_player' => 'boolean',
			'auto_load_next_lesson' => 'boolean',
			'auto_complete_topic' => 'boolean',
			'frontend_dashboard_page' => 'integer',
			'frontend_instructor_reg_page' => 'integer',
			'is_show_public_profile' => 'boolean',
			'is_instructor_can_publish_course' => 'boolean',
			'is_instructor_update_course_price' => 'boolean',
			'is_enabled_instructor_review' => 'boolean',
			'frontend_student_reg_page' => 'string',
			'is_student_can_upload_files' => 'boolean',
			'password_reset_page' => 'integer',
			'tutor_booking_page' => 'integer',
			// eCommerce
			'monetization_engine' => 'string',
			'is_enabled_earning' => 'boolean',
			'admin_commission_percentage' => 'string',
			'instructor_commission_percentage' => 'string',
			// WooCommerce
			'hide_course_product_from_shop_page' => 'boolean',
			'woo_force_login_before_enroll' => 'boolean',
			'woo_order_auto_complete' => 'boolean',
			'store_link_inside_frontend_dashboard' => 'boolean',
			'store_link_label_inside_frontend_dashboard' => 'string',
			'is_enabled_fd_link_inside_woo_dashboard' => 'boolean',
			'woo_dashboard_fd_link_label' => 'string',
			'is_enabled_fd_link_inside_woo_order_page' => 'boolean',
			'woo_order_page_fd_link_label' => 'string',
			// Withdrawal
			'instructor_minimum_withdraw_amount' => 'integer',
			'is_enabled_instructor_paypal_withdraw' => 'boolean',
			'is_enabled_instructor_echeck_withdraw' => 'boolean',
			'is_enabled_instructor_bank_withdraw' => 'boolean',
			'instructor_bank_withdraw_instruction' => 'string',
			// fee
			'is_enabled_fee_deduction' => 'boolean',
			'fee_deduction_name' => 'string',
			'fee_deduction_amount' => 'integer',
			'fee_deduction_type' => 'string',
		], $payload_data );

		$default = BaseSettings::get_default_data();
		$is_update = BaseSettings::save_settings( [
			'is_enabled_academy_web_font' => $payload['is_enabled_academy_web_font'] ?? $default['is_enabled_academy_web_font'],
			'is_enabled_academy_login' => $payload['is_enabled_academy_login'] ?? $default['is_enabled_academy_login'],
			'primary_color' => $payload['primary_color'] ?? $default['primary_color'],
			'secondary_color' => $payload['secondary_color'] ?? $default['secondary_color'],
			'text_color' => $payload['text_color'] ?? $default['text_color'],
			'gray_color' => $payload['gray_color'] ?? $default['gray_color'],
			'border_color' => $payload['border_color'] ?? $default['border_color'],
			'course_page' => $payload['course_page'] ?? $default['course_page'],
			'is_enabled_course_review' => $payload['is_enabled_course_review'] ?? $default['is_enabled_course_review'],
			'is_enabled_course_share' => $payload['is_enabled_course_share'] ?? $default['is_enabled_course_share'],
			'is_enabled_course_wishlist' => $payload['is_enabled_course_wishlist'] ?? $default['is_enabled_course_wishlist'],
			'course_archive_courses_per_page' => $payload['course_archive_courses_per_page'] ?? $default['course_archive_courses_per_page'],
			'course_archive_courses_per_row' => $payload['course_archive_courses_per_row'] ?? $default['course_archive_courses_per_row'],
			'course_archive_filters' => $payload['course_archive_filters'] ?? $default['course_archive_filters'],
			'course_archive_sidebar_position' => $payload['course_archive_sidebar_position'] ?? $default['course_archive_sidebar_position'],
			'course_archive_courses_order' => $payload['course_archive_courses_order'] ?? $default['course_archive_courses_order'],
			'course_card_style' => $payload['course_card_style'] ?? $default['course_card_style'],
			'is_enabled_course_single_enroll_count' => $payload['is_enabled_course_single_enroll_count'] ?? $default['is_enabled_course_single_enroll_count'],
			'is_opened_course_single_first_topic' => $payload['is_opened_course_single_first_topic'] ?? $default['is_opened_course_single_first_topic'],
			// Course Certificate
			'academy_primary_certificate_id' => $payload['academy_primary_certificate_id'] ?? $default['academy_primary_certificate_id'],
			// Dashboard
			'is_enable_apply_instructor_menu' => $payload['is_enable_apply_instructor_menu'] ?? $default['is_enable_apply_instructor_menu'],
			// Lessons
			'lessons_page' => $payload['lessons_page'] ?? $default['lessons_page'],
			'is_enabled_lessons_php_render' => $payload['is_enabled_lessons_php_render'] ?? $default['is_enabled_lessons_php_render'],
			'lessons_topbar_logo' => $payload['lessons_topbar_logo'] ?? $default['lessons_topbar_logo'],
			'is_enabled_lessons_theme_header_footer' => $payload['is_enabled_lessons_theme_header_footer'] ?? $default['is_enabled_lessons_theme_header_footer'],
			'is_enabled_lessons_content_title' => $payload['is_enabled_lessons_content_title'] ?? $default['is_enabled_lessons_content_title'],
			'lessons_topic_length' => $payload['lessons_topic_length'] ?? $default['lessons_topic_length'],
			'is_disabled_lessons_right_click' => $payload['is_disabled_lessons_right_click'] ?? $default['is_disabled_lessons_right_click'],
			'is_enabled_academy_player' => $payload['is_enabled_academy_player'] ?? $default['is_enabled_academy_player'],
			'frontend_dashboard_page' => $payload['frontend_dashboard_page'] ?? $default['frontend_dashboard_page'],
			'frontend_instructor_reg_page' => $payload['frontend_instructor_reg_page'] ?? $default['frontend_instructor_reg_page'],
			'is_show_public_profile' => $payload['is_show_public_profile'] ?? $default['is_show_public_profile'],
			'is_instructor_can_publish_course' => $payload['is_instructor_can_publish_course'] ?? $default['is_instructor_can_publish_course'],
			'is_instructor_update_course_price' => $payload['is_instructor_update_course_price'] ?? $default['is_instructor_update_course_price'],
			'is_enabled_instructor_review' => $payload['is_enabled_instructor_review'] ?? $default['is_enabled_instructor_review'],
			'frontend_student_reg_page' => $payload['frontend_student_reg_page'] ?? $default['frontend_student_reg_page'],
			'is_student_can_upload_files' => $payload['is_student_can_upload_files'] ?? $default['is_student_can_upload_files'],
			'password_reset_page' => $payload['password_reset_page'] ?? $default['password_reset_page'],
			'tutor_booking_page' => $payload['tutor_booking_page'] ?? $default['tutor_booking_page'],
			// eCommerce
			'monetization_engine' => $payload['monetization_engine'] ?? $default['monetization_engine'],
			// WooCommerce
			'hide_course_product_from_shop_page' => $payload['hide_course_product_from_shop_page'] ?? $default['hide_course_product_from_shop_page'],
			'woo_force_login_before_enroll' => $payload['woo_force_login_before_enroll'] ?? $default['woo_force_login_before_enroll'],
			'woo_order_auto_complete' => $payload['woo_order_auto_complete'] ?? $default['woo_order_auto_complete'],
			'store_link_inside_frontend_dashboard' => $payload['store_link_inside_frontend_dashboard'] ?? $default['store_link_inside_frontend_dashboard'],
			'store_link_label_inside_frontend_dashboard' => $payload['store_link_label_inside_frontend_dashboard'] ?? $default['store_link_label_inside_frontend_dashboard'],
			'is_enabled_fd_link_inside_woo_dashboard' => $payload['is_enabled_fd_link_inside_woo_dashboard'] ?? $default['is_enabled_fd_link_inside_woo_dashboard'],
			'woo_dashboard_fd_link_label' => $payload['woo_dashboard_fd_link_label'] ?? $default['woo_dashboard_fd_link_label'],
			'is_enabled_fd_link_inside_woo_order_page' => $payload['is_enabled_fd_link_inside_woo_order_page'] ?? $default['is_enabled_fd_link_inside_woo_order_page'],
			'woo_order_page_fd_link_label' => $payload['woo_order_page_fd_link_label'] ?? $default['woo_order_page_fd_link_label'],
			// earning
			'is_enabled_earning' => $payload['is_enabled_earning'] ?? $default['is_enabled_earning'],
			'admin_commission_percentage' => $payload['admin_commission_percentage'] ?? $default['admin_commission_percentage'],
			'instructor_commission_percentage' => $payload['instructor_commission_percentage'] ?? $default['instructor_commission_percentage'],
			'is_enabled_fee_deduction' => $payload['is_enabled_fee_deduction'] ?? $default['is_enabled_fee_deduction'],
			'fee_deduction_name' => $payload['fee_deduction_name'] ?? $default['fee_deduction_name'],
			'fee_deduction_amount' => $payload['fee_deduction_amount'] ?? $default['fee_deduction_amount'],
			'fee_deduction_type' => $payload['fee_deduction_type'] ?? $default['fee_deduction_type'],
			// Withdrawal
			'instructor_minimum_withdraw_amount' => $payload['instructor_minimum_withdraw_amount'] ?? $default['instructor_minimum_withdraw_amount'],
			'is_enabled_instructor_paypal_withdraw' => $payload['is_enabled_instructor_paypal_withdraw'] ?? $default['is_enabled_instructor_paypal_withdraw'],
			'is_enabled_instructor_echeck_withdraw' => $payload['is_enabled_instructor_echeck_withdraw'] ?? $default['is_enabled_instructor_echeck_withdraw'],
			'is_enabled_instructor_bank_withdraw' => $payload['is_enabled_instructor_bank_withdraw'] ?? $default['is_enabled_instructor_bank_withdraw'],
			'instructor_bank_withdraw_instruction' => $payload['instructor_bank_withdraw_instruction'] ?? $default['instructor_bank_withdraw_instruction'],
		]);
		do_action( 'academy/admin/after_save_settings', $is_update, 'base', $payload_data );
		wp_send_json_success( $is_update );
	}

}
