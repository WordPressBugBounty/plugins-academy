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

	private function check_academy_course_action( $request, $perm_method ) {

		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'unauthorized', __( 'Unauthorized.', 'academy' ), [ 'status' => 401 ] );
		}

		// manage_academy_instructor is what Academy-Pro's role-permission addon
		// elevates a staff member to for the duration of an academy/v1 request
		// (see AcademyProRolePermission\Caps::elevate()) — the same base check
		// every other Academy REST controller accepts. Without it here, a staff
		// user holding the `academy.webhooks` permission could never pass this
		// hardcoded manage_options-only check, and RestGuard's per-permission
		// enforcement (which is supposed to be the actual gate) never even gets
		// a chance to run. Found via a live report: webhook create failed for
		// every staff member regardless of granted permissions.
		if ( current_user_can( 'manage_options' ) || current_user_can( 'manage_academy_instructor' ) ) {
			return true;
		}

		return new WP_Error( 'unauthorized', __( 'Unauthorized.', 'academy' ), [ 'status' => 401 ] );
	}
}
