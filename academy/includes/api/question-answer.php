<?php
namespace Academy\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Core controller used to access comments via the REST API.
 *
 * @since 4.7.0
 *
 * @see WP_REST_Controller
 */
class QuestionAnswer extends \WP_REST_Controller {


	public static function init() {
		$self            = new self();
		$self->namespace = ACADEMY_PLUGIN_SLUG . '/v1';
		$self->rest_base = 'question_answer';
		add_action( 'rest_api_init', array( $self, 'register_routes' ) );
		add_filter( 'rest_post_dispatch', array( $self, 'add_x_wp_total_header' ), 10, 3 );
		add_action( 'wp_ajax_academy/insert_qa', array( $self, 'insert_qa' ) );
		add_action( 'wp_ajax_academy/update_qa', array( $self, 'update_qa' ) );
		add_action( 'wp_ajax_academy/delete_qa', array( $self, 'delete_qa' ) );
	}

	/**
	 * Registers the routes for comments.
	 *
	 * @since 4.7.0
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	public function get_items( $request ) {
		$course_id = $request->get_param( 'post' );
		$page = $request->get_param( 'page' );
		$status = $request->get_param( 'status' );
		$parent = $request->get_param( 'parent' );
		$per_page = $request->get_param( 'per_page' );
		$offset = ( $page - 1 ) * $per_page;

		$user_id = get_current_user_id();
		$instructor_course_ids = \Academy\Helper::get_assigned_courses_ids_by_instructor_id( $user_id );

		$response = [];

		if ( empty( $instructor_course_ids ) && ( empty( $course_id ) ) ) {
			return rest_ensure_response( [] );
		}

		$args = array(
			'status' => $status,
			'parent' => $parent,
			'offset' => $offset,
			'page' => $page,
			'number' => $per_page,
			'type' => 'academy_qa',
		);

		if ( isset( $course_id ) ) {
			$args['post_id'] = $course_id;
			unset( $args['parent'] );
		}

		if ( ! current_user_can( 'administrator' ) && ! empty( $instructor_course_ids ) ) {
			$args['post__in'] = $instructor_course_ids;
		}

		$comments = get_comments( $args );

		foreach ( $comments as $comment ) {
			$response[] = $this->prepare_comment_for_response( $comment );
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Checks if a given request has access to read comments.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		$is_administrator          = current_user_can( 'manage_options' );
		$manage_academy_instructor = current_user_can( 'manage_academy_instructor' );
		$user_ID                   = get_current_user_id();
		if ( ! empty( $request['post'] ) ) {
			foreach ( (array) $request['post'] as $post_id ) {
				$is_public = get_post_meta( $post_id, 'academy_course_type', true ) === 'public' ? true : false;
				$enrolled      = \Academy\Helper::is_enrolled( $post_id, $user_ID );
				$is_instructor = \Academy\Helper::is_instructor_of_this_course( $user_ID, $post_id );
				if ( $is_administrator || $enrolled || $is_instructor || $is_public ) {
					return true;
				}
			}
		}
		if ( $is_administrator || $manage_academy_instructor ) {
			return true;
		}
		return false;
	}


	public function get_collection_params() {
		return array(
			'page'     => array(
				'description'       => __( 'Current page of the collection.', 'academy' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'minimum'           => 1,
			),
			'per_page' => array(
				'description'       => __( 'Maximum number of items to be returned in result set.', 'academy' ),
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'post'   => array(
				'description'       => __( 'Course id is missing', 'academy' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'author'   => array(
				'description'       => __( 'Query by author id', 'academy' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'search'   => array(
				'description'       => __( 'Limit results to those matching a string.', 'academy' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}

	public function prepare_comment_for_response( $comment ) {
		if ( ! is_object( $comment ) ) {
			return [];
		}
		return [
			'id' => (int) $comment->comment_ID,
			'post' => (int) $comment->comment_post_ID,
			'_post' => [
				'title' => get_the_title( $comment->comment_post_ID ),
				'permalink' => get_the_permalink( $comment->comment_post_ID ),
			],
			'parent' => (int) $comment->comment_parent,
			'author' => (int) $comment->user_id,
			'author_name' => $comment->comment_author,
			'date' => $comment->comment_date,
			'date_gmt' => $comment->comment_date_gmt,
			'content' => [
				'rendered' => $comment->comment_content,
			],
			'status' => $comment->comment_approved,
			'type' => $comment->comment_type,
			'meta' => [
				'question_title' => get_comment_meta( (int) $comment->comment_ID, 'academy_question_title', true )
			]
		];
	}

	public function insert_qa() {
		check_ajax_referer( 'academy_nonce', 'security' );
		$course_id = (int) sanitize_text_field( $_POST['post'] );

		$is_administrator = current_user_can( 'administrator' );
		$is_instructor  = \Academy\Helper::is_instructor_of_this_course( get_current_user_id(), $course_id );
		$enrolled    = \Academy\Helper::is_enrolled( $course_id, get_current_user_id() );
		$is_public = get_post_meta( $course_id, 'academy_course_type', true ) === 'public' ? true : false;

		if ( $is_administrator || $is_instructor || $enrolled || $is_public ) {
			$parent = (int) sanitize_text_field( $_POST['parent'] );
			$content = sanitize_text_field( $_POST['content'] );
			$comment_approved = sanitize_text_field( $_POST['status'] );
			$title = sanitize_text_field( $_POST['title'] );
			$current_user = wp_get_current_user();

			$default_data = array(
				'comment_content'      => '',
				'comment_post_ID'      => '',
				'comment_parent'       => 0,
				'comment_approved'       => 'waiting_for_answer',
				'comment_type'       => 'academy_qa',
				'user_id'              => $current_user->ID,
				'comment_author'       => $current_user->user_login,
				'comment_author_email' => $current_user->user_email,
				'comment_author_url'   => $current_user->user_url,
				'comment_agent'        => 'AcademyLMS',
			);

			$comment_data = wp_parse_args( array(
				'comment_post_ID'      => $course_id,
				'comment_parent'      => $parent,
				'comment_content'      => $content,
				'comment_approved'     => $comment_approved,
				'user_id'              => $current_user->ID,
				'comment_author'       => $current_user->user_login,
				'comment_author_email' => $current_user->user_email,
				'comment_author_url'   => $current_user->user_url,
				'comment_meta'         => array(
					'academy_question_title' => $title
				)
			), $default_data );

			$comment_id = wp_insert_comment( $comment_data );

			$comment = $this->prepare_comment_for_response( get_comment( $comment_id ) );
			if ( 'waiting_for_answer' === $comment_approved ) {
				// insert question
				do_action( 'academy/frontend/insert_course_qa', $comment );
			} elseif ( 'answered' === $comment_approved ) {
				// reply question
				do_action( 'academy/frontend/insert_course_qa_answered', $comment );
			}

			wp_send_json_success( $comment );

		}//end if
		wp_send_json_error( __( 'Sorry, you have not permission to create QA.', 'academy' ) );
	}

	public function update_qa() {
		check_ajax_referer( 'academy_nonce', 'security' );

		if ( current_user_can( 'manage_academy_instructor' ) ) {
			$comment_ID = (int) sanitize_text_field( $_POST['id'] );
			$comment_approved = sanitize_text_field( $_POST['status'] );
			wp_update_comment( array(
				'comment_ID'                => $comment_ID,
				'comment_approved'          => $comment_approved,
			) );
			$comment = $this->prepare_comment_for_response( get_comment( $comment_ID ) );
			wp_send_json_success( $comment );
		}
		wp_send_json_error( __( 'Sorry, you have no permission to update QA.', 'academy' ) );
	}
	public function delete_qa() {
		check_ajax_referer( 'academy_nonce', 'security' );
		if ( current_user_can( 'manage_academy_instructor' ) ) {
			$comment_ID = (int) sanitize_text_field( $_POST['id'] );
			$force = sanitize_text_field( $_POST['force'] );
			$comment = $this->prepare_comment_for_response( get_comment( $comment_ID ) );
			$is_delete = wp_delete_comment( $comment_ID, $force );
			wp_send_json_success([
				'previous' => $comment,
				'status' => $is_delete
			]);
		}
		wp_send_json_error( __( 'Sorry, you have no permission to delete QA.', 'academy' ) );
	}

	public function add_x_wp_total_header( $response, $handler, $request ) {
		if ( '/' . $this->namespace . '/' . $this->rest_base === $request->get_route() ) {
			$total = 0;
			if ( ! current_user_can( 'manage_options' ) ) {
				$total = $this->get_total_number_of_qa( get_current_user_id() );
			} else {
				$total = $this->get_total_number_of_qa();
			}

			$response->header( 'x-wp-total', $total );
		}
		return $response;
	}

	private function get_total_number_of_qa( $user_id = 0 ) {
		global $wpdb;

		// Base query
		$query = "SELECT COUNT(comment_ID)
			FROM {$wpdb->comments}
			WHERE comment_type = %s
			AND comment_agent = %s
			AND comment_parent = %d";

		$query_params = array( 'academy_qa', 'AcademyLMS', 0 );

		if ( $user_id ) {
			$course_ids = \Academy\Helper::get_course_ids_by_instructor_id( $user_id );

			if ( ! empty( $course_ids ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $course_ids ), '%d' ) );
				$query .= " AND comment_post_ID IN ($placeholders)";
				$query_params = array_merge( $query_params, $course_ids );
			}
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_var( $wpdb->prepare( $query, $query_params ) );
	}

}
