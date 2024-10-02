<?php

namespace AcademyEasyDigitalDownloads;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Academy\Helper;
use Academy\Interfaces\AddonInterface;

final class EasyDigitalDownloads implements AddonInterface {
	private $addon_name = 'easy-digital-downloads';
	private function __construct() {
		$this->define_constants();
		$this->init_addon();
	}

	public function define_constants() {
		/**
		 * Defines CONSTANTS for Whole Addon.
		 */
		define( 'ACADEMY_EASY_DIGITAL_DOWNLOADS_VERSION', '1.0' );
		define( 'ACADEMY_EASY_DIGITAL_DOWNLOADS_ADDON_NAME', $this->addon_name );
	}

	public function init_addon() {
		// fire addon activation hook
		add_action( "academy/addons/activated_{$this->addon_name}", array( $this, 'addon_activation_hook' ) );
		// if disable then stop running addon
		if ( ! \Academy\Helper::get_addon_active_status( $this->addon_name ) || ! Helper::is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) ) {
			return;
		}

		// integration starts
		Ajax::init();
		Integration::init();
		Hooks::init();
	}

	public static function init() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	public function addon_activation_hook() {

	}
}
