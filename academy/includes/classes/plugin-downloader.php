<?php
namespace Academy\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class PluginDownloader {
	public static function init() {
		$self = new self();
		add_action( 'wp_ajax_academy/check_installed_plugins', [ $self, 'check_installed_plugins' ] );
		add_action( 'wp_ajax_academy/install_storeengine', [ $self, 'install_storeengine_plugins' ] );
	}

	public function check_installed_plugins() {
		check_ajax_referer( 'academy_nonce', 'security' );

		$plugin_slug = isset( $_POST['plugin_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_slug'] ) ) : '';

		if ( empty( $plugin_slug ) ) {
			wp_send_json_error( [
				'message' => __( 'Invalid plugin slug.', 'academy' ),
			] );
		}

		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( [
				'message' => __( 'You do not have permission to install plugins.', 'academy' ),
			] );
		}

		$plugin_file = $plugin_slug . '/' . $plugin_slug . '.php';
		$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;

		if ( \Academy\Helper::is_plugin_active( $plugin_file ) ) {
			wp_send_json_success( [
				'message'      => __( 'Plugin is already active.', 'academy' ),
				'is_installed' => true,
				'is_active'    => true,
			] );
		}

		if ( file_exists( $plugin_path ) ) {
			wp_send_json_success( [
				'message'      => __( 'Plugin is already installed.', 'academy' ),
				'is_installed' => true,
				'is_active'    => false,
			] );
		}

		wp_send_json_error( [
			'message'      => __( 'Plugin is not installed.', 'academy' ),
			'is_installed' => false,
		] );
	}

	public function install_storeengine_plugins() {
		check_ajax_referer( 'academy_nonce', 'security' );

		$plugin_slug = isset( $_POST['plugin_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_slug'] ) ) : '';

		if ( empty( $plugin_slug ) ) {
			wp_send_json_error( [
				'message' => __( 'Invalid plugin slug.', 'academy' ),
			] );
		}

		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( [
				'message' => __( 'You do not have permission to install plugins.', 'academy' ),
			] );
		}

		$plugin_file = $plugin_slug . '/' . $plugin_slug . '.php';
		$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;

		if ( ! file_exists( $plugin_path ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			include_once ABSPATH . 'wp-admin/includes/file.php';
			include_once ABSPATH . 'wp-admin/includes/misc.php';
			include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			$api = plugins_api( 'plugin_information', [
				'slug'   => $plugin_slug,
				'fields' => [ 'sections' => false ],
			] );

			if ( is_wp_error( $api ) || empty( $api->download_link ) ) {
				wp_send_json_error( [
					'message' => __( 'Plugin download failed or plugin not found.', 'academy' ),
				] );
			}

			$upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
			$install_result = $upgrader->install( $api->download_link );

			if ( ! $install_result || ! file_exists( $plugin_path ) ) {
				wp_send_json_error( [
					'message' => __( 'Plugin installation failed.', 'academy' ),
				] );
			}
		}//end if

		$activation_result = activate_plugin( $plugin_file, '', false, true );

		if ( is_wp_error( $activation_result ) ) {
			wp_send_json_error( [
				'message' => $activation_result->get_error_message(),
				'status'  => 400,
			] );
		}

		wp_send_json_success( [
			'message'      => __( 'Plugin successfully installed and activated.', 'academy' ),
			'is_installed' => true,
		] );
	}

}
