<?php
namespace AcademyQuizzes\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyQuizzes\API\Schema\QuizAnswerSchema;
use AcademyQuizzes\Classes\Query;

class QuizAnswers extends \WP_REST_Controller {

	use QuizAnswerSchema;

	public static function init() {
		$self            = new self();
		$self->namespace = ACADEMY_PLUGIN_SLUG . '/v1';
		$self->rest_base = 'quiz_answers';
		add_action( 'rest_api_init', array( $self, 'register_routes' ) );
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
					'permission_callback' => array( $this, 'get_items_permission_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_permissions_check' ),
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
					'permission_callback' => array( $this, 'get_item_permission_check' ),
					'args'                => $get_item_args,
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_item_schema(),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
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
	}

	public function get_items_permission_check( $request ) {
		if ( ! current_user_can( 'manage_academy_instructor' ) ) {
			return new \WP_Error(
				'rest_forbidden_context',
				esc_html__( 'Sorry, you are not allowed to get quiz answers.', 'academy' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	public function get_item_permission_check( $request ) {
		if ( ! current_user_can( 'manage_academy_instructor' ) ) {
			return new \WP_Error(
				'rest_forbidden_context',
				esc_html__( 'Sorry, you are not allowed to get quiz answer.', 'academy' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	public function create_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_academy_instructor' ) ) {
			return new \WP_Error(
				'rest_forbidden_context',
				esc_html__( 'Sorry, you are not allowed to create quiz answer', 'academy' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	public function update_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_academy_instructor' ) ) {
			return new \WP_Error(
				'rest_forbidden_context',
				esc_html__( 'Sorry, you are not allowed to update quiz answer', 'academy' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	public function delete_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_academy_instructor' ) ) {
			return new \WP_Error(
				'rest_forbidden_context',
				esc_html__( 'Sorry, you are not allowed to delete quiz answer', 'academy' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
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
		$params = $request->get_params();

		$args = array(
			'limit' => 12,
			'offset' => 0,
		);
		$questions = Query::get_quiz_questions( $args );

		$data = array();

		if ( empty( $questions ) ) {
			return rest_ensure_response( $data );
		}

		foreach ( $questions as $question ) {
			$response = $this->rest_prepare_item( $question, $request );
			$data[] = $this->rest_prepare_for_collection( $response );
		}

		return rest_ensure_response( $data );
	}

	public function get_item( $request ) {
		global $wpdb;
		$question_id = $request->get_param( 'id' );
		$question_type = $request->get_param( 'question_type' );
		$answers = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *  FROM {$wpdb->prefix}academy_quiz_answers WHERE question_id=%d AND question_type=%s",
				$question_id,
				$question_type
			),
			OBJECT
		);
		$response = [];
		foreach ( $answers as $answer ) {
			$answer->answer_title = html_entity_decode( $answer->answer_title );
			$response[] = $this->prepare_answer_for_response( $answer );
		}
		return new \WP_REST_Response(
			$response,
			200
		);
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
		if ( empty( empty( $request['quiz_id'] ) || $request['question_type'] ) || empty( $request['question_id'] ) ) {
			return new \WP_Error(
				'rest_academy_quiz_answers_params_missing',
				esc_html__( 'quiz_id, question_type and question_id param missing', 'academy' ),
				array( 'status' => 400 )
			);
		}
		$prepared_answer = $this->prepare_item_for_database( $request );
		$answer_id = Query::quiz_answer_insert( wp_unslash( (array) $prepared_answer ) );// previously use wp_slash
		$answer = Query::get_quiz_answer( $answer_id );
		return rest_ensure_response( $answer );
	}

	public function update_item( $request ) {
		$params = $request->get_params();
		if ( empty( $params['answer_id'] ) ) {
			return new \WP_Error(
				'rest_answer_id_not_exists',
				esc_html__( 'Cannot update existing answer.', 'academy' ),
				array( 'status' => 400 )
			);
		}

		$prepared_answer = $this->prepare_item_for_database( $request );
		$answer_id = Query::quiz_answer_insert( wp_unslash( (array) $prepared_answer ) );// previously use wp_slash
		$answer = Query::get_quiz_answer( $answer_id );
		return rest_ensure_response( $answer );
	}

	public function delete_item( $request ) {
		$answer_id = $request->get_param( 'id' );
		$is_delete = Query::delete_answer( $answer_id );
		return new \WP_REST_Response( $is_delete, 200 );

	}


	protected function rest_prepare_item( $comment, $request ) {
		$data = array();

		$schema = $this->get_public_item_schema();

		if ( isset( $schema['properties']['question_id'] ) ) {
			$data['question_id'] = (int) $comment->question_id;
		}

		if ( isset( $schema['properties']['question_title'] ) ) {
			$data['question_title'] = $comment->question_title;
		}

		if ( isset( $schema['properties']['question_content'] ) ) {
			$data['question_content'] = $comment->question_content;
		}

		return $data;
	}

	protected function prepare_item_for_database( $request ) {
		$prepared_question  = new \stdClass();

		$schema = $this->get_item_schema();

		// Answer Id.
		if ( ! empty( $schema['answer_id'] ) && isset( $request['answer_id'] ) ) {
			if ( is_numeric( $request['answer_id'] ) ) {
				$prepared_question->answer_id = $request['answer_id'];
			}
		}

		// Quiz Id.
		if ( ! empty( $schema['quiz_id'] ) && isset( $request['quiz_id'] ) ) {
			if ( is_numeric( $request['quiz_id'] ) ) {
				$prepared_question->quiz_id = $request['quiz_id'];
			}
		}

		// Question Id.
		if ( ! empty( $schema['question_id'] ) && isset( $request['question_id'] ) ) {
			if ( is_numeric( $request['question_id'] ) ) {
				$prepared_question->question_id = $request['question_id'];
			}
		}

		// Question Id.
		if ( ! empty( $schema['question_type'] ) && isset( $request['question_type'] ) ) {
			if ( is_string( $request['question_type'] ) ) {
				$prepared_question->question_type = $request['question_type'];
			}
		}

		// Answer title.
		if ( ! empty( $schema['answer_title'] ) && isset( $request['answer_title'] ) ) {
			if ( is_string( $request['answer_title'] ) ) {
				$prepared_question->answer_title = html_entity_decode( $request['answer_title'] );
			}
		}

		// Answer Content.
		if ( ! empty( $schema['answer_content'] ) && isset( $request['answer_content'] ) ) {
			if ( is_string( $request['answer_content'] ) ) {
				$prepared_question->answer_content = $request['answer_content'];
			}
		}

		// Answer Flag.
		if ( ! empty( $schema['is_correct'] ) && isset( $request['is_correct'] ) ) {
			if ( is_numeric( $request['is_correct'] ) || is_bool( $request['is_correct'] ) ) {
				$prepared_question->is_correct = (int) $request['is_correct'];
			}
		}

		// Answer Image Id.
		if ( ! empty( $schema['image_id'] ) && isset( $request['image_id'] ) ) {
			if ( is_numeric( $request['image_id'] ) ) {
				$prepared_question->image_id = $request['image_id'];
			}
		}

		// Answer View Format
		if ( ! empty( $schema['view_format'] ) && isset( $request['view_format'] ) ) {
			if ( is_string( $request['view_format'] ) ) {
				$prepared_question->view_format = $request['view_format'];
			}
		}

		// Answer Order
		if ( ! empty( $schema['answer_order'] ) && isset( $request['answer_order'] ) ) {
			if ( is_numeric( $request['answer_order'] ) ) {
				$prepared_question->answer_order = $request['answer_order'];
			}
		}

		return apply_filters( 'academy/api/rest_pre_insert_quiz_answer', $prepared_question, $request );
	}

	protected function prepare_answer_for_response( $answer ) {
		$prepared_answer = $answer;
		$prepared_answer->is_correct = \Academy\Helper::sanitize_checkbox_field( $answer->is_correct );
		return $prepared_answer;
	}


	public function rest_prepare_for_collection( $response ) {
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

}
