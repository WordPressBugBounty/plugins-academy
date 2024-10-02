<?php
namespace Academy\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tools {

	private function get_memory_limit() {
		// WP memory limit.
		$wp_memory_limit = WP_MEMORY_LIMIT;
		if ( function_exists( 'memory_get_usage' ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			$wp_memory_limit = max( $wp_memory_limit, @ini_get( 'memory_limit' ) );
		}
		return $wp_memory_limit;
	}

	private function get_curl_version() {
		$curl_version = '';
		if ( function_exists( 'curl_version' ) ) {
			$curl_version = curl_version();
			$curl_version = $curl_version['version'] . ', ' . $curl_version['ssl_version'];
		}
		return $curl_version;
	}

	public function get_wordpress_environment_status() {
		return array(
			'home_url'                  => get_option( 'home' ),
			'site_url'                  => get_option( 'siteurl' ),
			'wp_version'                => get_bloginfo( 'version' ),
			'academy_version'            => ACADEMY_VERSION,
			'wp_multisite'              => is_multisite(),
			'wp_memory_limit'           => $this->get_memory_limit(),
			'wp_debug_mode'             => ( defined( 'WP_DEBUG' ) && WP_DEBUG ),
			'wp_cron'                   => ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ),
			'language'                  => get_locale(),
			'external_object_cache'     => wp_using_ext_object_cache(),
		);
	}

	public function get_server_environment_status() {
		// phpcs:disable
		global $wpdb;
		return array(
			'server_info'               => isset( $_SERVER['SERVER_SOFTWARE'] ) ? wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) : '',
			'php_version'               => phpversion(),
			'php_post_max_size'         => @ini_get( 'post_max_size' ),
			'php_max_execution_time'    => @ini_get( 'max_execution_time' ),
			'php_max_input_vars'        => @ini_get( 'max_input_vars' ),
			'curl_version'              => $this->get_curl_version(),
			'suhosin_installed'         => extension_loaded( 'suhosin' ),
			'max_upload_size'           => wp_max_upload_size(),
			'mysql_version'             => $wpdb->db_version(),
			'default_timezone'          => date_default_timezone_get(),
			'fsockopen_or_curl_enabled' => ( function_exists( 'fsockopen' ) || function_exists( 'curl_init' ) ),
			'soapclient_enabled'        => class_exists( 'SoapClient' ),
			'domdocument_enabled'       => class_exists( 'DOMDocument' ),
			'gzip_enabled'              => is_callable( 'gzopen' ),
			'mbstring_enabled'          => extension_loaded( 'mbstring' ),
		);
		// phpcs:enable
	}
}

