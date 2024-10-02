<?php
namespace AcademyMigrationTool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Interfaces\AddonInterface;

final class MigrationTool implements AddonInterface {
	private $addon_name = 'migration-tool';
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
		define( 'ACADEMY_MIGRATION_TOOL_VERSION', '1.0.0' );
	}

	public function init_addon() {
		// fire addon activation hook
		add_action( "academy/addons/activated_{$this->addon_name}", array( $this, 'addon_activation_hook' ) );
		// if disable then stop running addons
		if ( ! \Academy\Helper::get_addon_active_status( $this->addon_name ) ) {
			return;
		}

		Ajax::init();
	}

	public function addon_activation_hook() {

	}
}
