<?php
namespace Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Frontend {

	public static function init() {
		$self = new self();
		Frontend\Comments::init();
		Frontend\Template::init();
		$self->dispatch_hooks();
	}

	public function dispatch_hooks() {
		add_filter( 'the_content', array( $this, 'assign_shortcode_to_page_content' ) );
		add_action( 'wp_footer', array( $this, 'add_react_modal_div' ) );
		add_action( 'init', array( $this, 'disable_admin_topbar_for_student_role' ) );
	}
	public function disable_admin_topbar_for_student_role() {
		$user = wp_get_current_user();
		if ( $user && in_array( 'academy_student', (array) $user->roles, true ) ) {
			add_filter( 'show_admin_bar', '__return_false' );
		}
	}
	public function assign_shortcode_to_page_content( $content ) {
		// if content have any data then render that content
		if ( ! empty( $content ) ) {
			return $content;
		}

		// Dashboard Page
		$user_dashboard_page_ID = (int) Helper::get_settings( 'frontend_dashboard_page' );
		$student_reg_page_ID    = (int) Helper::get_settings( 'frontend_student_reg_page' );
		$instructor_reg_page_ID = (int) Helper::get_settings( 'frontend_instructor_reg_page' );
		$password_reset_page_ID = (int) Helper::get_settings( 'password_reset_page' );

		if ( get_the_ID() === $user_dashboard_page_ID ) {
			return '[academy_dashboard]';
		} elseif ( get_the_ID() === $student_reg_page_ID ) {
			return '[academy_student_registration_form]';
		} elseif ( get_the_ID() === $instructor_reg_page_ID ) {
			return '[academy_instructor_registration_form]';
		} elseif ( get_the_ID() === $password_reset_page_ID ) {
			return '[academy_password_reset_form]';
		}
		return $content;
	}
	public function add_react_modal_div() {
		echo '<div id="academyFrontendModalWrap"></div>';
	}

}
