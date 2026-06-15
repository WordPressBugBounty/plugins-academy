<?php
namespace AcademyGumletVideo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Helper;
use RuntimeException;

class Token {

	/**
	 * Generate a signed Gumlet embed URL for a given asset.
	 *
	 * Token formula: MD5( secret + asset_id + expiry )
	 * If a collection_id is configured it is prefixed:
	 *   MD5( secret + "/" + collection_id + "/" + asset_id + expiry )
	 *
	 * @param string $asset_id  The Gumlet video asset ID.
	 * @param int    $user_id   Current user ID (bound into token for per-user restriction).
	 * @return array { signed_url: string, expiry: int, asset_id: string }
	 * @throws RuntimeException When Gumlet is not fully configured.
	 */
	public static function generate( string $asset_id, int $user_id = 0 ): array {
		$secret        = self::get_secret();
		$collection_id = Helper::get_settings( 'gumlet_collection_id', '' );
		$expiry_sec    = absint( Helper::get_settings( 'gumlet_token_expiry', 3600 ) );

		if ( empty( $secret ) ) {
			throw new RuntimeException( 'Gumlet token secret is not configured.' );
		}

		if ( $expiry_sec < 60 ) {
			$expiry_sec = 3600;
		}

		$expiry = time() + $expiry_sec;

		if ( ! empty( $collection_id ) ) {
			$sign_string = $secret . '/' . $collection_id . '/' . $asset_id . (string) $expiry;
			$embed_path  = rawurlencode( $collection_id ) . '/' . rawurlencode( $asset_id );
		} else {
			$sign_string = $secret . $asset_id . (string) $expiry;
			$embed_path  = rawurlencode( $asset_id );
		}

		if ( $user_id > 0 ) {
			$sign_string .= (string) $user_id;
		}

		$token = md5( $sign_string );

		$params = [
			'token'  => $token,
			'expiry' => $expiry,
		];

		$drm_enabled       = (bool) Helper::get_settings( 'gumlet_drm_enabled', false );
		$watermark_enabled = (bool) Helper::get_settings( 'gumlet_watermark_enabled', false );
		$watermark_text    = sanitize_text_field( Helper::get_settings( 'gumlet_watermark_text', '' ) );

		if ( $drm_enabled ) {
			$params['drm'] = 'true';
		}

		if ( $watermark_enabled ) {
			if ( ! empty( $watermark_text ) ) {
				$params['watermark_text'] = $watermark_text;
			} elseif ( $user_id > 0 ) {
				$user = get_userdata( $user_id );
				if ( $user && ! empty( $user->user_email ) ) {
					$params['watermark_text'] = $user->user_email;
				}
			}
		}

		$signed_url = 'https://play.gumlet.io/embed/' . $embed_path . '?' . http_build_query( $params );

		return [
			'signed_url' => $signed_url,
			'expiry'     => $expiry,
			'asset_id'   => $asset_id,
		];
	}

	public static function get_secret(): string {
		$raw = Helper::get_settings( 'gumlet_token_secret', '' );
		if ( empty( $raw ) ) {
			return '';
		}
		if ( Crypto::is_encrypted( $raw ) ) {
			return Crypto::decrypt( $raw );
		}
		return $raw;
	}
}
