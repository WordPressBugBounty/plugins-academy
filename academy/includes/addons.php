<?php

namespace Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Helper;
use AcademyStoreEngine\Storeengine;
use AcademyEasyContentManager\EasyContentManager;

class Addons {

	/**
	 * Option listing the add-ons switched off because a plugin they need was
	 * deactivated, so reactivating it can switch them back on.
	 */
	const SWITCHED_OFF_OPTION = 'academy_addons_switched_off_for_plugins';

	/**
	 * Plugins deactivated so far in this request, mapped to whether it was
	 * network-wide. `deactivated_plugin` fires before WordPress saves the new
	 * active-plugins list (and once per plugin in a bulk deactivation), so
	 * until then these still read as active.
	 *
	 * @var array<string, bool>
	 */
	private static $deactivated_plugins = array();

	public static function init() {
		$self = new self();
		// Load all addons
		$self->addons_loader();
		// Addons Ajax
		add_action( 'wp_ajax_academy/addons/get_all_addons', array( $self, 'get_all_addons' ) );
		add_action( 'wp_ajax_academy/addons/saved_addon_status', array( $self, 'saved_addon_status' ) );
		// check requirement
		add_action( 'academy/before_active_addon', array( $self, 'check_addon_pre_active_requirement' ), 10, 2 );
		// Not on a silent deactivation (e.g. while WordPress updates the
		// plugin), which this hook deliberately skips.
		add_action( 'deactivated_plugin', array( $self, 'on_plugin_deactivated' ), 10, 2 );
		// Fires after WordPress saves the new active-plugins list.
		add_action( 'activated_plugin', array( __CLASS__, 'restore_addons_switched_off_for_plugins' ) );
	}

