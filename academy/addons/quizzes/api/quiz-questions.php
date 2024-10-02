<?php
namespace AcademyQuizzes\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyQuizzes\Classes\Query;
use AcademyQuizzes\API\Schema\QuizQuestionSchema;


class QuizQuestions extends \WP_REST_Controller {

	use QuizQuestionSchema;

	public static function init() {
		$self            = new self();
		$self->namespace = ACADEMY_PLUGIN_SLUG . '/v1';
		$self->rest_base = 'quiz_questions';
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
					'permission_callback' => '__return_true',
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
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
					'permission_callback' => '__return_true',
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

	public function create_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_academy_instructor' ) ) {
			return new \WP_Error(
				'rest_forbidden_context',
				esc_html__( 'Sorry, you are not allowed to create quiz question', 'academy' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	public function update_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_academy_instructor' ) ) {
			return new \WP_Error(
				'rest_forbidden_context',
				esc_html__( 'Sorry, you are not allowed to update quiz question', 'academy' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	public function delete_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_academy_instructor' ) ) {
			return new \WP_Error(
				'rest_forbidden_context',
				esc_html__( 'Sorry, you are not allowed to delete quiz question', 'academy' ),
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
		$id = $request->get_param( 'id' );
		$question = Query::get_quiz_question( $id );
		return new \WP_REST_Response(
			$question,
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
		if ( ! empty( $request['id'] ) ) {
			return new \WP_Error(
				'rest_post_exists',
				esc_html__( 'Cannot create existing question.', 'academy' ),
				array( 'status' => 400 )
			);
		}

		$prepared_question = $this->prepare_item_for_database( $request );
		$question_id = Query::quiz_question_insert( wp_unslash( (array) $prepared_question ) );
		$question = Query::get_quiz_question( $question_id );
		return rest_ensure_response( $question );
	}

	public function update_item( $request ) {
		$params = $request->get_params();
		if ( empty( $params['question_id'] ) ) {
			return new \WP_Error(
				'rest_question_id_not_exists',
				esc_html__( 'Cannot update existing question.', 'academy' ),
				array( 'status' => 400 )
			);
		}
		$prepared_question = $this->prepare_item_for_database( $request );
		$question_id = Query::quiz_question_insert( wp_unslash( (array) $prepared_question ) );
		$question = Query::get_quiz_question( $question_id );
		return rest_ensure_response( $question );
	}

	public function delete_item( $request ) {
		$question_id = $request->get_param( 'id' );
		$is_delete = Query::delete_question( $question_id );
		return new \WP_REST_Response( $is_delete, 200 );
	}


	protected function rest_prepare_item( $question, $request ) {
		$data = array();

		$schema = $this->get_public_item_schema();

		if ( isset( $schema['properties']['question_id'] ) ) {
			$data['question_id'] = (int) $question->question_id;
		}

		if ( isset( $schema['properties']['question_title'] ) ) {
			$data['question_title'] = $question->question_title;
		}

		if ( isset( $schema['properties']['question_content'] ) ) {
			$data['question_content'] = $question->question_content;
		}

		return $data;
	}

	protected function prepare_item_for_database( $request ) {
		$prepared_question  = new \stdClass();

		$schema = $this->get_item_schema();

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

		// Question title.
		if ( ! empty( $schema['question_name'] ) && isset( $request['question_title'] ) ) {
			if ( is_string( $request['question_title'] ) ) {
				$prepared_question->question_title = $request['question_title'];
			}
		}

		// Question Content.
		if ( ! empty( $schema['question_content'] ) && isset( $request['question_content'] ) ) {
			if ( is_string( $request['question_content'] ) ) {
				$prepared_question->question_content = $request['question_content'];
			}
		}

		// Question lavel.
		if ( ! empty( $schema['question_level'] ) && isset( $request['question_level'] ) ) {
			if ( is_string( $request['question_level'] ) ) {
				$prepared_question->question_level = $request['question_level'];
			}
		}

		// Question Type.
		if ( ! empty( $schema['question_type'] ) && isset( $request['question_type'] ) ) {
			if ( is_string( $request['question_type'] ) ) {
				$prepared_question->question_type = $request['question_type'];
			}
		}

		// Question Score.
		if ( ! empty( $schema['question_score'] ) && isset( $request['question_score'] ) ) {
			if ( is_numeric( $request['question_score'] ) ) {
				$prepared_question->question_score = $request['question_score'];
			}
		}

		// Question Settings.
		if ( ! empty( $schema['question_settings'] ) && isset( $request['question_settings'] ) ) {
			if ( is_array( $request['question_settings'] ) ) {
				$prepared_question->question_settings = wp_json_encode( $request['question_settings'] );
			}
		}

		return apply_filters( 'academy/api/rest_pre_insert_quiz_question', $prepared_question, $request );
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

}
