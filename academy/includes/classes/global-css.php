<?php
namespace Academy\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Helper;

class GlobalCss {
	public static function init() {
		$self = new self();
		if ( is_admin() ) {
			add_action( 'admin_head', array( $self, 'get_global_css' ) );
			add_action( 'admin_print_styles', array( $self, 'get_global_css' ), 30 );
		} else {
			add_action( 'wp_head', array( $self, 'get_global_css' ) );
		}
	}
	public function get_global_css() {
		$primary_color = Helper::get_settings( 'primary_color', '#7b68ee' );
		$secondary_color = Helper::get_settings( 'secondary_color', '#eae8fa' );
		$text_color = Helper::get_settings( 'text_color', '#111' );
		$border_color = Helper::get_settings( 'border_color', '#E5E4E6' );
		$gray_color = Helper::get_settings( 'gray_color', '#f6f7f9' );
		$is_enabled_academy_web_font = (bool) Helper::get_settings( 'is_enabled_academy_web_font' );
		$primary_font = "'Inter', sans-serif";
		if ( ! $is_enabled_academy_web_font && ! is_admin() ) {
			$primary_font = 'inherit';
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "
            <style>
                :root {
                    --academy-primary-font: $primary_font;
                    --academy-primary-color: $primary_color;
                    --academy-secondary-color: $secondary_color;
                    --academy-text-color: $text_color;
                    --academy-border-color: $border_color;
                    --academy-gray-color: $gray_color;
                }
            </style>
        ";
	}
}
