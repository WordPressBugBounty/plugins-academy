<?php
namespace AcademyMultiInstructor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Interfaces\AddonInterface;

final class MultiInstructor implements AddonInterface {
	private $addon_name = 'multi_instructor';
	private function __construct() {
		$this->define_constants();
		$this->init_addon();
	}
	public static function init() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	public function define_constants() {
		/**
		 * Defines CONSTANTS for Whole Addon.
		 */
		define( 'ACADEMY_MULTI_INSCTUCTOR_VERSION', '1.0.0' );
		define( 'ACADEMY_MULTI_INSCTUCTOR_VERSION_NAME', 'academy_multi_instructor_version' );
		define( 'ACADEMY_MULTI_INSCTUCTOR_INCLUDES_DIR_PATH', ACADEMY_ROOT_DIR_PATH . 'includes/addons/multi-instructor/includes/' );
	}

	public function init_addon() {
		// fire addon activation hook
		add_action( "academy/addons/activated_{$this->addon_name}", array( $this, 'addon_activation_hook' ) );
		// if disable then stop running addons
		if ( ! \Academy\Helper::get_addon_active_status( $this->addon_name ) ) {
			return;
		}

		Admin\Settings::Init();
		Ajax::Init();
		Post::init();
		API::Init();
	}

	public function addon_activation_hook() {
		Installer::init();
	}
}
