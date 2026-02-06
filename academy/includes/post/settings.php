<?php
namespace  Academy\Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Helper;
use Academy\Classes\Sanitizer;
use Academy\Classes\AbstractPostHandler;

class Settings extends AbstractPostHandler {
	public function __construct() {
		$this->actions = array(
			'save_frontend_dashboard_edit_profile_settings' => array(
				'callback' => array( $this, 'save_frontend_dashboard_edit_profile_settings' ),
				'capability' => 'read'
			),
			'save_frontend_dashboard_reset_password' => array(
				'callback' => array( $this, 'save_frontend_dashboard_reset_password' ),
				'capability' => 'read'
			),
		);
	}

	public function save_frontend_dashboard_edit_profile_settings( $form_data ) {
		$payload = Sanitizer::sanitize_payload( [
			'first_name'                  => 'string',
			'last_name'                   => 'string',
			'academy_profile_designation' => 'string',
			'academy_phone_number'        => 'string',
			'academy_profile_bio'         => 'post',
			'academy_website_url'         => 'url',
			'academy_github_url'          => 'url',
			'academy_facebook_url'        => 'url',
			'academy_twitter_url'         => 'url',
			'academy_linkedin_url'        => 'url',
			'academy-cover-photo-url'     => 'url',
			'academy-profile-photo-url'   => 'url',
			'academy_select-field'        => 'string',
			'academy_range-field'         => 'string',
			'academy_checkbox-field'      => 'string',
			'academy_radio-field'         => 'string',
			'academy_textarea-field'      => 'string',
			'academy_number-field'        => 'string',
			'academy_time-field'          => 'string',
			'academy_text-field'          => 'string',
			'academy_date-field'          => 'string',
		], $form_data );

		$user_id = get_current_user_id();
		foreach ( $payload as $key => $value ) {
			if ( ! isset( $form_data[ $key ] ) || empty( $value ) ) {
				continue;
			}
			update_user_meta( $user_id, $key, $value );
		}
		update_user_meta( $user_id, 'academy_cover_photo', $payload['academy-cover-photo-url'] );
		update_user_meta( $user_id, 'academy_profile_photo', $payload['academy-profile-photo-url'] );

		$referer_url = Helper::sanitize_referer_url( wp_get_referer() );
		wp_safe_redirect( $referer_url );
	}

	public function save_frontend_dashboard_reset_password() {
		if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'academy_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'academy' ) );
		}

		if (
			empty( $_POST['current_password'] ) ||
			empty( $_POST['new_password'] ) ||
			empty( $_POST['confirm_new_password'] )
		) {
			wp_die( esc_html__( 'All password fields are required.', 'academy' ) );
		}

		$current_user = wp_get_current_user();

		if ( ! $current_user || ! $current_user->ID ) {
			wp_die( esc_html__( 'Invalid user.', 'academy' ) );
		}

		$current_password     = sanitize_text_field( wp_unslash( $_POST['current_password'] ) );
		$new_password         = sanitize_text_field( wp_unslash( $_POST['new_password'] ) );
		$confirm_new_password = sanitize_text_field( wp_unslash( $_POST['confirm_new_password'] ) );

		if ( ! wp_check_password( $current_password, $current_user->user_pass, $current_user->ID ) ) {
			wp_die( esc_html__( 'Current password is incorrect.', 'academy' ) );
		}

		// Match new passwords
		if ( $new_password !== $confirm_new_password ) {
			wp_die( esc_html__( 'New password and confirm password do not match.', 'academy' ) );
		}

		// Change password (WordPress native & secure)
		reset_password( $current_user, $new_password );

		// Send confirmation email
		wp_mail(
			$current_user->user_email,
			esc_html__( 'Your password has been changed', 'academy' ),
			sprintf(
				/* translators: %s: user display name */
				esc_html__(
					"Hello %1\$s,\n\nYour account password has been successfully changed.\n\nIf you did not perform this action, please contact support immediately.\n\n%2\$s",
					'academy'
				),
				$current_user->display_name,
				site_url()
			)
		);
		wp_signon([
			'user_login'     => $current_user->user_login,
			'user_password'  => $new_password,
			'remember'       => false
		], false);
		// Safe redirect back
		$referer_url = Helper::sanitize_referer_url( wp_get_referer() );
		wp_safe_redirect( $referer_url );
		exit;
	}

}
