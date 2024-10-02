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

	public function save_frontend_dashboard_edit_profile_settings() {
		$payload = Sanitizer::sanitize_payload([
			'first_name' => 'string',
			'last_name' => 'string',
			'academy_profile_designation' => 'string',
			'academy_phone_number' => 'string',
			'academy_profile_bio' => 'post',
			'academy_website_url' => 'url',
			'academy_github_url' => 'url',
			'academy_facebook_url' => 'url',
			'academy_twitter_url' => 'url',
			'academy_linkedin_url' => 'url',
			'academy-cover-photo-url' => 'url',
			'academy-profile-photo-url' => 'url',
		], $_POST); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$user_id = get_current_user_id();

		$first_name = isset( $payload['first_name'] ) ? $payload['first_name'] : get_user_meta( $user_id, 'first_name', true );
		$last_name = isset( $payload['last_name'] ) ? $payload['last_name'] : get_user_meta( $user_id, 'last_name', true );
		$designation = isset( $payload['academy_profile_designation'] ) ? $payload['academy_profile_designation'] : get_user_meta( $user_id, 'designation', true );
		$phone_number = isset( $payload['academy_phone_number'] ) ? $payload['academy_phone_number'] : get_user_meta( $user_id, 'phone_number', true );
		$bio = isset( $payload['academy_profile_bio'] ) ? $payload['academy_profile_bio'] : get_user_meta( $user_id, 'bio', true );
		$website_url = isset( $payload['academy_website_url'] ) ? $payload['academy_website_url'] : get_user_meta( $user_id, 'website_url', true );
		$github_url = isset( $payload['academy_github_url'] ) ? $payload['academy_github_url'] : get_user_meta( $user_id, 'github_url', true );
		$facebook_url = isset( $payload['academy_facebook_url'] ) ? $payload['academy_facebook_url'] : get_user_meta( $user_id, 'facebook_url', true );
		$twitter_url = isset( $payload['academy_twitter_url'] ) ? $payload['academy_twitter_url'] : get_user_meta( $user_id, 'twitter_url', true );
		$linkedin_url = isset( $payload['academy_linkedin_url'] ) ? $payload['academy_linkedin_url'] : get_user_meta( $user_id, 'linkedin_url', true );
		$cover_photo_url = ! empty( $payload['academy-cover-photo-url'] ) ? $payload['academy-cover-photo-url'] : get_user_meta( $user_id, 'academy_cover_photo', true );
		$profile_photo_url = ! empty( $payload['academy-profile-photo-url'] ) ? $payload['academy-profile-photo-url'] : get_user_meta( $user_id, 'academy_profile_photo', true );

		update_user_meta( $user_id, 'first_name', $first_name );
		update_user_meta( $user_id, 'last_name', $last_name );
		update_user_meta( $user_id, 'academy_profile_designation', $designation );
		update_user_meta( $user_id, 'academy_phone_number', $phone_number );
		update_user_meta( $user_id, 'academy_profile_bio', $bio );
		update_user_meta( $user_id, 'academy_website_url', $website_url );
		update_user_meta( $user_id, 'academy_github_url', $github_url );
		update_user_meta( $user_id, 'academy_facebook_url', $facebook_url );
		update_user_meta( $user_id, 'academy_twitter_url', $twitter_url );
		update_user_meta( $user_id, 'academy_linkedin_url', $linkedin_url );
		update_user_meta( $user_id, 'academy_cover_photo', $cover_photo_url );
		update_user_meta( $user_id, 'academy_profile_photo', $profile_photo_url );

		$referer_url = Helper::sanitize_referer_url( wp_get_referer() );
		wp_safe_redirect( $referer_url );
	}

	public function save_frontend_dashboard_reset_password() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['current_password'] ) && isset( $_POST['new_password'] ) && isset( $_POST['confirm_new_password'] ) ) {
						$current_user = wp_get_current_user(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
						$current_password = sanitize_text_field( $_POST['current_password'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
						$new_password = sanitize_text_field( $_POST['new_password'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
						$confirm_new_password = sanitize_text_field( $_POST['confirm_new_password'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( wp_check_password( $current_password, $current_user->user_pass, $current_user->ID ) ) {
				if ( $new_password === $confirm_new_password ) {
					wp_set_password( $new_password, $current_user->ID );

					wp_signon([
						'user_login'     => $current_user->user_login,
						'user_password'  => $new_password,
						'remember'       => false
					], false);

					$referer_url = Helper::sanitize_referer_url( wp_get_referer() );
					wp_safe_redirect( $referer_url );
				} else { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					wp_die( esc_html__( 'New password and confirm password did not matched.', 'academy' ) );
				}
			} else { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					wp_die( esc_html__( 'Current password is incorrect.', 'academy' ) );
			}//end if
		}//end if
	}
}
