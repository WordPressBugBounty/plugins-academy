<?php
namespace  Academy\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AcademyDashboard {

	public function __construct() {
		add_shortcode( 'academy_dashboard', array( $this, 'frontend_dashboard' ) );
	}
	public function frontend_dashboard() {
		ob_start();
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo do_action( 'academy/shortcode/before_academy_dashboard' );

		if ( ! is_user_logged_in() ) {
			if ( ! \Academy\Helper::get_settings( 'is_enabled_academy_login' ) ) {
				wp_safe_redirect( wp_login_url( get_the_permalink() ) );
				exit;
			}
			echo do_shortcode( '[academy_login_form form_title="' . esc_html__( 'Sign in to Access Your Dashboard', 'academy' ) . '" show_logged_in_message="false"]' );
		} else {
			$instructor_status = get_user_meta( get_current_user_id(), 'academy_instructor_status', true );
			if ( 'pending' === $instructor_status ) {
				echo '<p class="academy-instructor-pending-status-message">' . esc_html__( 'Please wait for admin\'s to approve you as an instructor.', 'academy' ) . '</p>';
			}
			\Academy\Helper::get_template( 'shortcode/frontend-dashboard.php' );
		}
		return apply_filters( 'academy/templates/shortcode/dashboard', ob_get_clean() );
	}
}
