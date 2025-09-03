<?php
namespace AcademyWebhooks\Authorization;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_REST_Posts_Controller;
use WP_Error;

class WebHookController extends WP_REST_Posts_Controller {

	public function __construct() {
		parent::__construct( 'academy_webhook' );
	}

	public function get_item_permissions_check( $request ) {
		return $this->check_academy_course_action( $request, __FUNCTION__ );
	}

	public function get_items_permissions_check( $request ) {
		return $this->check_academy_course_action( $request, __FUNCTION__ );
	}

	public function create_item_permissions_check( $request ) {
		return $this->check_academy_course_action( $request, __FUNCTION__ );
	}

	public function update_item_permissions_check( $request ) {
		return $this->check_academy_course_action( $request, __FUNCTION__ );
	}

	public function delete_item_permissions_check( $request ) {
		return $this->check_academy_course_action( $request, __FUNCTION__ );
	}

	/**
	 * Check permission based on action and ownership.
	 */
	private function check_academy_course_action( $request, $perm_method ) {

		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'unauthorized', __( 'Unauthorized.', 'academy' ), [ 'status' => 401 ] );
		}

		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return new WP_Error( 'unauthorized', __( 'Unauthorized.', 'academy' ), [ 'status' => 401 ] );
	}

}
