<?php
namespace Academy\Ajax;

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

	/**
	 * Settings that moved to the Design screen.
	 *
	 * @return string[]
	 */
	public static function moved_to_design() {
		return array_merge(
			\Academy\Design\Palette::keys(),
			[
				'course_archive_courses_per_row',
				'is_show_course_excerpt',
				'course_archive_sidebar_position',
				'course_card_style',
				'is_enabled_course_single_enroll_count',
			]
		);
	}

	public function update_base_settings( $payload_data ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		do_action( 'academy/admin/before_save_settings', $payload_data, 'base' );
		$payload = Sanitizer::sanitize_payload( apply_filters( 'academy/admin/settings/sanitize_payload', [
			'is_enabled_academy_login' => 'boolean',
			'primary_color' => 'string',
			'secondary_color' => 'string',
			'text_color' => 'string',
			'gray_color' => 'string',
			'border_color' => 'string',
			'surface_color' => 'string',
			'dark_primary_color' => 'string',
			'dark_secondary_color' => 'string',
			'dark_text_color' => 'string',
			'dark_border_color' => 'string',
			'dark_gray_color' => 'string',
			'dark_surface_color' => 'string',
			'course_page' => 'integer',
			'is_enabled_course_review' => 'boolean',
			'is_enabled_course_popup_review' => 'boolean',
			'is_enabled_course_share' => 'boolean',
			'is_enabled_course_wishlist' => 'boolean',
			'course_archive_courses_per_page' => 'integer',
			'course_archive_courses_per_row' => 'array',
			'course_archive_filters' => 'json',
			'course_archive_sidebar_position' => 'string',
			'course_archive_courses_order' => 'string',
			'course_archive_category_filter_dropdown' => 'boolean',
			'course_card_style' => 'string',
			'is_enabled_course_single_enroll_count' => 'boolean',
			'is_opened_course_single_first_topic' => 'boolean',
			'minimum_course_completion_on_review' => 'integer',
			'is_enabled_course_coming_soon' => 'boolean',
			'is_show_course_excerpt' => 'boolean',
			'is_enable_course_review_edit' => 'boolean',
			// Course Certificate
			'academy_primary_certificate_id' => 'integer',
			// dashboard
			'is_enable_apply_instructor_menu' => 'boolean',
			'academy_frontend_dashboard_redirect_login_page' => 'string',
			'academy_frontend_dashboard_redirect_login_url' => 'url',
			// Lesson
			'lesson_content_width' => 'integer',
			'lesson_content_width_unit' => 'string',
			'lessons_page' => 'integer',
			'is_enabled_lessons_php_render' => 'boolean',
			'lesson_self_hosted_video_autoplay' => 'boolean',
			'is_enabled_lessons_theme_header_footer' => 'boolean',
			'is_enabled_lessons_content_title' => 'boolean',
			'lessons_topic_length' => 'integer',
			'is_disabled_lessons_right_click' => 'boolean',
			'is_enabled_academy_lessons_comment' => 'boolean',
			'is_disabled_lessons_video_skip' => 'boolean',
			'lessons_video_completion_threshold' => 'integer',
			'external_video_min_watch_seconds' => 'integer',
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
			'is_reset_academy_enrolled_course_progress' => 'boolean',
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
			'woo_order_auto_complete_status' => 'array',
			'store_link_inside_frontend_dashboard' => 'boolean',
			'store_link_label_inside_frontend_dashboard' => 'string',
			'store_force_login_before_enroll' => 'boolean',
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
			// Text Reader (TTS)
			'is_enabled_lesson_text_reader' => 'boolean',
			'lesson_tts_rate'               => 'string',
			'lesson_tts_pitch'              => 'string',
			'lesson_tts_highlight'          => 'boolean',
			'lesson_tts_auto_scroll'        => 'boolean',
			'lesson_tts_hide_text'          => 'boolean',
			// Learn Page — Layout
			'learn_page_engine'          => 'string',
			'learn_page_sidebar_width'   => 'integer',
			'learn_page_learner_theme_toggle' => 'boolean',
			'learn_page_learner_width_toggle' => 'boolean',
			'learn_page_colors'          => 'array',
			'learn_page_nav_placement'   => 'string',
			'learn_page_layout_width'    => 'string',
			'learn_page_show_side_panel' => 'boolean',
			'learn_page_sidebar_default_open' => 'boolean',
			'learn_page_sidebar_position' => 'string',
			'learn_page_content_load'    => 'string',
			'learn_page_show_content_header' => 'boolean',
			'learn_page_show_mark_complete' => 'boolean',
			'learn_page_theme_mode'      => 'string',
			'learn_page_topbar_items'    => 'json',
			'learn_page_menu_items'      => 'json',
			// Quiz Proctoring
			'quiz_proctoring_browser_lock' => 'boolean',
			// lesson migration
			'academy_is_hp_lesson_active' => 'boolean',
			// chatgpt integration
			'chatgpt_api_key'   => 'string',
			'chatgpt_model'     => 'string',
			'chatgpt_img_model' => 'string',
			'allow_instructor_to_use_chatgpt' => 'boolean',
			// after registration course enroll settings
			'enable_auto_enroll_after_registration' => 'boolean',
			'after_registration_auto_enroll_courses_id' => 'array',
			'user_roles_for_auto_enroll' => 'array',
			// Maintenance mode
			'academy_maintenance_scope' => 'string',
			'academy_maintenance_course_ids' => 'array',
			'academy_maintenance_lock_archive' => 'boolean',
			'academy_maintenance_locked_pages' => 'array',
			'academy_maintenance_message' => 'post',
			'academy_maintenance_end_time' => 'string',
		]), $payload_data );

		$default = BaseSettings::get_default_data();

		$redirect_login_url = $payload['academy_frontend_dashboard_redirect_login_url'] ?? $default['academy_frontend_dashboard_redirect_login_url'];
		$redirect_login_page = $payload['academy_frontend_dashboard_redirect_login_page'] ?? $default['academy_frontend_dashboard_redirect_login_page'];
		if ( 'custom_login' === $redirect_login_page ) {
			self::check_redirect_login_url_is_valid( $redirect_login_url );
		}

		$fields = apply_filters( 'academy/admin/settings/save', [
			'is_enabled_academy_login' => $payload['is_enabled_academy_login'] ?? $default['is_enabled_academy_login'],
			'primary_color' => $payload['primary_color'] ?? $default['primary_color'],
			'secondary_color' => $payload['secondary_color'] ?? $default['secondary_color'],
			'text_color' => $payload['text_color'] ?? $default['text_color'],
			'gray_color' => $payload['gray_color'] ?? $default['gray_color'],
			'border_color' => $payload['border_color'] ?? $default['border_color'],
			'surface_color' => $payload['surface_color'] ?? $default['surface_color'],
			'dark_primary_color' => $payload['dark_primary_color'] ?? $default['dark_primary_color'],
			'dark_secondary_color' => $payload['dark_secondary_color'] ?? $default['dark_secondary_color'],
			'dark_text_color' => $payload['dark_text_color'] ?? $default['dark_text_color'],
			'dark_border_color' => $payload['dark_border_color'] ?? $default['dark_border_color'],
			'dark_gray_color' => $payload['dark_gray_color'] ?? $default['dark_gray_color'],
			'dark_surface_color' => $payload['dark_surface_color'] ?? $default['dark_surface_color'],
			'course_page' => $payload['course_page'] ?? $default['course_page'],
			'is_enabled_course_review' => $payload['is_enabled_course_review'] ?? $default['is_enabled_course_review'],
			'is_enabled_course_popup_review' => $payload['is_enabled_course_popup_review'] ?? $default['is_enabled_course_popup_review'],
			'is_enabled_course_share' => $payload['is_enabled_course_share'] ?? $default['is_enabled_course_share'],
			'is_enabled_course_wishlist' => $payload['is_enabled_course_wishlist'] ?? $default['is_enabled_course_wishlist'],
			'course_archive_courses_per_page' => $payload['course_archive_courses_per_page'] ?? $default['course_archive_courses_per_page'],
			'course_archive_courses_per_row' => $payload['course_archive_courses_per_row'] ?? $default['course_archive_courses_per_row'],
			'course_archive_filters' => $payload['course_archive_filters'] ?? $default['course_archive_filters'],
			'course_archive_sidebar_position' => $payload['course_archive_sidebar_position'] ?? $default['course_archive_sidebar_position'],
			'course_archive_courses_order' => $payload['course_archive_courses_order'] ?? $default['course_archive_courses_order'],
			'course_archive_category_filter_dropdown' => $payload['course_archive_category_filter_dropdown'] ?? $default['course_archive_category_filter_dropdown'],
			'course_card_style' => $payload['course_card_style'] ?? $default['course_card_style'],
			'is_enabled_course_single_enroll_count' => $payload['is_enabled_course_single_enroll_count'] ?? $default['is_enabled_course_single_enroll_count'],
			'is_opened_course_single_first_topic' => $payload['is_opened_course_single_first_topic'] ?? $default['is_opened_course_single_first_topic'],
			'minimum_course_completion_on_review' => $payload['minimum_course_completion_on_review'] ?? $default['minimum_course_completion_on_review'],
			'is_enabled_course_coming_soon' => $payload['is_enabled_course_coming_soon'] ?? $default['is_enabled_course_coming_soon'],
			'is_show_course_excerpt'  => $payload['is_show_course_excerpt'] ?? $default['is_show_course_excerpt'],
			'is_enable_course_review_edit'  => $payload['is_enable_course_review_edit'] ?? $default['is_enable_course_review_edit'],
			// Course Certificate
			'academy_primary_certificate_id' => $payload['academy_primary_certificate_id'] ?? $default['academy_primary_certificate_id'],
			// Dashboard
			'is_enable_apply_instructor_menu' => $payload['is_enable_apply_instructor_menu'] ?? $default['is_enable_apply_instructor_menu'],
			'academy_frontend_dashboard_redirect_login_page' => $redirect_login_page,
			'academy_frontend_dashboard_redirect_login_url' => $redirect_login_url,
			// Lessons
			'academy_is_hp_lesson_active' => $payload['academy_is_hp_lesson_active'] ?? $default['academy_is_hp_lesson_active'],
			'lessons_page' => $payload['lessons_page'] ?? $default['lessons_page'],
			'lesson_content_width' => $payload['lesson_content_width'] ?? $default['lesson_content_width'],
			'lesson_content_width_unit' => $payload['lesson_content_width_unit'] ?? $default['lesson_content_width_unit'],
			'is_enabled_lessons_php_render' => $payload['is_enabled_lessons_php_render'] ?? $default['is_enabled_lessons_php_render'],
			'lesson_self_hosted_video_autoplay' => $payload['lesson_self_hosted_video_autoplay'] ?? $default['lesson_self_hosted_video_autoplay'],
			'is_enabled_lessons_theme_header_footer' => $payload['is_enabled_lessons_theme_header_footer'] ?? $default['is_enabled_lessons_theme_header_footer'],
			'is_enabled_lessons_content_title' => $payload['is_enabled_lessons_content_title'] ?? $default['is_enabled_lessons_content_title'],
			'lessons_topic_length' => $payload['lessons_topic_length'] ?? $default['lessons_topic_length'],
			'is_disabled_lessons_right_click' => $payload['is_disabled_lessons_right_click'] ?? $default['is_disabled_lessons_right_click'],
			'is_enabled_academy_lessons_comment' => $payload['is_enabled_academy_lessons_comment'] ?? $default['is_enabled_academy_lessons_comment'],
			'is_disabled_lessons_video_skip' => $payload['is_disabled_lessons_video_skip'] ?? $default['is_disabled_lessons_video_skip'],
			'lessons_video_completion_threshold' => $payload['lessons_video_completion_threshold'] ?? $default['lessons_video_completion_threshold'],
			'external_video_min_watch_seconds' => $payload['external_video_min_watch_seconds'] ?? $default['external_video_min_watch_seconds'],
			'frontend_dashboard_page' => $payload['frontend_dashboard_page'] ?? $default['frontend_dashboard_page'],
			'frontend_instructor_reg_page' => $payload['frontend_instructor_reg_page'] ?? $default['frontend_instructor_reg_page'],
			'is_show_public_profile' => $payload['is_show_public_profile'] ?? $default['is_show_public_profile'],
			'is_instructor_can_publish_course' => $payload['is_instructor_can_publish_course'] ?? $default['is_instructor_can_publish_course'],
			'is_instructor_update_course_price' => $payload['is_instructor_update_course_price'] ?? $default['is_instructor_update_course_price'],
			'is_enabled_instructor_review' => $payload['is_enabled_instructor_review'] ?? $default['is_enabled_instructor_review'],
			'frontend_student_reg_page' => $payload['frontend_student_reg_page'] ?? $default['frontend_student_reg_page'],
			'is_student_can_upload_files' => $payload['is_student_can_upload_files'] ?? $default['is_student_can_upload_files'],
			'is_reset_academy_enrolled_course_progress' => $payload['is_reset_academy_enrolled_course_progress'] ?? $default['is_reset_academy_enrolled_course_progress'],
			'password_reset_page' => $payload['password_reset_page'] ?? $default['password_reset_page'],
			'tutor_booking_page' => $payload['tutor_booking_page'] ?? $default['tutor_booking_page'],
			// eCommerce
			'monetization_engine' => $payload['monetization_engine'] ?? $default['monetization_engine'],
			// WooCommerce
			'hide_course_product_from_shop_page' => $payload['hide_course_product_from_shop_page'] ?? $default['hide_course_product_from_shop_page'],
			'woo_force_login_before_enroll' => $payload['woo_force_login_before_enroll'] ?? $default['woo_force_login_before_enroll'],
			'woo_order_auto_complete' => $payload['woo_order_auto_complete'] ?? $default['woo_order_auto_complete'],
			'woo_order_auto_complete_status' => $payload['woo_order_auto_complete_status'] ?? $default['woo_order_auto_complete_status'],
			'store_link_inside_frontend_dashboard' => $payload['store_link_inside_frontend_dashboard'] ?? $default['store_link_inside_frontend_dashboard'],
			'store_link_label_inside_frontend_dashboard' => $payload['store_link_label_inside_frontend_dashboard'] ?? $default['store_link_label_inside_frontend_dashboard'],
			'store_force_login_before_enroll' => $payload['store_force_login_before_enroll'] ?? $default['store_force_login_before_enroll'],
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
			// Text Reader (TTS)
			'is_enabled_lesson_text_reader' => $payload['is_enabled_lesson_text_reader'] ?? $default['is_enabled_lesson_text_reader'],
			'lesson_tts_rate'               => $payload['lesson_tts_rate'] ?? $default['lesson_tts_rate'],
			'lesson_tts_pitch'              => $payload['lesson_tts_pitch'] ?? $default['lesson_tts_pitch'],
			'lesson_tts_highlight'          => $payload['lesson_tts_highlight'] ?? $default['lesson_tts_highlight'],
			'lesson_tts_auto_scroll'        => $payload['lesson_tts_auto_scroll'] ?? $default['lesson_tts_auto_scroll'],
			'lesson_tts_hide_text'          => $payload['lesson_tts_hide_text'] ?? $default['lesson_tts_hide_text'],
			// Learn Page — Layout
			'learn_page_engine'          => $payload['learn_page_engine'] ?? $default['learn_page_engine'],
			'learn_page_sidebar_width'   => $payload['learn_page_sidebar_width'] ?? $default['learn_page_sidebar_width'],
			'learn_page_learner_theme_toggle' => $payload['learn_page_learner_theme_toggle'] ?? $default['learn_page_learner_theme_toggle'],
			'learn_page_learner_width_toggle' => $payload['learn_page_learner_width_toggle'] ?? $default['learn_page_learner_width_toggle'],
			'learn_page_colors'          => $payload['learn_page_colors'] ?? $default['learn_page_colors'],
			'learn_page_nav_placement'   => $payload['learn_page_nav_placement'] ?? $default['learn_page_nav_placement'],
			'learn_page_layout_width'    => $payload['learn_page_layout_width'] ?? $default['learn_page_layout_width'],
			'learn_page_show_side_panel' => $payload['learn_page_show_side_panel'] ?? $default['learn_page_show_side_panel'],
			'learn_page_sidebar_default_open' => $payload['learn_page_sidebar_default_open'] ?? $default['learn_page_sidebar_default_open'],
			'learn_page_sidebar_position' => $payload['learn_page_sidebar_position'] ?? $default['learn_page_sidebar_position'],
			'learn_page_content_load'    => $payload['learn_page_content_load'] ?? $default['learn_page_content_load'],
			'learn_page_show_content_header' => $payload['learn_page_show_content_header'] ?? $default['learn_page_show_content_header'],
			'learn_page_show_mark_complete' => $payload['learn_page_show_mark_complete'] ?? $default['learn_page_show_mark_complete'],
			'learn_page_theme_mode' => $payload['learn_page_theme_mode'] ?? $default['learn_page_theme_mode'],
			'learn_page_topbar_items'    => $payload['learn_page_topbar_items'] ?? $default['learn_page_topbar_items'],
			'learn_page_menu_items'      => $payload['learn_page_menu_items'] ?? $default['learn_page_menu_items'],
			// Quiz Proctoring
			'quiz_proctoring_browser_lock' => $payload['quiz_proctoring_browser_lock'] ?? $default['quiz_proctoring_browser_lock'],
			// chatgpt integration
			'chatgpt_api_key'   => $payload['chatgpt_api_key'] ?? $default['chatgpt_api_key'] ?? '',
			'chatgpt_model'     => $payload['chatgpt_model'] ?? $default['chatgpt_model'] ?? '',
			'chatgpt_img_model' => $payload['chatgpt_img_model'] ?? $default['chatgpt_img_model'] ?? '',
			'allow_instructor_to_use_chatgpt' => $payload['allow_instructor_to_use_chatgpt'] ?? $default['allow_instructor_to_use_chatgpt'] ?? false,
			// after registration course enroll settings
			'enable_auto_enroll_after_registration' => $payload['enable_auto_enroll_after_registration'] ?? $default['enable_auto_enroll_after_registration'],
			'after_registration_auto_enroll_courses_id' => $payload['after_registration_auto_enroll_courses_id'] ?? $default['after_registration_auto_enroll_courses_id'],
			'user_roles_for_auto_enroll' => $payload['user_roles_for_auto_enroll'] ?? $default['user_roles_for_auto_enroll'],
			'academy_maintenance_scope' => $payload['academy_maintenance_scope'] ?? $default['academy_maintenance_scope'],
			'academy_maintenance_course_ids' => $payload['academy_maintenance_course_ids'] ?? $default['academy_maintenance_course_ids'],
			'academy_maintenance_lock_archive' => $payload['academy_maintenance_lock_archive'] ?? $default['academy_maintenance_lock_archive'],
			'academy_maintenance_locked_pages' => $payload['academy_maintenance_locked_pages'] ?? $default['academy_maintenance_locked_pages'],
			'academy_maintenance_message' => $payload['academy_maintenance_message'] ?? $default['academy_maintenance_message'],
			'academy_maintenance_end_time' => $payload['academy_maintenance_end_time'] ?? $default['academy_maintenance_end_time'],

		], $payload, $default );

		// Colours and course display options are edited on Design now. Keep what
		// is stored for them, so an open Settings screen never writes back stale
		// values and a missing field never resets one to its default.
		$saved = BaseSettings::get_saved_data();
		foreach ( self::moved_to_design() as $moved ) {
			if ( array_key_exists( $moved, (array) $saved ) ) {
				$fields[ $moved ] = $saved[ $moved ];
			} else {
				unset( $fields[ $moved ] );
			}
		}

		// With Multi Instructor on, admin + instructor commission split each
		// sale between them, so together they must be exactly 100%.
		if ( \Academy\Helper::get_addon_active_status( 'multi_instructor' ) && ! empty( $fields['is_enabled_earning'] ) ) {
			$commission_total = (float) $fields['admin_commission_percentage'] + (float) $fields['instructor_commission_percentage'];
			if ( abs( $commission_total - 100 ) > 0.001 ) {
				wp_send_json_error( [
					'message' => __( 'Admin and Instructor Commission Percentage must add up to 100%.', 'academy' ),
				], 400 );
			}
		}

		$is_update = BaseSettings::save_settings( $fields );
		do_action( 'academy/admin/after_save_settings', $is_update, 'base', $payload_data );
		wp_send_json_success( $is_update );
	}

	public static function check_redirect_login_url_is_valid( $auth_redirect_url ) {
		if ( ! $auth_redirect_url ) {
			wp_send_json_error( [
				'message' => __( 'Login URL is required.', 'academy' )
			], 400 );
		} else {
			if ( filter_var( $auth_redirect_url, FILTER_VALIDATE_URL ) === false ) {
				wp_send_json_error( [
					'message' => __( 'Login URL is invalid.', 'academy' )
				], 400 );
			}

			if ( is_ssl() && ! str_starts_with( $auth_redirect_url, 'https://' ) ) {
				wp_send_json_error( [
					'message' => __( 'Invalid dashboard login redirect URL. Please use secure (https) URL.', 'academy' )
				], 400 );
			}

			if ( str_starts_with( $auth_redirect_url, \Academy\Helper::get_page_permalink( 'frontend_dashboard_page' ) ) ) {
				// Prevent redirect loop.
				wp_send_json_error( [
					'message' => __( 'Dashboard URL is not allowed. Please use academy as auth redirect instead.', 'academy' )
				], 400 );
			}

			if ( ! wp_validate_redirect( $auth_redirect_url ) ) {
				wp_send_json_error( [
					'message' => __( 'Login URL is not allowed.', 'academy' )
				], 400 );
			}
		}//end if
	}
}
