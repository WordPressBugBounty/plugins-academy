<?php
namespace Academy\Customizer\Style;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Base {
	public static function get_settings() {
		return get_option( 'academy_customizer_style_settings' );
	}

	public static function add_unit_to_value( $valueArray, $unit = 'px' ) {
		$output = '';
		if ( is_array( $valueArray ) ) {
			foreach ( $valueArray as $valueItem ) {
				$output .= (int) $valueItem;
				$output .= $unit . ' ';
			}
		}
		return rtrim( $output );
	}

	public static function generate_dimensions_css( $selector, $data, $type = 'padding' ) {
		$raw = '';
		if ( max( $data['desktop'] ) ) {
			$raw  = $selector . '{' . $type . ':' . self::add_unit_to_value( $data['desktop'], $data['unit'] ) . ';}';
		}
		if ( max( $data['tablet'] ) ) {
			$raw .= '@media(max-width: 1024px){' . $selector . '{' . $type . ':' . self::add_unit_to_value( $data['tablet'], $data['unit'] ) . ';}}';
		}
		if ( max( $data['mobile'] ) ) {
			$raw .= '@media(max-width: 767px){' . $selector . '{' . $type . ':' . self::add_unit_to_value( $data['mobile'], $data['unit'] ) . ';}}';
		}
		return $raw;
	}
}
