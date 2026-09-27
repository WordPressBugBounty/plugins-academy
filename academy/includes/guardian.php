<?php
namespace Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Guardian / Family feature dispatcher.
 *
 * Adds a family layer to Academy: a guardian ↔ learner relationship (the
 * canonical store other plugins sync to), a family REST API, a guardian
 * dashboard tab and completion notifications.
 */
class Guardian {

	const DB_VERSION = '1.0';
	const ROUTES_VERSION = '1.1';
	const ADDON_SLUG = 'guardian';

	public static function init() {
		// Install the family table + guardian role the moment the addon is
		// toggled on from the Add-ons screen (mirrors the addon activation seam).
		add_action( 'academy/addons/activated_' . self::ADDON_SLUG, array( __CLASS__, 'on_activate' ) );

		// Guardian is an opt-in addon now — do nothing until it's enabled.
		if ( ! \Academy\Helper::get_addon_active_status( self::ADDON_SLUG ) ) {
			return;
		}

		self::maybe_install();
		self::maybe_flush_routes();
		Guardian\RestApi::init();
		Guardian\Notifications::init();
		// Must register unconditionally (not just on frontend requests) — the
		// `academy/frontend_dashboard_menu_items` filter it adds needs to be in
		// place on ANY request that flushes rewrite rules, including the
		// admin-context request that toggles this addon on. Skipping it in
		// admin context meant the "My Family" (`guardian`) dashboard route
		// never made it into the cached rewrite rules, 404ing the menu link.
		Guardian\Frontend::init();
	}

	/**
	 * Fired when the Guardian addon is activated — force the install regardless
	 * of the stored db version so the table + role always exist afterwards.
	 */
	public static function on_activate() {
		self::install();
		\Academy\Helper::flush_rewrite_rules();
		Options::set( Options::DB_VERSIONS, 'guardian_routes', self::ROUTES_VERSION );
	}

	/**
	 * Self-heal the guardian table + role once per version. Academy's activation
	 * installer creates these, but a plugin UPDATE (not a re-activation) would
	 * otherwise miss them, breaking guardian/parent linking.
	 */
	private static function maybe_install() {
		if ( Options::get( Options::DB_VERSIONS, 'guardian' ) === self::DB_VERSION ) {
			return;
		}
		self::install();
	}

	/**
	 * Self-heal the "My Family" dashboard route once per version. The `guardian`
	 * menu item (Guardian\Frontend::add_menu_item()) appears as soon as the addon
	 * is enabled, but its `.../guardian/` URL only resolves once WordPress's
	 * cached rewrite rules are flushed — so sites where the addon was toggled on
	 * before `on_activate()` flushed routes are stuck 404ing on that link until
	 * this runs.
	 */
	private static function maybe_flush_routes() {
		if ( Options::get( Options::DB_VERSIONS, 'guardian_routes' ) === self::ROUTES_VERSION ) {
			return;
		}
		\Academy\Helper::flush_rewrite_rules();
		Options::set( Options::DB_VERSIONS, 'guardian_routes', self::ROUTES_VERSION );
	}

	private static function install() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		global $wpdb;
		Database\CreateGuardianMapTable::up( $wpdb->prefix, $wpdb->get_charset_collate() );
		Classes\Role::add_guardian_role();
		Options::set( Options::DB_VERSIONS, 'guardian', self::DB_VERSION );
	}
}
