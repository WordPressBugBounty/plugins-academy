<?php
namespace AcademyQuizzes\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyQuizzes\Classes\Query;
use AcademyQuizzes\API\Schema\QuizAttemptsSchema;

class QuizAttempts extends \WP_REST_Controller {

	use QuizAttemptsSchema;

	public static function init() {
		$self            = new self();
		$self->namespace = ACADEMY_PLUGIN_SLUG . '/v1';
		$self->rest_base = 'quiz_attempts';
		add_action( 'rest_api_init', array( $self, 'register_routes' ) );
		add_filter( 'rest_post_dispatch', array( $self, 'add_x_wp_total_header' ), 10, 3 );
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => $this->get_item_schema(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		$get_item_args = array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => esc_html__( 'Unique identifier for the object.', 'academy' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => $get_item_args,
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => $this->get_item_schema(),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_permissions_check' ),
					'args'                => array(
						'force' => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => esc_html__( 'Whether to bypass Trash and force deletion.', 'academy' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),

			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/get_student_quiz_attempt_details',
			array(
				'args'   => array(
					'course_id' => array(
						'description' => esc_html__( 'Course ID.', 'academy' ),
						'type'        => 'integer',
					),
					'student_id' => array(
						'description' => esc_html__( 'Student ID.', 'academy' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_student_quiz_attempt_details' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => $get_item_args,
				),
				'schema' => array( $this, 'get_public_item_schema' ),

			)
		);

		// Get All Quiz Attempts
		$quiz_object             = get_post_type_object( 'academy_quiz' );
		$quiz_rest_base = ! empty( $quiz_object->rest_base ) ? $quiz_object->rest_base : $quiz_object->name;
		register_rest_route(
			$this->namespace,
			'/' . $quiz_rest_base . '/(?P<id>[\d]+)/quiz_attempts',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_quiz_attempts' ),
				'permission_callback' => array( $this, 'quiz_attempts_permissions_check' ),
			)
		);
	}


	public function permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden_context',
				esc_html__( 'Sorry, you are not allowed to get quiz attempt answers.', 'academy' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		$course_id = $request->get_param( 'course_id' );
		$enrolled    = \Academy\Helper::is_enrolled( $course_id, get_current_user_id() );
		$is_public = \Academy\Helper::is_public_course( $course_id );
		if ( current_user_can( 'manage_academy_instructor' ) || $enrolled || $is_public || current_user_can( 'read_academy_course' ) ) {
			return true;
		}

		return new \WP_Error(
			'rest_forbidden_context',
			esc_html__( 'Sorry, you are not allowed to get quiz attempt answers.', 'academy' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	public function delete_permissions_check() {
		if ( ! current_user_can( 'manage_academy_instructor' ) ) {
			return new \WP_Error(
				'rest_forbidden_context',
				esc_html__( 'Sorry, you are not allowed to delete quiz attempt.', 'academy' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	public function quiz_attempts_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden_context',
				esc_html__( 'Sorry, you are not allowed to get quiz attempt answers.', 'academy' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		$course_id = $request->get_param( 'course_id' );
		$is_public = \Academy\Helper::get_course_type( $course_id ) === 'public' ? true : false;
		$is_administrator = current_user_can( 'administrator' );
		$is_instructor  = \Academy\Helper::is_instructor_of_this_course( get_current_user_id(), $course_id );
		$enrolled    = \Academy\Helper::is_enrolled( $course_id, get_current_user_id() );
		if ( $is_administrator || $is_instructor || $enrolled || $is_public ) {
			return true;
		}
		return false;
	}


	/**
	 * Retrieves a collection of posts.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or \WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$args = $request->get_params();
		$page = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );
		$offset = ( $page - 1 ) * $per_page;
		$args['offset'] = $offset;
		$attempts = [];
		if ( current_user_can( 'manage_options' ) ) {
			// Site administrators may read every attempt.
			$attempts = Query::get_quiz_attempts( $args );
		} elseif ( current_user_can( 'manage_academy_instructor' ) ) {
			// Instructors are scoped to attempts on their own courses.
			$attempts = Query::get_quiz_attempts_for_instructors( $args );
		} else {
			// Everyone else (e.g. subscribers/students) may only read their own attempts.
			// Force the user scope on both the main query (restrict_user_id) and the
			// quiz_id sub-branch (user_id) so a supplied user_id cannot widen access.
			$args['restrict_user_id'] = get_current_user_id();
			$args['user_id']          = get_current_user_id();
			$attempts = Query::get_quiz_attempts( $args );
		}

		$data = array();
		if ( empty( $attempts ) || ( is_array( $attempts ) && 0 === count( $attempts ) ) ) {
			return rest_ensure_response( $data );
		}

		foreach ( $attempts as $attempt ) {
			$response = $this->rest_prepare_item( $attempt, $request );
			$data[] = $this->rest_prepare_for_collection( $response );
		}
		$data = apply_filters( 'academy_quizzes/frontend/quiz_attempts', $data, $args );
		return rest_ensure_response( $data );
	}

	public function get_item( $request ) {
		$id = $request->get_param( 'id' );
		$attempt = Query::get_quiz_attempt( $id );
		if ( empty( $attempt ) ) {
			return rest_ensure_response( [] );
		}
		if ( ! $this->can_access_attempt( $attempt ) ) {
			return new \WP_Error(
				'rest_forbidden_context',
				esc_html__( 'Sorry, you are not allowed to view this quiz attempt.', 'academy' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		$response = $this->rest_prepare_item( $attempt, $request );
		return rest_ensure_response( $response );
	}

	/**
	 * Determine whether the current user is allowed to access a specific attempt row.
	 *
	 * Access is granted to site administrators, the instructor of the attempt's
	 * course, and the user who owns the attempt. This is the ownership check that
	 * the per-request `course_id` permission callback cannot enforce, since the
	 * attacker controls that parameter.
	 *
	 * @param object $attempt Attempt row.
	 * @return bool
	 */
	protected function can_access_attempt( $attempt ) {
		if ( empty( $attempt ) ) {
			return false;
		}

		$current_user_id = get_current_user_id();

		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		if ( (int) $attempt->user_id === (int) $current_user_id ) {
			return true;
		}

		if ( \Academy\Helper::is_instructor_of_this_course( $current_user_id, (int) $attempt->course_id ) ) {
			return true;
		}

		return false;
	}



	/**
	 * Creates a single post.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or \WP_Error object on failure.
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['id'] ) ) {
			return new \WP_Error(
				'rest_post_exists',
				esc_html__( 'Cannot create existing question.', 'academy' ),
				array( 'status' => 400 )
			);
		}

		$prepared_attempt = $this->prepare_item_for_database( $request );
		do_action( 'academy_quizzes/api/before_quiz_attempt_start', $prepared_attempt );
		$attempt_id = Query::quiz_attempt_insert( wp_unslash( (array) $prepared_attempt ) );
		$attempt = Query::get_quiz_attempt( $attempt_id );
		$response = $this->rest_prepare_item( $attempt, $request );
		do_action( 'academy_quizzes/api/after_quiz_attempt_start', $attempt );
		return rest_ensure_response( $response );
	}

	public function update_item( $request ) {
		$params = $request->get_params();

		// Only the attempt owner (or an administrator/course instructor) may finalize
		// or modify an attempt. Without this, any enrolled user could tamper with
		// another user's attempt by passing its attempt_id.
		$existing_attempt = Query::get_quiz_attempt( isset( $params['attempt_id'] ) ? $params['attempt_id'] : 0 );
		if ( empty( $existing_attempt ) || ! $this->can_access_attempt( $existing_attempt ) ) {
			return new \WP_Error(
				'rest_forbidden_context',
				esc_html__( 'Sorry, you are not allowed to update this quiz attempt.', 'academy' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		do_action( 'academy_quizzes/api/before_quiz_attempt_finished', $params );
		$total_questions_marks = Query::get_total_questions_marks_by_attempt_id( $params['attempt_id'] );
		$total_earned_marks = Query::get_quiz_attempt_answers_earned_marks( get_current_user_id(), $params['attempt_id'] );
		$params['total_marks'] = $total_questions_marks;
		$params['earned_marks'] = $total_earned_marks;
		$passing_grade = (int) get_post_meta( $params['quiz_id'], 'academy_quiz_passing_grade', true );
		$earned_percentage  = \Academy\Helper::calculate_percentage( $total_questions_marks, $total_earned_marks );
		$params['attempt_status'] = ( $earned_percentage >= $passing_grade ? 'passed' : 'failed' );
		if ( 'failed' === $params['attempt_status'] && Query::is_required_manually_reviewed( $params['quiz_id'] ) ) {
			$params['attempt_status'] = 'pending';
		}
		$params['attempt_info'] = array(
			'total_correct_answers' => Query::get_total_quiz_attempt_correct_answers( $params['attempt_id'] )
		);
		$prepare_attempt = $this->prepare_item_for_database( $params );
		$attempt_id = Query::quiz_attempt_insert( wp_unslash( (array) $prepare_attempt ) );
		$attempt = Query::get_quiz_attempt( $attempt_id );
		$response = $this->rest_prepare_item( $attempt, $request );
		do_action( 'academy/frontend/quiz_attempt_status_' . $attempt->attempt_status, $attempt );
		do_action( 'academy_quizzes/api/after_quiz_attempt_finished', $attempt );
		return new \WP_REST_Response( $response, 200 );
	}

	public function delete_item( $request ) {
		$attempt_id = $request->get_param( 'id' );

		// Scope deletion to the attempt's owner/course. delete_permissions_check only
		// verifies the caller is an instructor, not that the attempt is theirs to delete.
		$existing_attempt = Query::get_quiz_attempt( $attempt_id );
		if ( empty( $existing_attempt ) || ! $this->can_access_attempt( $existing_attempt ) ) {
			return new \WP_Error(
				'rest_forbidden_context',
				esc_html__( 'Sorry, you are not allowed to delete this quiz attempt.', 'academy' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		Query::delete_quiz_attempt( $attempt_id );
		do_action( 'academy_quizzes/api/after_delete_quiz_attempt', $attempt_id );
		return new \WP_REST_Response( $attempt_id, 200 );
	}

	public static function get_quiz_attempts( $request ) {
		$quiz_id     = $request['id'];
		$course_id = $request->get_param( 'course_id' );
		$results = Query::get_quiz_attempt_details_by_quiz_id([
			'per_page' => 10,
			'offset' => 0,
			'quiz_id' => $quiz_id,
			'course_id' => $course_id,
			'user_id' => get_current_user_id()
		]);
		$results = apply_filters( 'academy_quizzes/api/before_quiz_attempts', $results );
		return new \WP_REST_Response( $results, 200 );
	}

	public function get_student_quiz_attempt_details( $request ) {
		$attempt_id = $request->get_param( 'id' );

		// Resolve the real owner from the attempt row rather than trusting the
		// caller-supplied user_id/course_id. Authorization is then decided against
		// the current user (owner, course instructor, or administrator).
		$attempt = \AcademyQuizzes\Classes\Query::get_quiz_attempt( $attempt_id );

		if ( ! empty( $attempt ) && $this->can_access_attempt( $attempt ) ) {
			$student_id = (int) $attempt->user_id;
			$prepare_response = [];
			$attempt_details = \AcademyQuizzes\Classes\Query::get_quiz_attempt_details( $attempt_id, $student_id );
			$quiz_id = $attempt->quiz_id;
			$is_enable_skip_question = get_post_meta( $quiz_id, 'academy_quiz_skip_question_showing', true );
			if ( $is_enable_skip_question ) {
				$skip_questions = \AcademyQuizzes\Classes\Query::get_quiz_attempt_skip_questions( $attempt_id, $student_id, $quiz_id );
				$attempt_details = array_merge( $attempt_details, $skip_questions );
			}
			foreach ( $attempt_details as $attempt_item ) {
				$attempt_item->given_answer = \AcademyQuizzes\Helper::prepare_given_answer( $attempt_item->question_type, $attempt_item );
				$attempt_item->is_correct = (bool) $attempt_item->is_correct;
				$attempt_item->correct_answer = \AcademyQuizzes\Helper::prepare_correct_answer( $attempt_item->question_type, $attempt_item );
				$attempt_item->question_title = html_entity_decode( $attempt_item->question_title );
				$attempt_item->question_image_url = ! empty( $attempt_item->question_image_id ) ? wp_get_attachment_url( $attempt_item->question_image_id ) : '';
				$attempt_answer_id = $attempt_item->attempt_answer_id ? $attempt_item->attempt_answer_id : $attempt_item->question_id;
				$attempt_item->is_skipped_question = (bool) $attempt_item->attempt_answer_id ? false : true;
				$prepare_response[ $attempt_answer_id ] = $attempt_item;
			}

			return new \WP_REST_Response( $prepare_response, 200 );
		}//end if
		return new \WP_REST_Response( array( 'error' => esc_html__( 'Access Denied', 'academy' ) ), 403 );
	}

	protected function rest_prepare_item( $attempt, $request ) {
		$data = array();
		$schema = $this->get_public_item_schema();

		if ( isset( $schema['properties']['attempt_id'] ) ) {
			$data['attempt_id'] = (int) $attempt->attempt_id;
		}

		if ( isset( $schema['properties']['course_id'] ) ) {
			$data['course_id'] = (int) $attempt->course_id;
			$data['_course'] = array(
				'title' => html_entity_decode( get_the_title( $attempt->course_id ) ),
				'permalink' => get_the_permalink( $attempt->course_id )
			);
		}

		if ( isset( $schema['properties']['quiz_id'] ) ) {
			$data['quiz_id'] = (int) $attempt->quiz_id;
			$data['_quiz'] = array(
				'title' => html_entity_decode( get_the_title( $attempt->quiz_id ) ),
			);
		}

		if ( isset( $schema['properties']['user_id'] ) ) {
			$data['user_id'] = (int) $attempt->user_id;
			$user_data = get_userdata( $attempt->user_id );

			if ( $user_data ) {
				$user = [
					'ID'            => $user_data->ID,
					'user_nicename' => $user_data->user_nicename,
					'display_name'  => $user_data->display_name,
					'user_registered' => $user_data->user_registered,
					'admin_permalink' => get_edit_user_link( $attempt->user_id ),
				];

				$data['_user'] = $user;
			}
		}

		if ( isset( $schema['properties']['total_questions'] ) ) {
			$data['total_questions'] = (int) $attempt->total_questions;
		}

		if ( isset( $schema['properties']['total_answered_questions'] ) ) {
			$data['total_answered_questions'] = (int) $attempt->total_answered_questions;
		}

		if ( isset( $schema['properties']['total_marks'] ) ) {
			$data['total_marks'] = (float) $attempt->total_marks;
		}

		if ( isset( $schema['properties']['earned_marks'] ) ) {
			$data['earned_marks'] = (float) $attempt->earned_marks;
		}

		if ( isset( $schema['properties']['attempt_info'] ) ) {
			$data['attempt_info'] = json_decode( $attempt->attempt_info );
		}

		if ( isset( $schema['properties']['attempt_status'] ) ) {
			$data['attempt_status'] = $attempt->attempt_status;
		}

		if ( isset( $schema['properties']['attempt_ip'] ) ) {
			$data['attempt_ip'] = $attempt->attempt_ip;
		}

		if ( isset( $schema['properties']['attempt_started_at'] ) ) {
			$data['attempt_started_at'] = $attempt->attempt_started_at;
		}

		if ( isset( $schema['properties']['answer_ended_at'] ) ) {
			$data['answer_ended_at'] = $attempt->answer_ended_at;
		}

		return apply_filters( 'academy_quizzes/api/quiz_attempt_item', $data );
	}

	protected function prepare_item_for_database( $request ) {
		$prepared_attempt  = new \stdClass();

		$schema = $this->get_item_schema();

		// Attempt Id.
		if ( ! empty( $schema['attempt_id'] ) && isset( $request['attempt_id'] ) ) {
			if ( is_numeric( $request['attempt_id'] ) ) {
				$prepared_attempt->attempt_id = $request['attempt_id'];
			}
		}

		// course Id.
		if ( ! empty( $schema['course_id'] ) && isset( $request['course_id'] ) ) {
			if ( is_numeric( $request['course_id'] ) ) {
				$prepared_attempt->course_id = $request['course_id'];
			}
		}

		// Quiz Id.
		if ( ! empty( $schema['quiz_id'] ) && isset( $request['quiz_id'] ) ) {
			if ( is_numeric( $request['quiz_id'] ) ) {
				$prepared_attempt->quiz_id = $request['quiz_id'];
			}
		}

		// User Id.
		if ( ! empty( $schema['user_id'] ) && isset( $request['user_id'] ) ) {
			if ( is_numeric( $request['user_id'] ) ) {
				$prepared_attempt->user_id = $request['user_id'];
			}
		}

		// Total Questions.
		if ( ! empty( $schema['total_questions'] ) && isset( $request['total_questions'] ) ) {
			if ( is_numeric( $request['total_questions'] ) ) {
				$prepared_attempt->total_questions = $request['total_questions'];
			}
		}

		// Total Answered Questions.
		if ( ! empty( $schema['total_answered_questions'] ) && isset( $request['total_answered_questions'] ) ) {
			if ( is_numeric( $request['total_answered_questions'] ) ) {
				$prepared_attempt->total_answered_questions = $request['total_answered_questions'];
			}
		}

		// Total Marks Questions.
		if ( ! empty( $schema['total_marks'] ) && isset( $request['total_marks'] ) ) {
			if ( is_numeric( $request['total_marks'] ) ) {
				$prepared_attempt->total_marks = $request['total_marks'];
			}
		}

		// Earned Marks.
		if ( ! empty( $schema['earned_marks'] ) && isset( $request['earned_marks'] ) ) {
			if ( is_numeric( $request['earned_marks'] ) ) {
				$prepared_attempt->earned_marks = $request['earned_marks'];
			}
		}

		// Attempt Info.
		if ( ! empty( $schema['attempt_info'] ) && isset( $request['attempt_info'] ) ) {
			if ( is_array( $request['attempt_info'] ) ) {
				$prepared_attempt->attempt_info = wp_json_encode( wp_unslash( $request['attempt_info'] ) );
			}
		}

		// Attempt Status.
		if ( ! empty( $schema['attempt_status'] ) && isset( $request['attempt_status'] ) ) {
			if ( is_string( $request['attempt_status'] ) ) {
				$prepared_attempt->attempt_status = $request['attempt_status'];
			}
		}

		// Attempt IP.
		if ( ! empty( $schema['attempt_ip'] ) && isset( $request['attempt_ip'] ) ) {
			if ( is_string( $request['attempt_ip'] ) ) {
				$prepared_attempt->attempt_ip = $request['attempt_ip'];
			}
		}

		return apply_filters( 'academy/api/rest_pre_insert_quiz_attempt', $prepared_attempt, $request );
	}

	protected function rest_prepare_for_collection( $response ) {
		if ( ! ( $response instanceof \WP_REST_Response ) ) {
			return $response;
		}

		$data  = (array) $response->get_data();
		$server = rest_get_server();
		if ( method_exists( $server, 'get_compact_response_links' ) ) {
			$links = call_user_func( array( $server, 'get_compact_response_links' ), $response );
		} else {
			$links = call_user_func( array( $server, 'get_response_links' ), $response );
		}

		if ( ! empty( $links ) ) {
			$data['_links'] = $links;
		}

		return $data;
	}
	public function add_x_wp_total_header( $response, $handler, $request ) {
		$route = '/' . $this->namespace . '/' . $this->rest_base;
		if ( $route === $request->get_route() ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				$total = \AcademyQuizzes\Classes\Query::get_total_number_of_attempts( get_current_user_id() );
			} else {
				$total = \AcademyQuizzes\Classes\Query::get_total_number_of_attempts();
			}
			$response->header( 'x-wp-total', $total );
		}
		return $response;
	}
}
