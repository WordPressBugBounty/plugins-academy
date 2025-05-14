<?php
namespace AcademyChatgpt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Interfaces\AddonInterface;
use AcademyChatgpt\Platforms\Chatgpt\Init as ChatgptIntegrationInit;
use AcademyChatgpt\CourseImport\Ajax as CourseImportAjax;

final class Chatgpt implements AddonInterface {
	private $addon_name = 'chatgpt';
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
		define( 'ACADEMY_CHATGPT_VERSION', '1.0.0' );
	}

	public function init_addon() {
		// fire addon activation hook
		add_action( "academy/addons/activated_{$this->addon_name}", array( $this, 'addon_activation_hook' ) );
		// if disable then stop running addons
		if ( ! \Academy\Helper::get_addon_active_status( $this->addon_name ) ) {
			return;
		}

		// load if request come from administrative interface
		// chatgpt integration
		if ( is_admin() ) {
			( new ChatgptIntegrationInit() )->dispatch_actions();
			( new CourseImportAjax() )->dispatch_actions();
		}

	}

	public function addon_activation_hook() {
		// DO NOTHING
	}


}

