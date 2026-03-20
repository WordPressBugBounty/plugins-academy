<?php
namespace  Academy\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AcademyLogin {

	public function __construct() {
		add_shortcode( 'academy_login_form', array( $this, 'login_form' ) );
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
			'login_redirect_url'        => esc_url( get_the_permalink() ),
			'logout_redirect_url'       => esc_url( home_url( '/' ) ),
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
}


