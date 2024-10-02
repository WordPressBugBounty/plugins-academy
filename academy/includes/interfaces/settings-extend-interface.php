<?php
namespace Academy\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface SettingsExtendInterface {
	public function set_settings_default_data( $settings);
}
