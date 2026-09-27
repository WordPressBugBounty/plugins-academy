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
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'read_item_permissions_check' ],
					'args'                => $this->get_collection_params(),
				],
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => $this->get_item_schema(),
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/export',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'export_items' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/import',
			[
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'import_items' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
				],
			]
		);

		$get_item_args = [
			'context' => $this->get_context_param( [ 'default' => 'view' ] ),
		];

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			[
				'args'   => [
					'id' => [
						'description' => esc_html__( 'Unique identifier for the object.', 'academy' ),
						'type'        => 'integer',
					],
				],
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'read_item_permissions_check' ],
					'args'                => $get_item_args,
				],
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_item' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => $this->get_item_schema(),
				],
				[
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'delete_item_permissions_check' ],
					'args'                => [
						'force' => [
							'type'        => 'boolean',
							'default'     => false,
							'description' => esc_html__( 'Whether to bypass Trash and force deletion.', 'academy' ),
						],
					],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);

		// Reverse lookup: which quizzes reference this question.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/quizzes',
			[
				'args' => [
					'id' => [
						'description' => esc_html__( 'Unique identifier for the question.', 'academy' ),
						'type'        => 'integer',
					],
				],
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item_quizzes' ],
					'permission_callback' => [ $this, 'read_item_permissions_check' ],
				],
			]
		);
	}

	/**
	 * GET /quiz_questions/{id}/quizzes — the quizzes that reference a question.
	 *
	 * @param \WP_REST_Request $request
	 */
	public function get_item_quizzes( $request ) {
		$quizzes = Query::get_quizzes_using_question( (int) $request->get_param( 'id' ) );
		return rest_ensure_response( $quizzes );
	}

	/**
	 * Reading the question bank is an authoring capability, not a public one.
	 *
	 * Every consumer of these read routes is an admin-side screen (the quiz
	 * builder and the Question Bank browser). Students never reach questions
	 * through here — they get them via the quiz attempt flow — so gating reads
	 * behind the same capability as writes does not affect the learner side,
	 * and it keeps question titles, content and explanations from being
	 * enumerable by anyone who knows the route.
	 *
	 * @param \WP_REST_Request $request
	 */
	public function read_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_academy_instructor' ) ) {
			return new \WP_Error(
				'rest_forbidden_context',
				esc_html__( 'Sorry, you are not allowed to view quiz questions', 'academy' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
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
		$params   = $request->get_params();
		$per_page = (int) ( $params['per_page'] ?? 10 );
		$page     = max( 1, (int) ( $params['page'] ?? 1 ) );

		$filter_args = [
			'limit'          => $per_page,
			'offset'         => ( $page - 1 ) * $per_page,
			'search'         => sanitize_text_field( $params['search'] ?? '' ),
			'question_type'  => sanitize_text_field( $params['question_type'] ?? '' ),
			'question_level' => sanitize_text_field( $params['question_level'] ?? '' ),
			'orderby'        => sanitize_text_field( $params['orderby'] ?? 'question_created_at' ),
			'order'          => sanitize_text_field( $params['order'] ?? 'DESC' ),
		];

		// Instructors (unlike admins) may only browse/reuse questions that
		// live in their own quizzes — otherwise the question bank and the
		// quiz builder's "Reuse Content" picker leak every other author's
		// question titles and content.
		if ( ! current_user_can( 'manage_options' ) ) {
			$filter_args['quiz_id__in'] = Query::get_quiz_ids_by_instructor_id( get_current_user_id() );
		}

		$questions = Query::get_quiz_questions( $filter_args );
		$total     = Query::get_quiz_questions_count( $filter_args );

		// One scan of all quizzes' meta gives the per-question usage counts for
		// this page (no join table — membership lives in quiz post meta).
		$usage_map = Query::get_question_usage_map();

		$data = [];
		foreach ( $questions as $question ) {
			$item                    = $this->rest_prepare_item( $question, $request );
			$item['used_in_count']   = count( $usage_map[ (int) $question->question_id ] ?? [] );
			$data[]                  = $this->rest_prepare_for_collection( $item );
		}

		$response = rest_ensure_response( $data );
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', (int) ceil( $total / $per_page ) );
		return $response;
	}

	public function export_items( $request ) {
		$export_args = [
			'limit'  => 9999,
			'offset' => 0,
		];

		// Same scoping as get_items(): an instructor can only export the
		// questions from their own quizzes, not the whole site's bank.
		if ( ! current_user_can( 'manage_options' ) ) {
			$export_args['quiz_id__in'] = Query::get_quiz_ids_by_instructor_id( get_current_user_id() );
		}

		$questions = Query::get_quiz_questions( $export_args );

		if ( empty( $questions ) ) {
			return new \WP_Error(
				'no_questions',
				esc_html__( 'No questions to export.', 'academy' ),
				[ 'status' => 404 ]
			);
		}

		$data = [];
		foreach ( $questions as $q ) {
			// get_question_answers_by_question_id needs the question_type to build its query
			$answers = Query::get_question_answers_by_question_id( $q->question_id, $q->question_type );
			$answer_data = [];
			if ( ! empty( $answers ) ) {
				foreach ( $answers as $a ) {
					$answer_data[] = [
						'answer_title'   => $a->answer_title,
						'answer_content' => $a->answer_content,
						'is_correct'     => (int) $a->is_correct,
						'image_id'       => $a->image_id,
						'view_format'    => $a->view_format,
						'answer_order'   => (int) $a->answer_order,
					];
				}
			}

			$data[] = [
				'question_title'          => $q->question_title,
				'question_title_type'     => $q->question_title_type,
				'question_content'        => $q->question_content,
				'question_explanation'    => $q->question_explanation,
				'question_type'           => $q->question_type,
				'question_level'          => $q->question_level,
				'question_score'          => $q->question_score,
				'question_negative_score' => $q->question_negative_score,
				'question_settings'       => $q->question_settings,
				'question_answers'        => wp_json_encode( $answer_data ),
			];
		}//end foreach

		return rest_ensure_response( $data );
	}

	public function import_items( $request ) {
		$rows = $request->get_json_params();
		if ( empty( $rows ) || ! is_array( $rows ) ) {
			return new \WP_Error(
				'invalid_data',
				esc_html__( 'No valid question data provided.', 'academy' ),
				[ 'status' => 400 ]
			);
		}

		$allowed_types  = [ 'trueFalse', 'singleChoice', 'multipleChoice', 'dropDown', 'fillInTheBlanks', 'imageAnswer', 'shortAnswer' ];
		$allowed_levels = [ '', 'high', 'medium', 'low' ];
		$imported       = 0;
		$errors         = [];

		foreach ( $rows as $index => $row ) {
			$title = self::sanitize_rich_title( $row['question_title'] ?? '' );
			if ( empty( $title ) || Query::question_title_exists( $title ) ) {
				/* translators: %d: CSV row number. */
				$errors[] = sprintf( esc_html__( 'Row %d: question_title is required or matched.', 'academy' ), $index + 1 );
				continue;
			}

			$title_type = sanitize_key( $row['question_title_type'] ?? 'plain' );
			if ( ! in_array( $title_type, [ 'plain', 'rich' ], true ) ) {
				$title_type = 'plain';
			}

			$type = sanitize_text_field( $row['question_type'] ?? 'trueFalse' );
			if ( ! in_array( $type, $allowed_types, true ) ) {
				/* translators: 1: CSV row number, 2: question type. */
				$errors[] = sprintf( esc_html__( 'Row %1$d: invalid question_type "%2$s".', 'academy' ), $index + 1, esc_html( $type ) );
				continue;
			}

			$level = sanitize_text_field( $row['question_level'] ?? '' );
			if ( ! in_array( $level, $allowed_levels, true ) ) {
				$level = '';
			}

			// quiz_id: if importing into a specific quiz, pass it in the request;
			// otherwise default to 0 (unassigned / question bank item).
			$quiz_id = absint( $row['quiz_id'] ?? 0 );

			$default_settings = [
				'display_points'  => true,
				'answer_required' => false,
				'randomize'       => false,
			];
			$settings = json_decode( wp_unslash( $row['question_settings'] ?? '' ), true );
			$settings = is_array( $settings ) ? wp_parse_args( $settings, $default_settings ) : $default_settings;

			$question_data = [
				'quiz_id'                 => $quiz_id,
				'question_title'          => $title,
				'question_title_type'     => $title_type,
				'question_content'        => self::sanitize_description( $row['question_content'] ?? '' ),
				'question_explanation'    => sanitize_text_field( $row['question_explanation'] ?? '' ),
				'question_type'           => $type,
				'question_level'          => $level,
				'question_score'          => (float) ( $row['question_score'] ?? 1.0 ),
				'question_negative_score' => (float) ( $row['question_negative_score'] ?? 0.0 ),
				'question_status'         => 'publish',
				'question_settings'       => wp_json_encode( $settings ),
			];

			$question_id = Query::quiz_question_insert( $question_data );

			if ( ! $question_id ) {
				/* translators: %d: CSV row number. */
				$errors[] = sprintf( esc_html__( 'Row %d: failed to insert question.', 'academy' ), $index + 1 );
				continue;
			}

			++$imported;

			// Insert answers for this question
			$answers = json_decode( $row['question_answers'], true ) ?? [];
			if ( ! empty( $answers ) && is_array( $answers ) ) {
				foreach ( $answers as $a_index => $answer ) {
					$answer_title = sanitize_text_field( $answer['answer_title'] ?? '' );

					if ( '' === $answer_title && empty( $answer['answer_content'] ) ) {
						$errors[] = sprintf(
							/* translators: 1: CSV row number, 2: answer number. */
							esc_html__( 'Row %1$d, answer %2$d: answer_title or answer_content is required, skipped.', 'academy' ),
							$index + 1,
							$a_index + 1
						);
						continue;
					}

					$answer_data = [
						'quiz_id'        => $quiz_id,
						'question_id'    => $question_id,
						'question_type'  => $type, // must match the question's type, table is filtered on this
						'answer_title'   => $answer_title,
						'answer_content' => sanitize_textarea_field( $answer['answer_content'] ?? '' ),
						'is_correct'     => (int) ( $answer['is_correct'] ?? 0 ),
						'image_id'       => absint( $answer['image_id'] ?? 0 ),
						'view_format'    => sanitize_text_field( $answer['view_format'] ?? '' ),
						'answer_order'   => (int) ( $answer['answer_order'] ?? $a_index ),
					];

					Query::quiz_answer_insert( $answer_data );
				}//end foreach
			} elseif ( ! in_array( $type, [ 'shortAnswer' ], true ) ) {
				// shortAnswer questions legitimately have no predefined answers;
				// everything else should have at least one.
				/* translators: 1: CSV row number, 2: question type. */
				$errors[] = sprintf( esc_html__( 'Row %1$d: no answers provided for question type "%2$s".', 'academy' ), $index + 1, esc_html( $type ) );
			}//end if
		}//end foreach

		return rest_ensure_response( [
			'imported' => $imported,
			'errors'   => $errors,
		] );
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
		$schema = $this->get_public_item_schema();
		$data   = [];

		$field_map = [
			'question_id'             => 'intval',
			'question_title'          => null,
			'question_title_type'     => null,
			'question_content'        => null,
			'question_explanation'    => null,
			'question_type'           => null,
			'question_level'          => null,
			'question_score'          => 'floatval',
			'question_negative_score' => 'floatval',
			'question_created_at'     => null,
		];

		foreach ( $field_map as $field => $cast ) {
			if ( isset( $schema['properties'][ $field ] ) ) {
				$value          = $question->$field ?? null;
				$data[ $field ] = $cast ? $cast( $value ) : $value;
			}
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

		// Question title type.
		if ( ! empty( $schema['question_title_type'] ) && isset( $request['question_title_type'] ) ) {
			if ( in_array( $request['question_title_type'], [ 'plain', 'rich' ], true ) ) {
				$prepared_question->question_title_type = $request['question_title_type'];
			}
		}

		// Question Content.
		if ( ! empty( $schema['question_content'] ) && isset( $request['question_content'] ) ) {
			if ( is_string( $request['question_content'] ) ) {
				$prepared_question->question_content = $request['question_content'];
			}
		}

		// Question Explanation
		if ( ! empty( $schema['question_explanation'] ) && isset( $request['question_explanation'] ) ) {
			if ( is_string( $request['question_explanation'] ) ) {
				$prepared_question->question_explanation = $request['question_explanation'];
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

		// Question Negative Score.
		if ( ! empty( $schema['question_negative_score'] ) && isset( $request['question_negative_score'] ) ) {
			if ( is_numeric( $request['question_negative_score'] ) ) {
				$prepared_question->question_negative_score = $request['question_negative_score'];
			}
		}

		// Question Image ID.
		if ( ! empty( $schema['question_image_id'] ) && isset( $request['question_image_id'] ) ) {
			if ( is_numeric( $request['question_image_id'] ) ) {
				$prepared_question->question_image_id = $request['question_image_id'];
			}
		}

		// Question Audio ID.
		if ( ! empty( $schema['question_audio_id'] ) && isset( $request['question_audio_id'] ) ) {
			if ( is_numeric( $request['question_audio_id'] ) ) {
				$prepared_question->question_audio_id = $request['question_audio_id'];
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
