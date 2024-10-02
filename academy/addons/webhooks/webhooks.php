<?php
namespace AcademyWebhooks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Interfaces\AddonInterface;
use Exception;

final class Webhooks implements AddonInterface {
	private $addon_name = 'webhooks';

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
		define( 'ACADEMY_WEBHOOK_VERSION', '1.0' );
		define( 'ACADEMY_WEBHOOK_VERSION_NAME', 'academy_webhook_version' );
		define( 'ACADEMY_WEBHOOK_INCLUDES_DIR_PATH', ACADEMY_ROOT_DIR_PATH . 'addons/webhooks/includes/' );
		define( 'ACADEMY_WEBHOOK_LISTENERS_DIR_PATH', ACADEMY_ROOT_DIR_PATH . 'addons/webhooks/listeners' );
	}
	public function init_addon() {
		// fire addon activation hook
		add_action( "academy/addons/activated_{$this->addon_name}", array( $this, 'addon_activation_hook' ) );
		// if disable then stop running addons
		if ( ! \Academy\Helper::get_addon_active_status( $this->addon_name ) ) {
			return;
		}

		Database::init();
		Listeners::init();
	}

	public function addon_activation_hook() {
		flush_rewrite_rules();
	}
}
