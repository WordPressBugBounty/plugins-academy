<?php
/**
 * Dummy Data Seeder addon (free, dev-only).
 *
 * One-click generator for a complete sample course — every lesson type plus a
 * quiz covering every question type — with a manifest-based reset. Toggleable
 * so it loads zero code when disabled (the intended state on production). When
 * active it exposes the `wp academy seed` CLI command and an Academy → Tools →
 * Dummy Data admin tab, and registers the core data providers.
 *
 * @package AcademySeeder
 */

namespace AcademySeeder;

use Academy\Interfaces\AddonInterface;
use AcademySeeder\Classes\Ajax;
use AcademySeeder\Classes\Cli;
use AcademySeeder\Providers\CategoryProvider;
use AcademySeeder\Providers\CourseProvider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Seeder implements AddonInterface {

	/**
	 * @var string
	 */
	private $addon_name = 'seeder';

	private function __construct() {
		$this->define_constants();
		$this->init_addon();
	}

	/**
	 * @return Seeder
	 */
	public static function init() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	public function define_constants() {
		define( 'ACADEMY_SEEDER_VERSION', '1.0.0' );
		define( 'ACADEMY_SEEDER_DIR_PATH', ACADEMY_ADDONS_DIR_PATH . 'seeder/' );
	}

	public function init_addon() {
		// Fire addon activation/deactivation hooks (registered even while off).
		add_action( "academy/addons/activated_{$this->addon_name}", [ $this, 'addon_activation_hook' ] );
		add_action( "academy/addons/deactivated_{$this->addon_name}", [ $this, 'addon_deactivation_hook' ] );

		// Stop here when the addon is disabled — load nothing else.
		if ( ! \Academy\Helper::get_addon_active_status( $this->addon_name ) ) {
			return;
		}

		add_filter( 'academy/seeder/providers', [ $this, 'register_providers' ] );

		if ( is_admin() ) {
			( new Ajax() )->dispatch_actions();
		}

		if ( defined( 'WP_CLI' ) && \WP_CLI ) {
			\WP_CLI::add_command( 'academy seed', Cli::class );
		}
	}

	/**
	 * Register the core data providers on the shared filter.
	 *
	 * @param \AcademySeeder\Classes\AbstractSeederProvider[] $providers
	 *
	 * @return array
	 */
	public function register_providers( array $providers ) {
		$providers[] = new CategoryProvider();
		$providers[] = new CourseProvider();

		return $providers;
	}

	public function addon_activation_hook() {
		\Academy\Helper::flush_rewrite_rules();
	}

	public function addon_deactivation_hook() {
		\Academy\Helper::flush_rewrite_rules();
	}
}
