<?php
namespace Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Academy\Helper;

class Addons {
	public static function init() {
		$self = new self();
		// Load all addons
		$self->addons_loader();
		// Addons Ajax
		add_action( 'wp_ajax_academy/addons/get_all_addons', array( $self, 'get_all_addons' ) );
		add_action( 'wp_ajax_academy/addons/saved_addon_status', array( $self, 'saved_addon_status' ) );
		// check requirement
		add_action( 'academy/before_active_addon', array( $self, 'check_addon_pre_active_requirement' ), 10, 2 );
	}

	private function addons_loader() {
		$Autoload = Autoload::get_instance();
		$addons = apply_filters('academy/addons/loader_args', [
			'multi-instructor' => 'MultiInstructor',
			'quizzes'          => 'Quizzes',
			'migration-tool'   => 'MigrationTool',
			'webhooks'         => 'Webhooks',
			'certificates'     => 'Certificates',
			'easy-digital-downloads' => 'EasyDigitalDownloads',
			'woocommerce'      => 'Woocommerce',
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

		$addon_name = ( isset( $_POST['addon_name'] ) ? sanitize_text_field( $_POST['addon_name'] ) : '' );
		$addon_slug = ( isset( $_POST['addon_slug'] ) ? sanitize_text_field( $_POST['addon_slug'] ) : '' );
		$status = (bool) ( isset( $_POST['status'] ) ? \Academy\Helper::sanitize_checkbox_field( $_POST['status'] ) : false );

		if ( empty( $addon_slug ) ) {
			wp_send_json_error( __( 'Addon Name missing', 'academy' ) );
		}

		if ( $status ) {
			$required_plugin = ( isset( $_POST['required_plugin'] ) ? json_decode( stripslashes( $_POST['required_plugin'] ), true ) : '' );
			do_action( 'academy/before_active_addon', $addon_slug, $required_plugin );
			if ( $required_plugin && is_array( $required_plugin ) ) {
				foreach ( $required_plugin as $plugin ) {
					if ( 'Wishlist Member' === $plugin['plugin_name'] ) {
						$active_plugins = get_option( 'active_plugins', array() );
						$plugin['plugin_dir_path'] = in_array( $plugin['plugin_dir_path'], $active_plugins, true ) ? $plugin['plugin_dir_path'] : ( in_array( 'wishlist-member-x/wpm.php', $active_plugins, true ) ? 'wishlist-member-x/wpm.php' : '' );
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
	}
}
