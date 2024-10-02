<?php
namespace Academy\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface SettingsInterface {
	public static function get_settings_saved_data();
	public static function get_settings_default_data();
	public static function save_settings();
}
