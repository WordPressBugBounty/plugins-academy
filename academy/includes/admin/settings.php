<?php
namespace Academy\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Admin\Settings\Base as BaseSettings;

class Settings {

	public static function init() {
		$self = new self();
		$self->save_settings();
	}

	public static function save_settings() {
		BaseSettings::save_settings();
	}
}
