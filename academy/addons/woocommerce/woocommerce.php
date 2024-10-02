<?php

namespace AcademyWoocommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Academy\Interfaces\AddonInterface;
use Academy\Helper;

final class Woocommerce implements AddonInterface {
	private $addon_name = 'woocommerce';
	private function __construct() {
		$this->define_constants();
		$this->init_addon();
	}

	public function define_constants() {
		define( 'ACADEMY_WOOCOMMERCE_VERSION', '1.0' );
	}

	public function init_addon() {
		// fire addon activation hook
		add_action( "academy/addons/activated_{$this->addon_name}", array( $this, 'addon_activation_hook' ) );
		// if disable then stop running addon
		if ( ! \Academy\Helper::get_addon_active_status( $this->addon_name ) || ! Helper::is_active_woocommerce() ) {
			return;
		}

		// integration starts
		Integration::init();
		Ajax::init();
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