	/**
	 * The plugins each add-on needs, as the Add-ons screen lists them
	 * (`required_plugin` / `required_any_plugin` in
	 * dev_academy/containers/backend-dashboard/pages/addons/index.js). Each
	 * inner list is one requirement that any of its plugins satisfies.
	 *
	 * @return array<string, string[][]>
	 */
	public static function get_required_plugins() {
		return apply_filters( 'academy/addons/required_plugins', array(
			'easy-digital-downloads'    => array( array( 'easy-digital-downloads/easy-digital-downloads.php' ) ),
			'woocommerce'               => array( array( 'woocommerce/woocommerce.php' ) ),
			'woocommerce-subscriptions' => array( array( 'woocommerce/woocommerce.php' ), array( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ),
			'course-bundle'             => array( array( 'woocommerce/woocommerce.php', 'storeengine/storeengine.php' ) ),
			'fluent-crm'                => array( array( 'fluent-crm/fluent-crm.php' ) ),
			'gamipress'                 => array( array( 'gamipress/gamipress.php' ) ),
			'paid-memberships-pro'      => array( array( 'paid-memberships-pro/paid-memberships-pro.php' ) ),
			'surecart'                  => array( array( 'surecart/surecart.php' ) ),
			'suremembers'               => array( array( 'suremembers/suremembers.php' ) ),
			'wishlist-member'           => array( array( 'wishlist-member/wpm.php', 'wishlist-member-x/wpm.php' ) ),
			'restrict-content-pro'      => array( array( 'restrict-content-pro/restrict-content-pro.php' ) ),
			'buddypress'                => array( array( 'buddypress/bp-loader.php' ) ),
			'buddyboss'                 => array( array( 'buddyboss-platform-release/bp-loader.php', 'buddyboss-platform/bp-loader.php' ) ),
			'fluent-community'          => array( array( 'fluent-community/fluent-community.php' ) ),
			'wpml'                      => array( array( 'sitepress-multilingual-cms/sitepress.php' ) ),
			'sendfox'                   => array( array( 'wp-sendfox/wp-sendfox.php' ) ),
			'member-press'              => array( array( 'memberpress/memberpress.php' ) ),
		) );
	}

	public function on_plugin_deactivated( $plugin, $network_deactivating ) {
		self::$deactivated_plugins[ $plugin ] = (bool) $network_deactivating;
		self::switch_off_addons_missing_plugins( array_keys( self::$deactivated_plugins ) );
	}

	/**
	 * Switches off every add-on that's on while a plugin it needs isn't
	 * active — the counterpart of refusing to switch one on without them, so
	 * deactivating the plugin leaves the add-on exactly as if it had been
	 * turned off on the Add-ons screen.
	 *
	 * @param string[]|null $only_requiring Only consider add-ons needing one of
	 *                                      these plugins; null for all of them.
	 */
	public static function switch_off_addons_missing_plugins( $only_requiring = null ) {
		$saved_addons = (array) json_decode( get_option( ACADEMY_ADDONS_SETTINGS_NAME, '{}' ), true );
		$switched_off = array();

		foreach ( self::get_required_plugins() as $addon_slug => $requirements ) {
			if ( empty( $saved_addons[ $addon_slug ] ) ) {
				continue;
			}
			if ( null !== $only_requiring && ! self::requires_any( $requirements, $only_requiring ) ) {
				continue;
			}
			if ( ! self::are_requirements_met( $requirements ) ) {
				$saved_addons[ $addon_slug ] = false;
				$switched_off[]              = $addon_slug;
			}
		}

		if ( ! $switched_off ) {
			return;
		}

		update_option( ACADEMY_ADDONS_SETTINGS_NAME, wp_json_encode( $saved_addons ) );
		// Later code in this request reads the add-ons from here.
		$GLOBALS['academy_addons'] = json_decode( wp_json_encode( $saved_addons ) );
		update_option(
			self::SWITCHED_OFF_OPTION,
			array_values( array_unique( array_merge( (array) get_option( self::SWITCHED_OFF_OPTION, array() ), $switched_off ) ) ),
			false
		);
		foreach ( $switched_off as $addon_slug ) {
			do_action( "academy/addons/deactivated_{$addon_slug}", false );
		}
	}

	/**
	 * Switches back on the add-ons switched off for a missing plugin, once
	 * every plugin they need is active again.
	 */
	public static function restore_addons_switched_off_for_plugins() {
		$switched_off = (array) get_option( self::SWITCHED_OFF_OPTION, array() );
		if ( ! $switched_off ) {
			return;
		}

		$saved_addons       = (array) json_decode( get_option( ACADEMY_ADDONS_SETTINGS_NAME, '{}' ), true );
		$required_plugins   = self::get_required_plugins();
		$switched_back_on   = array();
		$still_switched_off = array();

		foreach ( $switched_off as $addon_slug ) {
			// Switched back on by hand since — nothing left to restore.
			if ( ! empty( $saved_addons[ $addon_slug ] ) ) {
				continue;
			}
			if ( ! self::are_requirements_met( $required_plugins[ $addon_slug ] ?? array() ) ) {
				$still_switched_off[] = $addon_slug;
				continue;
			}
			$saved_addons[ $addon_slug ] = true;
			$switched_back_on[]          = $addon_slug;
		}

		update_option( self::SWITCHED_OFF_OPTION, $still_switched_off, false );
		if ( ! $switched_back_on ) {
			return;
		}

		update_option( ACADEMY_ADDONS_SETTINGS_NAME, wp_json_encode( $saved_addons ) );
		$GLOBALS['academy_addons'] = json_decode( wp_json_encode( $saved_addons ) );
		foreach ( $switched_back_on as $addon_slug ) {
			do_action( "academy/addons/activated_{$addon_slug}", true );
		}
	}

	private static function are_requirements_met( $requirements ) {
		foreach ( $requirements as $any_of ) {
			$is_met = false;
			foreach ( (array) $any_of as $basename ) {
				if ( self::is_required_plugin_active( $basename ) ) {
					$is_met = true;
					break;
				}
			}
			if ( ! $is_met ) {
				return false;
			}
		}
		return true;
	}

	private static function requires_any( $requirements, $basenames ) {
		foreach ( $requirements as $any_of ) {
			if ( array_intersect( $basenames, (array) $any_of ) ) {
				return true;
			}
		}
		return false;
	}

	private static function is_required_plugin_active( $basename ) {
		if ( ! array_key_exists( $basename, self::$deactivated_plugins ) ) {
			return Helper::is_plugin_active( $basename );
		}
		// Being deactivated in this request: it stays active only the other
		// way round — on this site itself after a network-wide deactivation,
		// or network-wide after deactivating it on this site.
		return self::$deactivated_plugins[ $basename ]
			? in_array( $basename, (array) get_option( 'active_plugins', array() ), true )
			: is_multisite() && is_plugin_active_for_network( $basename );
	}

	private function addons_loader() {
		$Autoload = Autoload::get_instance();
		$addons = apply_filters('academy/addons/loader_args', [
			'multi-instructor' => 'MultiInstructor',
			'quizzes' => 'Quizzes',
			'migration-tool' => 'MigrationTool',
			'webhooks' => 'Webhooks',
			'certificates' => 'Certificates',
			'easy-digital-downloads' => 'EasyDigitalDownloads',
			'woocommerce' => 'Woocommerce',
			'course-preview' => 'CoursePreview',
			'chatgpt' => 'Chatgpt',
			'gumlet-video' => 'GumletVideo',
			'notes' => 'Notes',
			'seeder' => 'Seeder',
			'quizpress' => 'Quizpress',
		]);

		foreach ( $addons as $addon_name => $addon_class_name ) {
			$addon_root_path = ACADEMY_ADDONS_DIR_PATH . $addon_name . '/';
			// Register the addon's root namespace and path.
			$addon_namespace = 'Academy' . $addon_class_name;
			$Autoload->add_namespace_directory( $addon_namespace, $addon_root_path );
			// Initialize the addon's main class.
			$class = $addon_namespace . '\\' . $addon_class_name;

			$class::init();
		}

		$Autoload->add_namespace_directory( 'AcademyStoreEngine', ACADEMY_ADDONS_DIR_PATH . 'storeengine/' );
		Storeengine::init();

		// Not a user-toggleable addon (no on/off entry in Settings > Addons) —
		// same reasoning as Storeengine above: always-on glue code, gated
		// purely on whether the sibling plugin is active.
		$Autoload->add_namespace_directory( 'AcademyEasyContentManager', ACADEMY_ADDONS_DIR_PATH . 'easy-content-manager/' );
		EasyContentManager::init();
	}

	public function get_all_addons() {
		check_ajax_referer( 'academy_nonce', 'security' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}
		$academy_addons = json_decode( get_option( ACADEMY_ADDONS_SETTINGS_NAME, '{}' ) );
		wp_send_json_success( $academy_addons );
	}

	public function saved_addon_status() {
		check_ajax_referer( 'academy_nonce', 'security' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}

		$addon_name = ( isset( $_POST['addon_name'] ) ? sanitize_text_field( wp_unslash( $_POST['addon_name'] ) ) : '' );
		$addon_slug = ( isset( $_POST['addon_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['addon_slug'] ) ) : '' );
		$status = (bool) ( isset( $_POST['status'] ) ? \Academy\Helper::sanitize_checkbox_field( sanitize_text_field( wp_unslash( $_POST['status'] ) ) ) : false );

		if ( empty( $addon_slug ) ) {
			wp_send_json_error( __( 'Addon Name missing', 'academy' ) );
		}

		if ( $status ) {
			$required_plugin = ( isset( $_POST['required_plugin'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['required_plugin'] ) ), true ) : '' );
			do_action( 'academy/before_active_addon', $addon_slug, $required_plugin );
			if ( $required_plugin && is_array( $required_plugin ) ) {
				foreach ( $required_plugin as $plugin ) {
					$active_plugins = get_option( 'active_plugins', array() );
					if ( 'Wishlist Member' === $plugin['plugin_name'] ) {
						$plugin['plugin_dir_path'] = in_array( $plugin['plugin_dir_path'], $active_plugins, true ) ? $plugin['plugin_dir_path'] : ( in_array( 'wishlist-member-x/wpm.php', $active_plugins, true ) ? 'wishlist-member-x/wpm.php' : '' );
					} elseif ( 'BuddyBoss' === $plugin['plugin_name'] ) {
						$plugin['plugin_dir_path'] = in_array( $plugin['plugin_dir_path'], $active_plugins, true ) ? $plugin['plugin_dir_path'] : ( in_array( 'buddyboss-platform/bp-loader.php', $active_plugins, true ) ? 'buddyboss-platform/bp-loader.php' : '' );
					}
					if ( ! Helper::is_plugin_active( sanitize_text_field( $plugin['plugin_dir_path'] ) ) ) {
						$error_message = sprintf( '%s Plugin is required to activate %s addon.', sanitize_text_field( $plugin['plugin_name'] ), $addon_name );
						wp_send_json_error( $error_message );
					}
				}
			}
		}

		// Saved Data
		$saved_addons = (array) json_decode( get_option( ACADEMY_ADDONS_SETTINGS_NAME ), true );
		$saved_addons[ $addon_slug ] = $status;
		update_option( ACADEMY_ADDONS_SETTINGS_NAME, wp_json_encode( $saved_addons ) );
		// Toggled by hand: that choice wins over switching it back on when its
		// plugin is reactivated.
		$switched_off = (array) get_option( self::SWITCHED_OFF_OPTION, array() );
		if ( in_array( $addon_slug, $switched_off, true ) ) {
			update_option( self::SWITCHED_OFF_OPTION, array_values( array_diff( $switched_off, array( $addon_slug ) ) ), false );
		}
		// Fire Addon Action
		if ( $status ) {
			do_action( "academy/addons/activated_{$addon_slug}", $status );
		} else {
			do_action( "academy/addons/deactivated_{$addon_slug}", $status );
		}
		// response
		wp_send_json_success( $saved_addons );
	}

	public function check_addon_pre_active_requirement( $addon_slug, $requirement ) {
		if ( 'certificates' === $addon_slug && Helper::is_plugin_active( 'academy-certificates/academy-certificates.php' ) ) {
			wp_send_json_error( esc_html__( 'To avoid conflicts, please first deactivate the Academy Certificate plugin.', 'academy' ) );
		}

		// Course Bundle has no monetization of its own - its admin page, REST
		// routes, and CPT only bootstrap when one of these is active (see
		// AcademyProCourseBundle\CourseBundle::init_addon() and
		// Helper::get_admin_menu_list()). Without this gate the addon could be
		// switched "on" with neither active, leaving admin.php?page=academy-course-bundle
		// unregistered and throwing WordPress's generic access-denied wall.
		if ( 'course-bundle' === $addon_slug && ! Helper::is_active_woocommerce() && ! class_exists( \StoreEngine::class ) ) {
			wp_send_json_error( esc_html__( 'WooCommerce or StoreEngine must be active to enable the Course Bundle addon.', 'academy' ) );
		}
	}
}
