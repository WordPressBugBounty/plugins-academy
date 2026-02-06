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

		$self->register_hooks();
	}

	private function register_hooks() {
		add_filter( 'the_content', [ $this, 'assign_shortcode_to_page_content' ], 9 );
		add_action( 'wp_footer', [ $this, 'add_react_modal_div' ] );
		add_action( 'init', [ $this, 'disable_admin_topbar_for_student_role' ] );

		// Reset password form template
		if ( ! is_user_logged_in() ) {
			add_action( 'template_redirect', [ $this, 'validate_reset_password_request' ] );
			add_filter( 'template_include', [ $this, 'load_reset_password_template' ] );
		}
		// course coming soon
		if ( \Academy\Helper::get_settings( 'is_enabled_course_coming_soon' ) ) {
			add_filter( 'academy/templates/single_course/enroll_form', array( $this, 'get_course_coming_soon_content' ), 15, 2 );
			add_filter( 'academy/assets/frontend_scripts_data', array( $this, 'add_course_coming_soon_time' ) );
			add_filter( 'academy/template/loop/footer_form', array( $this, 'get_course_type' ), 15, 2 );
			add_filter( 'academy/template/loop/price_args', array( $this, 'get_course_type' ), 15, 2 );
		}
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

	/**
	 * Validate reset link (NO rendering here)
	 */
	public function validate_reset_password_request() {

		if ( ! get_query_var( 'academy_retrieve_password' ) ) {
			return;
		}

		if ( empty( $_GET['reset_key'] ) || empty( $_GET['login'] ) ) {
			wp_die( esc_html__( 'Invalid or expired reset link.', 'academy' ) );
		}

		$reset_key = sanitize_text_field( wp_unslash( $_GET['reset_key'] ) );
		$login = sanitize_text_field( wp_unslash( $_GET['login'] ) );

		$user = get_user_by( 'login', $login );

		if ( ! $user ) {
			wp_die( esc_html__( 'Invalid reset request.', 'academy' ) );
		}

		$validated_user = check_password_reset_key( $reset_key, $user->user_login );

		if ( is_wp_error( $validated_user ) ) {
			wp_die(
				esc_html__( 'This reset link is invalid or has already been used.', 'academy' )
			);
		}

		set_query_var( 'academy_valid_reset', true );
	}

	public function load_reset_password_template( $template ) {

		if (
			get_query_var( 'academy_retrieve_password' ) &&
			get_query_var( 'academy_valid_reset' )
		) {
			return \Academy\Helper::get_template( 'reset-password.php' );
		}

		return $template;
	}

	public function get_course_coming_soon_content( $html, $course_id ) {
		$end_at = get_post_meta(
			$course_id,
			'academy_course_coming_soon_end_date',
			true
		);

		$datetime  = new \DateTime( $end_at, wp_timezone() );
		$end_timestamp = $datetime->getTimestamp();

		if ( $end_timestamp <= current_time( 'timestamp' ) ) {
			return $html;
		}
		ob_start();

		\Academy\Helper::get_template( 'single-course/coming-soon.php', [ 'course_id' => $course_id ] );

		return ob_get_clean();
	}

	public function add_course_coming_soon_time( $data ) {
		$course_id = get_the_ID();
		$end_at = get_post_meta(
			$course_id,
			'academy_course_coming_soon_end_date',
			true
		);

		$datetime  = new \DateTime( $end_at, wp_timezone() );
		$end_timestamp = $datetime->getTimestamp();

		if ( $end_timestamp > current_time( 'timestamp' ) ) {
			$data['academy_course_coming_soon_time'] = $end_timestamp;
		}

		return $data;
	}

	public function get_course_type( $args, $course_id ) {
		$end_at = get_post_meta( $course_id, 'academy_course_coming_soon_end_date', true );
		$datetime  = new \DateTime( $end_at, wp_timezone() );
		$end_timestamp = $datetime->getTimestamp();
		if ( $end_timestamp > current_time( 'timestamp' ) ) {
			$args['course_type'] = 'coming-soon';
		}

		return $args;
	}
}
