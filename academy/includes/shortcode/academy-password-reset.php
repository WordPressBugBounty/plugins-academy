<?php
namespace  Academy\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AcademyPasswordReset {

	public function __construct() {
		add_shortcode( 'academy_password_reset_form', array( $this, 'password_reset_form' ) );
		add_action( 'wp_ajax_nopriv_academy/shortcode/password_reset_handler', array( $this, 'password_reset_handler' ) );
	}
	public function password_reset_form( $atts ) {
		$attributes = shortcode_atts(array(
			'form_title'                => esc_html__( 'Reset Your password', 'academy' ),
			'username_label'            => esc_html__( 'Username or Email Address', 'academy' ),
			'reset_button_label'        => esc_html__( 'Get New Password', 'academy' ),
			'login_button_label'        => esc_html__( 'Back To Login', 'academy' ),
			'show_logged_in_message'    => true,
		), $atts);

		ob_start();
		if ( apply_filters( 'academy/shortcode/password_reset_form_is_user_logged_in', is_user_logged_in() ) ) {
			$show_logged_in_message = filter_var( $attributes['show_logged_in_message'], FILTER_VALIDATE_BOOLEAN );
			if ( $show_logged_in_message ) {
				$referer_url = \Academy\Helper::sanitize_referer_url( wp_get_referer() );
				$logout_redirect_url = get_the_permalink();
				$current_user = wp_get_current_user();
				$user_name   = $current_user->display_name;
				$a_tag       = '<a href="' . esc_url( wp_logout_url( $logout_redirect_url ? $logout_redirect_url : $referer_url ) ) . '">';
				$close_a_tag = '</a>';

				\Academy\Helper::get_template( 'shortcode/logged-in-user.php', array(
					'user_name' => $user_name,
					'a_tag'  => $a_tag,
					'close_a_tag'  => $close_a_tag,
				) );
			}
		} else {
			\Academy\Helper::get_template(
				'shortcode/password-reset.php',
				$attributes
			);
		}//end if
		return apply_filters( 'academy/shortcode/password-reset', ob_get_clean() );
	}
	public function password_reset_handler() {

		if ( ! isset( $_REQUEST['_wpnonce'] ) ||
			! wp_verify_nonce( $_REQUEST['_wpnonce'], 'academy_reset_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		$username = sanitize_text_field( $_POST['username'] );

		$ip = $_SERVER['REMOTE_ADDR'];
		$key = 'academy_reset_limit_' . md5( $ip . $username );

		if ( get_transient( $key ) ) {
			wp_send_json_error( __( 'Too many requests. Try later.', 'academy' ) );
		}

		set_transient( $key, 1, 60 );

		$user = get_user_by( 'login', $username );

		if ( ! $user ) {
			$user = get_user_by( 'email', $username );
		}

		// Hide user existence
		if ( ! $user ) {
			wp_send_json_success(
				__( 'If the account exists, you will receive an email.', 'academy' )
			);
		}

		do_action( 'academy/shortcode/before_password_reset', $user );

		retrieve_password( $user->user_login );

		wp_send_json_success(
			__( 'If the account exists, you will receive an email.', 'academy' )
		);
	}

}
