<?php
namespace AcademyCertificates\Authorization;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_REST_Posts_Controller;
use WP_Error;

class CertificateController extends WP_REST_Posts_Controller {

	public function __construct() {
		parent::__construct( 'academy_certificate' );
	}

	public function get_item_permissions_check( $request ) {
		return $this->check_academy_course_action( $request, __FUNCTION__, true );
	}

	public function get_items_permissions_check( $request ) {
		return $this->check_academy_course_action( $request, __FUNCTION__, true );
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

	private function check_academy_course_action( $request, $perm_method, bool $pass = false ) {

		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'unauthorized', __( 'Unauthorized.', 'academy' ), [ 'status' => 401 ] );
		}

		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		if ( ! parent::{$perm_method}( $request ) ) {
			return new WP_Error( 'unauthorized', __( 'Unauthorized.', 'academy' ), [ 'status' => 401 ] );
		}

		return $pass;
	}

}
