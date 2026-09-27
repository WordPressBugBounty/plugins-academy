<?php
namespace AcademyEasyContentManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Interfaces\AddonInterface;

final class EasyContentManager implements AddonInterface {

	private function __construct() {
		$this->define_constants();
		$this->init_addon();
	}

	public static function init(): self {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	public function define_constants() {
		define( 'ACADEMY_EASY_CONTENT_MANAGER_VERSION', '1.0' );
	}

	public function init_addon() {
		// ECM is a separate sibling plugin, and this integration has never
		// been a user-toggleable Academy addon (no on/off entry in Settings
		// > Addons) — it just wires Academy's custom taxonomy/field-group
		// UI slots up to ECM whenever ECM itself is installed and active.
		// Mirrors AcademyStoreEngine\Storeengine's gating.
		if ( ! \Academy\Helper::is_active_ecm() ) {
			return;
		}

		Hooks::init();
	}

	public function addon_activation_hook() {
	}
}
