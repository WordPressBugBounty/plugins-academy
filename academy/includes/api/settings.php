<?php

namespace Academy\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Admin\Settings\Base as BaseSettings;
use Academy\Admin\Settings\FormBuilder;
use Academy\Helper;
use AcademyProMailChimp\MailChimpService;
use WP_REST_Controller;
use WP_REST_Server;

class Settings extends WP_REST_Controller {

	/**
	 * Initialize hooks and option name
	 */
	public static function init() {
		$self            = new self();
		$self->namespace = ACADEMY_PLUGIN_SLUG . '/v1';
		$self->rest_base = 'settings';
		add_action( 'rest_api_init', array( $self, 'register_routes' ) );
		add_action( 'academy/admin/after_save_settings', array( $self, 'after_save_settings' ) );
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		$endpoint = '/settings/';

		register_rest_route(
			$this->namespace,
			$endpoint,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(),
				),
			)
		);
	}

	public function get_items( $request ) {
		$settings = BaseSettings::get_saved_data();
		if ( ! isset( $settings['form_builder'] ) ) {
			$settings['form_builder'] = FormBuilder::get_saved_data();
		}

		$response = apply_filters( 'academy/api/settings/get_settings', $settings );

		return rest_ensure_response( $response );
	}

	public function get_items_permissions_check( $request ) {
		return current_user_can( 'manage_options' ) || current_user_can( 'manage_academy_instructor' );
	}

	public function after_save_settings( $is_update ) {
		if ( $is_update ) {
			update_option( 'academy_flash_role_management', true );
		}
	}
}
