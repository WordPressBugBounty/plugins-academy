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
			! current_user_can( 'edit_academy_quizzes' )
		) {
			return new WP_Error( 'unauthorized', __( 'Unauthorized.', 'academy' ), [ 'status' => 401 ] );
		}
		return true;
	}

	public function create_item_permissions_check( $request ) {
		if ( ! is_user_logged_in() ||
			! current_user_can( 'edit_academy_quizzes' )
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
	 *
	 * @param array  $request      Request payload.
	 * @param string $perm_method  Permission method to check.
	 * @return bool True if permission granted, false otherwise.
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
		if ( (int) $post->post_author !== $user_id && ! $this->is_assigned_instructor_for_quiz( $user_id, $post_id ) ) {
			return new WP_Error( 'forbidden', __( 'You are not the owner of this quiz.', 'academy' ), [ 'status' => 403 ] );
		}

		return true;
	}

	/**
	 * Non-admin instructors may edit quizzes in courses they were assigned to
	 * by an admin, not only ones they authored themselves — `post_author`
	 * alone doesn't reflect that. Mirrors the equivalent lesson-author
	 * fallback in `Academy\API\Lessons::resolve_lesson_author_restriction()`.
	 *
	 * @param int $user_id
	 * @param int $quiz_id
	 * @return bool
	 */
	private function is_assigned_instructor_for_quiz( $user_id, $quiz_id ) {
		$assigned_course_ids = \Academy\Helper::get_assigned_courses_ids_by_instructor_id( $user_id );
		foreach ( (array) $assigned_course_ids as $assigned_course_id ) {
			if ( \Academy\Helper::is_course_curriculum( (int) $assigned_course_id, $quiz_id, 'quiz' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Filter the collection query to limit non-admins to their own posts.
	 *
	 * @param array                 $prepared_args Prepared query arguments.
	 * @param \WP_REST_Request|null $request       Optional request object.
	 * @return array Modified query arguments.
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
