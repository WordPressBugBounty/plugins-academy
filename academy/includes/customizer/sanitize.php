<?php
namespace Academy\Customizer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sanitize {

	public static function number_dimension( $value ) {
		if ( ! is_array( $value ) ) {
			return new \WP_Error( 'invalid_value', __( 'Expected array.', 'academy' ) );
		}
		$value['desktop'] = intval( sanitize_text_field( $value['desktop'] ) );
		$value['tablet']  = intval( sanitize_text_field( $value['tablet'] ) );
		$value['mobile']  = intval( sanitize_text_field( $value['mobile'] ) );
		return $value;
	}
	public static function sortable( $value ) {
		if ( ! is_array( $value ) ) {
			return new \WP_Error( 'invalid_value', __( 'Expected array.', 'academy' ) );
		}
		return [
			'items' => \Academy\Helper::sanitize_text_or_array_field( $value['items'] ),
		];
	}
	public static function dimensions( $value ) {
		if ( ! is_array( $value ) ) {
			return new \WP_Error( 'invalid_value', __( 'Expected array.', 'academy' ) );
		}
		$value['desktop']   = \Academy\Helper::sanitize_text_or_array_field( $value['desktop'] );
		$value['tablet']    = \Academy\Helper::sanitize_text_or_array_field( $value['tablet'] );
		$value['mobile']    = \Academy\Helper::sanitize_text_or_array_field( $value['mobile'] );
		$value['unit']      = sanitize_text_field( $value['unit'] );
		$value['isLinked']  = boolval( sanitize_text_field( $value['isLinked'] ) );
		return $value;
	}
}
