<?php
namespace AcademyGumletVideo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Admin\Settings\Base as BaseSettings;

class Bootstrap {

	public static function init(): void {
		add_filter( 'academy/admin/settings/sanitize_payload', [ __CLASS__, 'add_sanitize_rules' ] );
		add_filter( 'academy/admin/settings/save', [ __CLASS__, 'save_fields' ], 10, 3 );
		add_filter( 'academy/api/settings/get_settings', [ __CLASS__, 'mask_secret_in_response' ] );
	}

	public static function add_sanitize_rules( array $rules ): array {
		$rules['gumlet_token_secret']      = 'string';
		$rules['gumlet_collection_id']     = 'string';
		$rules['gumlet_token_expiry']      = 'integer';
		$rules['gumlet_signed_url_enabled'] = 'boolean';
		return $rules;
	}

	public static function save_fields( array $data, array $payload, array $default ): array {
		$existing = BaseSettings::get_saved_data();

		$data['gumlet_collection_id']     = sanitize_text_field( $payload['gumlet_collection_id'] ?? $default['gumlet_collection_id'] );
		$data['gumlet_token_expiry']      = absint( $payload['gumlet_token_expiry'] ?? $default['gumlet_token_expiry'] );
		$data['gumlet_signed_url_enabled'] = $payload['gumlet_signed_url_enabled'] ?? $default['gumlet_signed_url_enabled'];

		$new_secret = trim( $payload['gumlet_token_secret'] ?? '' );
		if ( ! empty( $new_secret ) ) {
			$data['gumlet_token_secret'] = Crypto::encrypt( $new_secret );
		} else {
			$data['gumlet_token_secret'] = $existing['gumlet_token_secret'] ?? '';
		}

		return $data;
	}

	public static function mask_secret_in_response( array $settings ): array {
		$settings = array_merge( [
			'gumlet_collection_id'     => '',
			'gumlet_token_expiry'      => 3600,
			'gumlet_signed_url_enabled'=> false,
		], $settings );

		$settings['gumlet_token_secret_is_set'] = ! empty( $settings['gumlet_token_secret'] ?? '' );
		unset( $settings['gumlet_token_secret'] );
		return $settings;
	}
}
