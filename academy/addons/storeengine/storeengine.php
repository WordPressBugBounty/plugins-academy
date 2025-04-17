<?php

namespace AcademyStoreEngine;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Academy\Interfaces\AddonInterface;

final class Storeengine implements AddonInterface {

	private function __construct() {
		$this->define_constants();
		$this->init_addon();
	}

	public function define_constants() {
		define( 'ACADEMY_STOREENGINE_VERSION', '1.0' );
	}

	public function init_addon() {
		// if disable then stop running addon
		if ( ! class_exists( \StoreEngine::class ) || ( defined( 'STOREENGINE_VERSION' ) && version_compare( STOREENGINE_VERSION, '1.0.0-beta-3', '<=' ) ) ) {
			return;
		}

		// integration starts
		Hooks::init();
		Integration::init();
		Ajax::init();
	}

	public static function init(): self {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	public function addon_activation_hook() {
	}
}
