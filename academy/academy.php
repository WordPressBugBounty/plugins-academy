<?php
/*
 * Plugin Name:     Academy LMS – AI Course Builder, Quizzes, Certificates & eLearning
 * Plugin URI:      http://academylms.net
 * Description:     Share your knowledge by launching an online course.
 * Version:         4.0.2
 * Author:          Academy LMS
 * Author URI:      http://academylms.net
 * License:         GPL-3.0+
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:     academy
 * Domain Path:     /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Academy {

	private function __construct() {
		$this->define_constants();
		$this->set_global_settings();
		$this->load_dependency();
		register_activation_hook( __FILE__, [ $this, 'activate' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );
		add_action( 'activated_plugin', array( $this, 'activated_redirect' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'maybe_open_setup_wizard' ) );
		add_action( 'plugins_loaded', [ $this, 'load_action_scheduler' ], -10 );
		add_action( 'plugins_loaded', [ $this, 'on_plugins_loaded' ] );
		add_action( 'academy_loaded', [ $this, 'init_plugin' ] );
		add_action( 'academy_loaded', [ $this, 'backfill_legacy_lesson_note_setting' ] );
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
		 * Defines CONSTANTS for Whole plugins.
		 */
		define( 'ACADEMY_VERSION', '4.0.2' );
		define( 'ACADEMY_DB_VERSION', '1.1' );
		define( 'ACADEMY_SETTINGS_NAME', 'academy_settings' );
		define( 'ACADEMY_ADDONS_SETTINGS_NAME', 'academy_addons' );
		define( 'ACADEMY_PLUGIN_FILE', __FILE__ );
		define( 'ACADEMY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		define( 'ACADEMY_PLUGIN_SLUG', 'academy' );
		define( 'ACADEMY_PLUGIN_ROOT_URI', plugins_url( '/', __FILE__ ) );
		define( 'ACADEMY_ROOT_DIR_PATH', plugin_dir_path( __FILE__ ) );
		define( 'ACADEMY_INCLUDES_DIR_PATH', ACADEMY_ROOT_DIR_PATH . 'includes/' );
		define( 'ACADEMY_ASSETS_DIR_PATH', ACADEMY_ROOT_DIR_PATH . 'assets/' );
		define( 'ACADEMY_ADDONS_DIR_PATH', ACADEMY_ROOT_DIR_PATH . 'addons/' );
		define( 'ACADEMY_BLOCK_TEMPLATES_DIR_PATH', ACADEMY_ROOT_DIR_PATH . 'templates/block-templates/' );
		define( 'ACADEMY_ASSETS_URI', ACADEMY_PLUGIN_ROOT_URI . 'assets/' );
		define( 'ACADEMY_TEMPLATE_DEBUG_MODE', false );
	}

	/**
	 * When WP has loaded all plugins, trigger the `academy_loaded` hook.
	 *
	 * This ensures `academy_loaded` is called only after all other plugins
	 * are loaded, to avoid issues caused by plugin directory naming changing
	 *
	 * @since 1.0.0
	 */
	public function on_plugins_loaded() {
		do_action( 'academy_loaded' );
	}

	/**
	 * Initialize the plugin
	 *
	 * @return void
	 */
	public function init_plugin() {
		// Init action.
		do_action( 'academy_before_init' );
		$this->load_global_css();
		$this->dispatch_hooks();
		$this->load_addons();
		// Init action.
		do_action( 'academy_init' );
	}

	public function dispatch_hooks() {
		Academy\Database::init();
		Academy\PermalinkRewrite::init();
		Academy\API::init();
		Academy\Ajax::init();
		Academy\Post::init();
		Academy\Assets::init();
		Academy\ScriptTranslations::init();
		Academy\TTS\Settings::init();
		Academy\Integration::init();
		Academy\Migration::init();
		Academy\Shortcode::init();
		Academy\Blocks::init();
		Academy\Design::init();
		Academy\LearnPage::init();
		Academy\FrontendDashboard\Dashboard::init();
		Academy\RegistrationForms::init();
		Academy\Customizer::init();
		Academy\Miscellaneous::init();
		Academy\Guardian::init();
		if ( is_admin() ) {
			Academy\Admin::init();
		}
		Academy\Frontend::init();
	}

	public function load_global_css() {
		Academy\Classes\GlobalCss::init();
	}

	public function load_addons() {
		Academy\Addons::init();
	}

	public function set_global_settings() {
		$GLOBALS['academy_settings'] = json_decode( get_option( ACADEMY_SETTINGS_NAME, '{}' ) );
		$GLOBALS['academy_addons'] = json_decode( get_option( ACADEMY_ADDONS_SETTINGS_NAME, '{}' ) );
	}

	/**
	 * `is_enabled_academy_lesson_note` was retired from the settings UI once
	 * lesson notes became the opt-in `notes` addon rather than a toggle, but
	 * external integrations (e.g. the zenappbuilder mobile-app builder's
	 * feature-detection) still read it via Helper::get_settings() to decide
	 * whether to offer a notes block. Only fill it in when genuinely absent, so
	 * a site that explicitly saved a value (from before the toggle was removed)
	 * keeps that value, and mirror the addon's real on/off state rather than
	 * hardcoding "on" — the addon is opt-in, so a site that hasn't enabled it has
	 * no /academy/v1/notes route for that block to call anyway.
	 *
	 * Runs on `academy_loaded` (after load_dependency()), not inside
	 * set_global_settings() itself — that runs before the autoloader is
	 * registered, so `Academy\Helper` isn't loadable there yet.
	 */
	public function backfill_legacy_lesson_note_setting() {
		if ( ! isset( $GLOBALS['academy_settings']->is_enabled_academy_lesson_note ) ) {
			$GLOBALS['academy_settings']->is_enabled_academy_lesson_note =
				\Academy\Helper::get_addon_active_status( 'notes' ) ? 'on' : 'off';
		}
	}

	public function load_action_scheduler() {
		$action_scheduler = ACADEMY_ROOT_DIR_PATH . 'vendor/woocommerce/action-scheduler/action-scheduler.php';
		if ( file_exists( $action_scheduler ) ) {
			require_once $action_scheduler;
		}
	}

	public function load_dependency() {
		require_once ACADEMY_INCLUDES_DIR_PATH . 'autoload.php';
		$prefixed_autoload = ACADEMY_ROOT_DIR_PATH . 'vendor/prefixed/autoload.php';
		if ( file_exists( $prefixed_autoload ) ) {
			require_once $prefixed_autoload;
		}
		// StoreEngine licensing/insights SDK. Kept OUT of Strauss prefixing
		// (excluded in composer.json) so `se_license_init()` stays a shared
		// global, and direct-required here — the SDK self-registers its own
		// classes, and its functions are function_exists-guarded, so loading
		// it alongside another plugin's copy is safe. Academy core hosts the
		// SDK so free addons (e.g. Academy Digital Campus) can license against
		// it without bundling their own copy or depending on Academy Pro.
		$license_sdk = ACADEMY_ROOT_DIR_PATH . 'vendor/storeengine/wordpress-sdk/init.php';
		if ( file_exists( $license_sdk ) ) {
			require_once $license_sdk;
		}
		require_once ACADEMY_INCLUDES_DIR_PATH . 'functions.php';
		require_once ACADEMY_INCLUDES_DIR_PATH . 'hooks.php';
	}

	public function activate() {
		Academy\Installer::init();
	}

	public function deactivate() {
	}

	public function activated_redirect( $plugin, $network_wide = null ) {
		if ( ACADEMY_PLUGIN_BASENAME !== $plugin || get_option( 'academy_has_redirect_to_setup_wizard' ) ) {
			return;
		}
		// Redirecting here would cut short a bulk activation (the other plugins
		// never get activated), and makes no sense for WP-CLI, ajax or a whole
		// network. Leave a note instead, and open setup on the next admin page.
		$bulk = isset( $_POST['checked'] ) || isset( $_GET['activate-multi'] ); // phpcs:ignore WordPress.Security.NonceVerification -- read only, to tell a bulk action apart.
		if ( $network_wide || $bulk || wp_doing_ajax() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			set_transient( 'academy_open_setup_wizard', 1, DAY_IN_SECONDS );
			return;
		}
		update_option( 'academy_has_redirect_to_setup_wizard', true, false );
		wp_safe_redirect( admin_url( 'admin.php?page=academy-setup' ) );
		exit;
	}

	/**
	 * Open setup once, on the first ordinary admin page after an activation that
	 * could not redirect straight away.
	 *
	 * @return void
	 */
	public function maybe_open_setup_wizard() {
		if ( ! get_transient( 'academy_open_setup_wizard' ) || wp_doing_ajax() || is_network_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		delete_transient( 'academy_open_setup_wizard' );
		if ( get_option( 'academy_has_redirect_to_setup_wizard' ) ) {
			return;
		}
		update_option( 'academy_has_redirect_to_setup_wizard', true, false );
		wp_safe_redirect( admin_url( 'admin.php?page=academy-setup' ) );
		exit;
	}
}

/**
 * Initializes the main plugin
 *
 * @return \Academy
 */
function academy_start() { // phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed -- plugin bootstrap: the class and its public start function share the main plugin file.
	return Academy::init();
}

// Plugin Start
academy_start();
