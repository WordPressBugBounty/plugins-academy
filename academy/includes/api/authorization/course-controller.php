<?php
namespace Academy\API\Authorization;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_REST_Posts_Controller;
use WP_Error;

class CourseController extends WP_REST_Posts_Controller {

	public function __construct() {
		parent::__construct( 'academy_courses' );
	}

	public function get_item_permissions_check( $request ) {
		return $this->check_academy_course_action( $request, __FUNCTION__ );
	}

	public function get_items_permissions_check( $request ) {
		if ( ! is_user_logged_in()
		) {
			return new WP_Error( 'unauthorized', __( 'Unauthorized.', 'academy' ), [ 'status' => 401 ] );
		}
		return parent::get_items_permissions_check( $request );
	}

	public function create_item_permissions_check( $request ) {
		if ( ! is_user_logged_in()
		) {
			return new WP_Error( 'unauthorized', __( 'Unauthorized.', 'academy' ), [ 'status' => 401 ] );
		}
		return parent::create_item_permissions_check( $request );
	}

	public function update_item_permissions_check( $request ) {
		return $this->check_academy_course_action( $request, __FUNCTION__ );
	}

	public function delete_item_permissions_check( $request ) {
		return $this->check_academy_course_action( $request, __FUNCTION__ );
	}

	private function check_academy_course_action( $request, $perm_method ) {

		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		// A user granted the CPT's own "act on any course" capability (e.g. by
		// the Role & Permission pro addon) bypasses the per-course ownership
		// check below, the same way WP core's map_meta_cap already treats
		// edit_others_* for standard post types. academy_courses is registered
		// with map_meta_cap: false, so that bypass doesn't happen automatically
		// here — this restores it for any grantor of the capability, not just
		// one specific addon.
		if ( current_user_can( 'edit_others_academy_courses' ) ) {
			return true;
		}

		if ( ! parent::{$perm_method}( $request ) ) {
			return new WP_Error( 'unauthorized', __( 'Unauthorized.', 'academy' ), [ 'status' => 404 ] );
		}

		$post_id = $request['id'];
		$post    = get_post( $post_id );

		if ( ! $post || $post->post_type !== $this->post_type ) {
			return new WP_Error( 'invalid_post', __( 'Invalid course ID.', 'academy' ), [ 'status' => 404 ] );
		}

		$user_id = get_current_user_id();

		// An instructor assigned to this course by an admin (not just its
		// original author) may view/edit it the same as the author — the
		// assignment itself is the grant, regardless of which HTTP method
		// this particular request happens to use. Deletion stays
		// owner/admin-only: co-instructor status isn't consent to remove
		// the course.
		$course_ids = \Academy\Helper::get_assigned_courses_ids_by_instructor_id( $user_id );
		if ( 'DELETE' !== $request->get_method() && in_array( (int) $post->ID, array_map( 'intval', (array) $course_ids ), true ) ) {
			return true;
		}

		if ( (int) $post->post_author !== $user_id ) {
			return new WP_Error( 'forbidden', __( 'You are not the owner of this course.', 'academy' ), [ 'status' => 403 ] );
		}

		return true;
	}

	public function prepare_items_query( $prepared_args = [], $request = null ) {
		$prepared_args = parent::prepare_items_query( $prepared_args, $request );

		if ( ! current_user_can( 'manage_options' ) ) {
			if ( 'GET' === $request->get_method() ) {
				// The "my courses" list (client explicitly scopes to its own
				// author id) must also include courses this user is an
				// assigned/co-instructor on, not only ones they authored —
				// mirrors the same admin-assigned-instructor case handled
				// above for a single course. The REST `author` collection
				// param is schema'd as an array (it accepts a comma-separated
				// list), so it always arrives as an array here even for a
				// single id — never compare it to an int directly.
				$user_id = get_current_user_id();
				$requested_author = (array) $request->get_param( 'author' );
				if ( 1 === count( $requested_author ) && (int) $requested_author[0] === $user_id ) {
					global $wpdb;
					$assigned_ids = array_map( 'intval', \Academy\Helper::get_assigned_courses_ids_by_instructor_id( $user_id ) );
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$authored_ids = array_map( 'intval', $wpdb->get_col( $wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_author = %d",
						$this->post_type,
						$user_id
					) ) );
					$allowed_ids = array_unique( array_merge( $assigned_ids, $authored_ids ) );
					unset( $prepared_args['author'], $prepared_args['author__in'] );
					$prepared_args['post__in'] = $allowed_ids ? $allowed_ids : [ 0 ];
				}
				return $prepared_args;
			}//end if
			unset( $prepared_args['author__in'] );
			$prepared_args['author'] = get_current_user_id();
		}//end if

		return $prepared_args;
	}
}
