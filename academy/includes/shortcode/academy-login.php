<?php
namespace  Academy\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AcademyLogin {

	public function __construct() {
		add_shortcode( 'academy_login_form', array( $this, 'login_form' ) );
		add_action( 'wp_ajax_nopriv_academy/shortcode/login_form_handler', array( $this, 'login_form_handler' ) );
	}

	public function login_form( $atts ) {
		$attributes = shortcode_atts(array(
			'form_title'                => esc_html__( 'Log In into your Account', 'academy' ),
			'username_label'            => esc_html__( 'Username or Email Address', 'academy' ),
			'username_placeholder'      => esc_html__( 'Username or Email Address', 'academy' ),
			'password_label'            => esc_html__( 'Password', 'academy' ),
			'password_placeholder'      => esc_html__( 'Password', 'academy' ),
			'remember_label'            => esc_html__( 'Remember me', 'academy' ),
			'login_button_label'        => esc_html__( 'Log In', 'academy' ),
			'reset_password_label'      => esc_html__( 'Reset password', 'academy' ),
			'show_logged_in_message'    => true,
			'student_register_url'      => '',
			'login_redirect_url'        => '',
			'logout_redirect_url'       => '',
		), $atts);

		ob_start();
		if ( apply_filters( 'academy/shortcode/login_form_is_user_logged_in', is_user_logged_in() ) ) {
			$show_logged_in_message = filter_var( $attributes['show_logged_in_message'], FILTER_VALIDATE_BOOLEAN );
			if ( $show_logged_in_message ) {
				$referer_url = \Academy\Helper::sanitize_referer_url( wp_get_referer() );
				$logout_redirect_url = ! empty( $attributes['logout_redirect_url'] ) ? sanitize_text_field( $attributes['logout_redirect_url'] ) : get_the_permalink();
				$current_user = wp_get_current_user();
				$user_name   = $current_user->display_name;
				$a_tag       = '<a href="' . esc_url( wp_logout_url( $logout_redirect_url ? $logout_redirect_url : $referer_url ) ) . '">';
				$close_a_tag = '</a>';
				\Academy\Helper::get_template(
					'shortcode/logged-in-user.php',
					array(
						'user_name' => $user_name,
						'a_tag'  => $a_tag,
						'close_a_tag'  => $close_a_tag,
					)
				);
			}
		} else {
			\Academy\Helper::get_template(
				'shortcode/login.php',
				$attributes
			);
		}//end if
		return apply_filters( 'academy/templates/shortcode/login', ob_get_clean() );
	}

	public function login_form_handler() {
		if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'academy_login_nonce' ) ) {
			wp_clear_auth_cookie();

			$username = sanitize_text_field( $_POST['username'] );
			$password = sanitize_text_field( $_POST['password'] );
			$remember = (bool) isset( $_POST['remember'] ) ? sanitize_text_field( $_POST['remember'] ) : false;
			$login_redirect_url = ( isset( $_POST['login_redirect_url'] ) ? sanitize_text_field( $_POST['login_redirect_url'] ) : '' );

			do_action( 'academy/shortcode/before_login_signon' );
			$secure_cookie = is_ssl();
			$user_signon = wp_signon( array(
				'user_login' => $username,
				'user_password' => $password,
				'remember' => $remember,
			), $secure_cookie );

			if ( is_wp_error( $user_signon ) ) {
				wp_send_json_error( [ $user_signon->get_error_message() ] );
			}

			wp_set_current_user( $user_signon->ID );

			do_action( 'set_current_user' );

			$redirect_url = ! empty( $login_redirect_url ) ? $login_redirect_url : \Academy\Helper::get_page_permalink( 'frontend_dashboard_page' );

			wp_send_json_success([
				'message' => __( 'You have logged in successfully. Redirecting...', 'academy' ),
				'redirect_url' => esc_url( $redirect_url )
			]);
		}//end if
		wp_die( esc_html__( 'Security check', 'academy' ) );
	}
}


