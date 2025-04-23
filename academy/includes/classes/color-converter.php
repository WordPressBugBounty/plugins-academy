<?php
namespace Academy\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ColorConverter {
	public static function rgb_to_hex( string $string ) : string {
		return preg_replace_callback(
			'/rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/mi',
			function ( $matches ) {
				return self::convert_rgb_to_hex(
					intval( $matches[1] ),
					intval( $matches[2] ),
					intval( $matches[3] ),
				);
			},
			$string
		);
	}
	public static function convert_rgb_to_hex( int $r, int $g, int $b ) : string {
		return '#' . strtoupper( implode( '', [
			str_pad(
				dechex( max( 0, min( 255, $r ) ) ), 2, '0', STR_PAD_LEFT
			),
			str_pad(
				dechex( max( 0, min( 255, $g ) ) ), 2, '0', STR_PAD_LEFT
			),
			str_pad(
				dechex( max( 0, min( 255, $b ) ) ), 2, '0', STR_PAD_LEFT
			),
		] ) );
	}
}
