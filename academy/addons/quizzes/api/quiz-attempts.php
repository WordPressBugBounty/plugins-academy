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
				'schema' => $this->get_public_item_schema(),
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
				'schema' => $this->get_public_item_schema(),
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
		if ( current_user_can( 'manage_academy_instructor' ) || $enrolled || $is_public ) {
			return true;
		}
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
		$is_public = get_post_meta( $course_id, 'academy_course_type', true ) === 'public' ? true : false;
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
		if ( ! current_user_can( 'manage_options' ) && current_user_can( 'manage_academy_instructor' ) ) {
			$attempts = Query::get_quiz_attempts_for_instructors( $args );
		} else {
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
		$response = $this->rest_prepare_item( $attempt, $request );
		return rest_ensure_response( $response );
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
		do_action( 'academy_quizzes/api/before_quiz_attempt_finished', $params );
		$total_questions_marks = Query::get_total_questions_marks_by_quiz_id( $params['quiz_id'] );
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
				$user = $user_data->data;
				$user->admin_permalink = get_edit_user_link( $attempt->user_id );
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
			$data['attempt_info'] = $attempt->attempt_info;
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
