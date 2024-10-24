<?php
/*
 * Plugin Name:		Academy LMS
 * Plugin URI:		http://academylms.net
 * Description:		Share your knowledge by launching an online course.
 * Version:			2.2.0
 * Author:			Academy LMS
 * Author URI:		http://academylms.net
 * License:			GPL-3.0+
 * License URI:		http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:		academy
 * Domain Path:		/languages
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
		add_action( 'plugins_loaded', [ $this, 'load_action_scheduler' ], -10 );
		add_action( 'plugins_loaded', [ $this, 'on_plugins_loaded' ] );
		add_action( 'academy_loaded', [ $this, 'init_plugin' ] );

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
		define( 'ACADEMY_VERSION', '2.2.0' );
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
		$this->load_textdomain();
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
		Academy\Integration::init();
		Academy\Migration::init();
		Academy\Shortcode::init();
		Academy\Customizer::init();
		Academy\Miscellaneous::init();
		if ( is_admin() ) {
			Academy\Admin::init();
		} else {
			Academy\Frontend::init();
		}
	}

	public function load_global_css() {
		Academy\Classes\GlobalCss::init();
	}

	public function load_addons() {
		Academy\Addons::init();
	}

	public function load_textdomain() {
		load_plugin_textdomain(
			'academy',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages/'
		);
	}

	public function set_global_settings() {
		$GLOBALS['academy_settings'] = json_decode( get_option( ACADEMY_SETTINGS_NAME, '{}' ) );
		$GLOBALS['academy_addons'] = json_decode( get_option( ACADEMY_ADDONS_SETTINGS_NAME, '{}' ) );
	}

	public function load_action_scheduler() {
		require_once ACADEMY_ROOT_DIR_PATH . 'library/action-scheduler/action-scheduler.php';
	}

	public function load_dependency() {
		require_once ACADEMY_INCLUDES_DIR_PATH . 'autoload.php';
		require_once ACADEMY_ROOT_DIR_PATH . 'library/deps/autoload.php';
		require_once ACADEMY_INCLUDES_DIR_PATH . 'functions.php';
		require_once ACADEMY_INCLUDES_DIR_PATH . 'hooks.php';
	}

	public function activate() {
		Academy\Installer::init();
	}

	public function deactivate() {

	}

	public function activated_redirect( $plugin, $network_wide = null ) {
		if ( ACADEMY_PLUGIN_BASENAME === $plugin ) {
			if ( ! get_option( 'academy_has_redirect_to_setup_wizard' ) ) {
				update_option( 'academy_has_redirect_to_setup_wizard', true, false );
				wp_safe_redirect( admin_url( 'admin.php?page=academy-setup' ) );
				exit;
			}
		}
	}
}

/**
 * Initializes the main plugin
 *
 * @return \Academy
 */
function academy_start() {
	return Academy::init();
}

// Plugin Start
academy_start();
