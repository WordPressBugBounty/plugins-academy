<?php
/**
 * "Extensions & Integrations" catalog for the Add-ons screen.
 *
 * A small, filterable list of free companion plugins that extend Academy LMS.
 * The React admin renders each as an install/activate/manage card
 * (see dev_academy/.../pages/addons/Integrations.js). Live install state
 * (active/installed) is layered on in {@see get_teaser_data()} and localized
 * into `AcademyGlobal.integrations`.
 *
 * Install is key-gated: the AJAX endpoint only ever receives a catalog KEY, so
 * a package URL is never taken from the client. For each installable plugin we
 * try wordpress.org first (by slug) and fall back to its Kodezen store URL —
 * so the catalog works whether a plugin is hosted on .org or the store.
 *
 * @package Academy\Admin
 */

namespace Academy\Admin;

use Academy\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Integrations {

	/**
	 * Static metadata for every companion plugin. `kind`:
	 *   'install' — real plugin; one-click install/activate then Manage/Docs.
	 *   'soon'    — announced but not shipped yet; a "Coming soon" teaser.
	 *
	 * @return array<string, array>
	 */
	protected static function definitions(): array {
		$definitions = array(
			'storeengine'      => array(
				'kind'         => 'install',
				'name'         => __( 'StoreEngine', 'academy' ),
				'basename'     => 'storeengine/storeengine.php',
				'slug'         => 'storeengine',
				'details'      => __( 'Sell courses with a full commerce engine — carts, checkout, orders and subscriptions.', 'academy' ),
				'icon'         => 'storeengine',
				'logo'         => ACADEMY_ASSETS_URI . 'images/addons/storeengine.svg',
				'color'        => '#2271b1',
				'requires'     => '',
				'docs_url'     => 'https://storeengine.pro/',
			),
			'gameengine'       => array(
				'kind'         => 'install',
				'name'         => __( 'GameEngine', 'academy' ),
				'basename'     => 'gameengine/gameengine.php',
				'slug'         => 'gameengine',
				'details'      => __( 'Gamify learning with points, badges, ranks and leaderboards across your courses.', 'academy' ),
				'icon'         => 'gamipress',
				'logo'         => ACADEMY_ASSETS_URI . 'images/addons/gameengine.svg',
				'color'        => '#006BFF',
				'requires'     => '',
				'docs_url'     => '',
			),
			'zencommunity'     => array(
				'kind'         => 'install',
				'name'         => __( 'ZenCommunity', 'academy' ),
				'basename'     => 'zencommunity/zencommunity.php',
				'slug'         => 'zencommunity',
				'details'      => __( 'Add community spaces, discussions and social learning around your courses.', 'academy' ),
				'icon'         => 'zencommunity',
				'logo'         => ACADEMY_ASSETS_URI . 'images/addons/zencommunity.svg',
				'color'        => '#2a3bee',
				'requires'     => '',
				'docs_url'     => '',
			),
			'gemcrm'           => array(
				'kind'         => 'install',
				'name'         => __( 'GemCRM', 'academy' ),
				'basename'     => 'gemcrm/gemcrm.php',
				'slug'         => 'gemcrm',
				'details'      => __( 'CRM, email automation and contact management to nurture your students.', 'academy' ),
				'icon'         => 'gemcrm',
				'logo'         => ACADEMY_ASSETS_URI . 'images/addons/gemcrm.svg',
				'color'        => '#006bff',
				'requires'     => '',
				'docs_url'     => 'https://academylms.net/email-marketing-for-online-courses/',
			),
			'gemsecurity'      => array(
				'kind'         => 'install',
				'name'         => __( 'GemSecurity', 'academy' ),
				'basename'     => 'gemsecurity/gemsecurity.php',
				'slug'         => 'gemsecurity',
				'details'      => __( 'Social login, reCAPTCHA and brute-force login protection for your site.', 'academy' ),
				'icon'         => 'lock',
				'color'        => '#dc2626',
				'requires'     => '',
				'docs_url'     => '',
			),
			'quizpress'        => array(
				'kind'         => 'install',
				'name'         => __( 'QuizPress', 'academy' ),
				'basename'     => 'quizpress/quizpress.php',
				'slug'         => 'quizpress',
				'details'      => __( 'Attach an existing QuizPress quiz to a course as a curriculum item — QuizPress manages the quiz content and grading, Academy handles enrollment and course progress.', 'academy' ),
				'icon'         => 'quizpress',
				'logo'         => ACADEMY_ASSETS_URI . 'images/addons/quizpress.svg',
				'color'        => '#f97316',
				'requires'     => '',
				'docs_url'     => '',
			),
			'easy-content-manager' => array(
				'kind'         => 'install',
				'name'         => __( 'Easy Content Manager', 'academy' ),
				'basename'     => 'easy-content-manager/easy-content-manager.php',
				'slug'         => 'easy-content-manager',
				'details'      => __( 'Manage enterprise-level course content and documents efficiently.', 'academy' ),
				'icon'         => 'ecm',
				'logo'         => ACADEMY_ASSETS_URI . 'images/addons/ecm.svg',
				'color'        => '#0ea5e9',
				'requires'     => '',
				'docs_url'     => 'https://academylms.net/docs/getting-started-with-academy-lms-and-ecm-integration/',
			),
			'gembooking'       => array(
				'kind'         => 'install',
				'name'         => __( 'GemBooking', 'academy' ),
				'basename'     => 'gembooking/gembooking.php',
				'slug'         => 'gembooking',
				'details'      => __( 'Enable booking and scheduling for classes, sessions, or training.', 'academy' ),
				'icon'         => 'tutor-booking-fill',
				'logo'         => ACADEMY_ASSETS_URI . 'images/addons/gembooking.svg',
				'color'        => '#006BFF',
				'requires'     => '',
				'docs_url'     => '',
			),
			'trueplayer'       => array(
				'kind'         => 'install',
				'name'         => __( 'TruePlayer', 'academy' ),
				'basename'     => 'trueplayer/trueplayer.php',
				'slug'         => 'trueplayer',
				'details'      => __( 'A modern video player with watch-verification and quiz-gating for lesson videos.', 'academy' ),
				'icon'         => 'video',
				'color'        => '#6366f1',
				'requires'     => '',
				'docs_url'     => '',
			),
			'academy-digital-campus' => array(
				'kind'         => 'install',
				'name'         => __( 'Academy Digital Campus', 'academy' ),
				'basename'     => 'academy-digital-campus/academy-digital-campus.php',
				'slug'         => 'academy-digital-campus',
				'details'      => __( 'Turn Academy into a full school system — admissions, parents, exams, fees and report cards.', 'academy' ),
				'icon'         => 'multi-instructor-fill',
				'color'        => '#10b981',
				'requires'     => '',
				'docs_url'     => '',
			),
			'ablocks'          => array(
				'kind'         => 'install',
				'name'         => __( 'aBlocks', 'academy' ),
				'basename'     => 'ablocks/ablocks.php',
				'slug'         => 'ablocks',
				'details'      => __( 'A powerful Gutenberg block library to design pages without code.', 'academy' ),
				'icon'         => 'aBlocks',
				'logo'         => ACADEMY_ASSETS_URI . 'images/addons/aBlocks.svg',
				'color'        => '#1D1D1F',
				'requires'     => '',
				'docs_url'     => 'https://academylms.net/how-to-use-ablocks-in-academy-lms/',
			),
			'academy-elementor-addons' => array(
				'kind'         => 'install',
				'name'         => __( 'Academy Elementor Addon', 'academy' ),
				'basename'     => 'academy-elementor-addons/academy-elementor-addons.php',
				'slug'         => 'academy-elementor-addons',
				'details'      => __( 'Academy widgets for the Elementor page builder.', 'academy' ),
				'icon'         => 'elementor',
				'logo'         => ACADEMY_ASSETS_URI . 'images/addons/elementor.svg',
				'color'        => '#d0455c',
				'requires'     => __( 'Elementor', 'academy' ),
				'docs_url'     => '',
			),
			'academy-divi-modules' => array(
				'kind'         => 'install',
				'name'         => __( 'Academy Divi Modules', 'academy' ),
				'basename'     => 'academy-divi-modules/academy-divi-modules.php',
				'slug'         => 'academy-divi-modules',
				'details'      => __( 'Academy modules for the Divi builder.', 'academy' ),
				'icon'         => 'divi-module',
				'logo'         => ACADEMY_ASSETS_URI . 'images/addons/divi-module.svg',
				'color'        => '#8b5cf6',
				'requires'     => __( 'Divi', 'academy' ),
				'docs_url'     => '',
			),
			'academy-bricks-addons' => array(
				'kind'         => 'install',
				'name'         => __( 'Academy Bricks Addons', 'academy' ),
				'basename'     => 'academy-bricks-addons/academy-bricks-addons.php',
				'slug'         => 'academy-bricks-addons',
				'details'      => __( 'Academy elements for the Bricks site builder.', 'academy' ),
				'icon'         => 'bricks',
				'logo'         => ACADEMY_ASSETS_URI . 'images/addons/bricks.svg',
				'color'        => '#111111',
				'requires'     => __( 'Bricks', 'academy' ),
				'docs_url'     => '',
			),
		);

		/**
		 * Filter the raw integration catalog (before live state is layered on).
		 *
		 * @param array $definitions Catalog keyed by plugin key.
		 */
		return apply_filters( 'academy/admin/integrations_definitions', $definitions );
	}

	/**
	 * Kodezen-store download URL for a key — the fallback used when the plugin
	 * is not on wordpress.org. Filterable so a site can repoint it.
	 *
	 * @param string $key
	 * @param string $slug
	 */
	public static function store_download_url( string $key, string $slug ): string {
		// GemSecurity's package lives under a different store path than the
		// generic pattern below — this is the same URL already used by the
		// compatibility notice at Admin\Notices::maybe_add_gemsecurity_notice().
		$overrides = array(
			'gemsecurity' => 'https://store.kodezen.com/se-download/free/gemsecurity/latest/',
		);

		$url = $overrides[ $key ] ?? "https://store.kodezen.com/download/free/{$slug}/latest/";

		return (string) apply_filters( 'academy/admin/integration_download_url', $url, $key, $slug );
	}

	/**
	 * Valid catalog keys — the only accepted input to the install endpoint.
	 *
	 * @return string[]
	 */
	public static function get_keys(): array {
		return array_keys( self::definitions() );
	}

	/**
	 * Teaser payload for every companion plugin, localized into
	 * `AcademyGlobal.integrations`. Each entry = static definition + live
	 * `active` / `installed` state + its `key`.
	 *
	 * @return array<string, array>
	 */
	public static function get_teaser_data(): array {
		$data = array();
		foreach ( self::definitions() as $key => $def ) {
			$data[ $key ] = array_merge(
				$def,
				array(
					'key'       => $key,
					'active'    => Helper::is_plugin_active( $def['basename'] ),
					'installed' => (bool) Helper::is_plugin_installed( $def['basename'] ),
				)
			);
		}

		/**
		 * Filter the full integrations teaser payload (with live state).
		 *
		 * @param array $data Teaser data keyed by plugin.
		 */
		return apply_filters( 'academy/admin/integrations', $data );
	}

	/**
	 * Download, install and activate a companion plugin — the one-click path.
	 *
	 * Resolved from $key against the catalog, so the package URL is never taken
	 * from the client. Tries wordpress.org (by slug) first and falls back to the
	 * Kodezen store URL. Idempotent: active → noop, installed → activate,
	 * otherwise download + activate.
	 *
	 * @param string $key Catalog key.
	 * @return array{status:string, message:string, key:string}|\WP_Error
	 */
	public static function install_and_activate( string $key ) {
		$definitions = self::definitions();
		if ( ! isset( $definitions[ $key ] ) ) {
			return new \WP_Error( 'academy_unknown_integration', __( 'Unknown plugin.', 'academy' ), array( 'status' => 400 ) );
		}

		$def = $definitions[ $key ];
		if ( 'install' !== $def['kind'] ) {
			return new \WP_Error( 'academy_integration_unavailable', __( 'This integration is not available to install yet.', 'academy' ), array( 'status' => 400 ) );
		}

		if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
			return new \WP_Error( 'academy_cannot_install', __( 'You do not have permission to install plugins.', 'academy' ), array( 'status' => 403 ) );
		}

		$basename = $def['basename'];
		$name     = $def['name'];

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		if ( Helper::is_plugin_active( $basename ) ) {
			return array(
				'status' => 'active',
				'key' => $key,
				'message' => sprintf( /* translators: %s: plugin name. */ __( '%s is already active.', 'academy' ), $name )
			);
		}

		if ( ! Helper::is_plugin_installed( $basename ) ) {
			$url = '';

			// Prefer wordpress.org when the plugin is listed there.
			$api = plugins_api( 'plugin_information', array(
				'slug'   => $def['slug'],
				'fields' => array( 'sections' => false ),
			) );
			if ( ! is_wp_error( $api ) && ! empty( $api->download_link ) ) {
				$url = $api->download_link;
			} else {
				$url = self::store_download_url( $key, $def['slug'] );
			}

			if ( ! WP_Filesystem() ) {
				return new \WP_Error( 'academy_fs_unavailable', __( 'WordPress could not access the filesystem. Please install the plugin manually.', 'academy' ), array( 'status' => 500 ) );
			}

			$skin     = new \Automatic_Upgrader_Skin();
			$upgrader = new \Plugin_Upgrader( $skin );
			$result   = $upgrader->install( $url );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
			if ( true !== $result ) {
				return new \WP_Error( 'academy_install_failed', __( 'The plugin could not be installed. Please install it manually.', 'academy' ), array( 'status' => 500 ) );
			}

			wp_clean_plugins_cache();
		}//end if

		if ( ! Helper::is_plugin_installed( $basename ) ) {
			return new \WP_Error( 'academy_install_missing', __( 'The plugin was downloaded but its main file was not found.', 'academy' ), array( 'status' => 500 ) );
		}

		$activated = activate_plugin( $basename );
		if ( is_wp_error( $activated ) ) {
			return $activated;
		}

		return array(
			'status'  => 'installed',
			'key'     => $key,
			'message' => sprintf( /* translators: %s: plugin name. */ __( '%s installed and activated.', 'academy' ), $name ),
		);
	}
}
