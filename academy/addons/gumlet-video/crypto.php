<?php
namespace AcademyGumletVideo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Crypto {

	private static function get_key(): string {
		$salt = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
		$salt .= defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : '';
		if ( empty( $salt ) ) {
			$salt = wp_salt( 'auth' );
		}
		return hash( 'sha256', $salt, true );
	}

	public static function encrypt( string $value ): string {
		if ( empty( $value ) ) {
			return '';
		}
		$key    = self::get_key();
		$iv     = random_bytes( 16 );
		$ct     = openssl_encrypt( $value, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
		if ( false === $ct ) {
			return '';
		}
		return base64_encode( $iv . $ct );
	}

	public static function decrypt( string $encoded ): string {
		if ( empty( $encoded ) ) {
			return '';
		}
		$data = base64_decode( $encoded, true );
		if ( false === $data || strlen( $data ) < 17 ) {
			return '';
		}
		$key = self::get_key();
		$iv  = substr( $data, 0, 16 );
		$ct  = substr( $data, 16 );
		$pt  = openssl_decrypt( $ct, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
		return false !== $pt ? $pt : '';
	}

	public static function is_encrypted( string $value ): bool {
		if ( empty( $value ) ) {
			return false;
		}
		$decoded = base64_decode( $value, true );
		return false !== $decoded && strlen( $decoded ) >= 17;
	}
}
