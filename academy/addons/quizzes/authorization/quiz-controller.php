<?php
namespace AcademyQuizzes\Authorization;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_REST_Posts_Controller;
use WP_Error;
use WP_Rest_Response;

class QuizController extends WP_REST_Posts_Controller {

	public function __construct() {
		parent::__construct( 'academy_quiz' );
	}

	public function get_item_permissions_check( $request ) {
		return $this->check_academy_quiz_action( $request, __FUNCTION__ );
	}

	public function get_items_permissions_check( $request ) {
		if ( ! is_user_logged_in() ||
			! current_user_can( 'edit_academy_courses' )
		) {
			return new WP_Error( 'unauthorized', __( 'Unauthorized.', 'academy' ), [ 'status' => 401 ] );
		}
		return true;
	}

	public function create_item_permissions_check( $request ) {
		if ( ! is_user_logged_in() ||
			! current_user_can( 'edit_academy_courses' )
		) {
			return new WP_Error( 'unauthorized', __( 'Unauthorized.', 'academy' ), [ 'status' => 401 ] );
		}
		return true;
	}

	public function update_item_permissions_check( $request ) {
		return $this->check_academy_quiz_action( $request, __FUNCTION__ );
	}

	public function delete_item_permissions_check( $request ) {
		return $this->check_academy_quiz_action( $request, __FUNCTION__ );
	}

	/**
	 * Check permission based on action and ownership.
	 */
	private function check_academy_quiz_action( $request, $perm_method ) {

		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		if ( ! parent::{$perm_method}( $request ) ) {
			return new WP_Error( 'unauthorized', __( 'Unauthorized.', 'academy' ), [ 'status' => 404 ] );
		}

		$post_id = $request['id'];
		$post    = get_post( $post_id );

		if ( ! $post || $post->post_type !== $this->post_type ) {
			return new WP_Error( 'invalid_post', __( 'Invalid quiz ID.', 'academy' ), [ 'status' => 404 ] );
		}

		$user_id = get_current_user_id();
		if ( (int) $post->post_author !== $user_id ) {
			return new WP_Error( 'forbidden', __( 'You are not the owner of this quiz.', 'academy' ), [ 'status' => 403 ] );
		}

		return true;
	}

	/**
	 * Filter the collection query to limit non-admins to their own posts.
	 */
	public function prepare_items_query( $prepared_args = [], $request = null ) {
		$prepared_args = parent::prepare_items_query( $prepared_args, $request );

		if ( ! current_user_can( 'manage_options' ) ) {
			unset( $prepared_args['author__in'] );
			$prepared_args['author'] = get_current_user_id();
		}
		return $prepared_args;
	}
}
