<?php
namespace AcademyCertificates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Interfaces\AddonInterface;
use AcademyCertificates\Ajax\FontDownloader;
use WP_Query;

final class Certificates implements AddonInterface {
	private $addon_name = 'certificates';
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
		define( 'ACADEMY_CERTIFICATE_VERSION', '1.0.0' );
	}

	public function init_addon() {
		// fire addon activation hook
		add_action( "academy/addons/activated_{$this->addon_name}", array( $this, 'addon_activation_hook' ) );
		add_action( "academy/addons/deactivated_{$this->addon_name}", array( $this, 'addon_deactivation_hook' ) );

		// if disable then stop running addons
		if ( ! \Academy\Helper::get_addon_active_status( $this->addon_name ) ) {
			return;
		}

		if ( \Academy\Helper::is_plugin_active( 'academy-certificates/academy-certificates.php' ) ) {
			return;
		}

		( new FontDownloader() )->dispatch_actions();
		if ( is_admin() ) {
			Admin::init();
		}
		Database::init();
		Assets::init();
		Frontend::init();
		Api::init();
	}

	public function addon_activation_hook() {
		Database::init();
		Installer::init();
		\Academy\Helper::flush_rewrite_rules();
	}

	public function addon_deactivation_hook() {
		\Academy\Helper::flush_rewrite_rules();
	}
}

