<?php
namespace AcademyQuizpress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Interfaces\AddonInterface;

final class Quizpress implements AddonInterface {
	private $addon_name = 'quizpress';
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
		define( 'ACADEMY_QUIZPRESS_VERSION', '1.0' );
				define( 'ACADEMY_QUIZPRESS_DIR_PATH', ACADEMY_ADDONS_DIR_PATH . 'quizpress/' );
	}
	public function init_addon() {
		// Not a user-toggleable addon anymore — QuizPress lives under
		// "Extensions & Integrations" like GameEngine/ECM, so this glue is on
		// whenever the QuizPress plugin itself is active.
		if ( ! self::is_quizpress_active() ) {
			return;
		}

		// The links table used to be created by the addon's on-toggle hook;
		// with no toggle, create it the first time QuizPress is seen active.
		if ( \Academy\Options::get( \Academy\Options::DB_VERSIONS, $this->addon_name ) !== ACADEMY_QUIZPRESS_VERSION ) {
			$this->addon_activation_hook();
		}

		Database::init();
		API::init();
		Frontend::init();
		Hooks::init();
		Classes\LinkSync::init();
	}

	/**
	 * QuizPress is a separate sibling plugin — everything here is a no-op
	 * unless it's actually installed and active.
	 *
	 * @return bool
	 */
	public static function is_quizpress_active() {
		return defined( 'QUIZPRESS_VERSION' );
	}

	public function addon_activation_hook() {
		Database::init();
		Installer::init();
	}

	public function addon_deactivation_hook() {
	}
}
