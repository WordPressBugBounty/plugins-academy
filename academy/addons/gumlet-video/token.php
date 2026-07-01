<?php
namespace AcademyGumletVideo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Helper;
use RuntimeException;

class Token {

	/**
	 * Generate a Gumlet embed URL for a given asset.
	 *
	 * Supports two modes based on plugin settings:
	 *
	 * Mode 1 — Signed URL ENABLED (Gumlet native signed iframe):
	 *   string_to_sign = "/embed/ASSET_ID?signed_expires=EXPIRY"
	 *                 OR "/embed/ASSET_ID?other_params&signed_expires=EXPIRY"
	 *   secret         = base64_decode( raw_secret )
	 *   signed_token   = HMAC-SHA1( secret, string_to_sign )
	 *
	 * Mode 2 — Signed URL DISABLED (plain embed URL):
	 *   No token or expiry params added.
	 *
	 * @param string $asset_id  The Gumlet video asset ID.
	 * @param int    $user_id   Current user ID (used for watermark).
	 * @return array { signed_url: string, expiry: int|null, asset_id: string }
	 * @throws RuntimeException When Gumlet is not fully configured.
	 */
	public static function generate( string $asset_id, int $user_id = 0 ): array {

		$collection_id     = Helper::get_settings( 'gumlet_collection_id', '' );
		$signed_enabled    = (bool) Helper::get_settings( 'gumlet_signed_url_enabled', false );
		$drm_enabled       = (bool) Helper::get_settings( 'gumlet_drm_enabled', false );
		$watermark_enabled = (bool) Helper::get_settings( 'gumlet_watermark_enabled', false );
		$watermark_text    = sanitize_text_field( Helper::get_settings( 'gumlet_watermark_text', '' ) );

		// Build embed path
		if ( ! empty( $collection_id ) ) {
			$embed_path = rawurlencode( $collection_id ) . '/' . rawurlencode( $asset_id );
		} else {
			$embed_path = rawurlencode( $asset_id );
		}

		$optional_params = [];

		if ( $drm_enabled ) {
			$optional_params['drm'] = 'true';
		}

		if ( $watermark_enabled ) {
			if ( ! empty( $watermark_text ) ) {
				$optional_params['watermark_text'] = $watermark_text;
			} elseif ( $user_id > 0 ) {
				$user = get_userdata( $user_id );
				if ( $user && ! empty( $user->user_email ) ) {
					$optional_params['watermark_text'] = $user->user_email;
				}
			}
		}

		$expiry     = null;
		$signed_url = 'https://play.gumlet.io/embed/' . $embed_path;

		if ( $signed_enabled ) {

			$secret     = self::get_secret();
			$expiry_sec = absint( Helper::get_settings( 'gumlet_token_expiry', 3600 ) );

			if ( empty( $secret ) ) {
				throw new RuntimeException( 'Gumlet token secret is not configured.' );
			}

			if ( $expiry_sec < 60 ) {
				$expiry_sec = 3600;
			}

			$expiry = time() + $expiry_sec;

			// ✅ STEP 2: Build relative URL with optional params only
			$embed_relative = '/embed/' . $embed_path;
			if ( ! empty( $optional_params ) ) {
				$embed_relative .= '?' . http_build_query( $optional_params );
			}

			// ✅ STEP 3: Append signed_expires to get the exact string to sign
			$separator      = empty( $optional_params ) ? '?' : '&';
			$string_to_sign = $embed_relative . $separator . 'signed_expires=' . $expiry;

			// ✅ STEP 4: Decode base64 secret
			$decoded_secret = base64_decode( $secret, true );
			if ( false === $decoded_secret || empty( $decoded_secret ) ) {
				$decoded_secret = $secret; // fallback: use raw
			}

			// ✅ STEP 5: HMAC-SHA1 exactly as Gumlet docs
			$signed_token = hash_hmac( 'sha1', $string_to_sign, $decoded_secret );

			// ✅ STEP 6: Build final URL manually in EXACT order
			// Order must be: optional_params → signed_expires → signed_token
			$final_params = $optional_params; // drm, watermark first
			$final_params['signed_expires'] = $expiry;
			$final_params['signed_token']   = $signed_token;

			$signed_url .= '?' . http_build_query( $final_params );

		} else {
			if ( ! empty( $optional_params ) ) {
				$signed_url .= '?' . http_build_query( $optional_params );
			}
		}

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
