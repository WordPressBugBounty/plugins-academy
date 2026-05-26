<?php
namespace AcademyQuizzes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \WP_REST_Controller as Controller;
use \WP_REST_Server as Server;
use \Academy\Helper as Helper;
use \AcademyQuizzes\Classes\Query as Query;
use \WP_REST_Response as Response;

class API extends Controller {

	public static function init() {
		$self = new self();
		API\QuizQuestions::init();
		API\QuizAnswers::init();
		API\QuizAttempts::init();
		add_filter( 'academy/api/user/meta_values', array( $self, 'user_quiz_analytics' ) );
		add_filter( 'rest_prepare_academy_quiz', array( $self, 'add_author_name_to_rest_response' ), 10, 3 );
		add_filter( 'rest_prepare_academy_quiz', [ $self, 'decode_special_characters_from_title' ], 10, 3 );
		self::rest_api_init();
	}

	public static function rest_api_init() {
		$self            = new self();
		$self->namespace = ACADEMY_PLUGIN_SLUG . '/v1';
		$self->rest_base = 'quizzes';
		add_action( 'rest_api_init', array( $self, 'register_routes' ) );
	}

	    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes() {
        $context_arg = array(
            'context' => $this->get_context_param( array( 'default' => 'view' ) ),
        );

        // Common ID argument schema.
        $id_arg = array(
            'description' => esc_html__( 'Unique identifier for the object.', 'academy' ),
            'type'        => 'integer',
            'required'    => true,
        );

        // Render quiz.
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/render_quiz',
            array(
                array(
                    'methods'             => Server::READABLE,
                    'callback'            => array( $this, 'render_quiz' ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                    'args'                => array_merge(
                        $context_arg,
                        array(
                            'quiz_id'   => $id_arg,
                            'course_id' => $id_arg,
                        )
                    ),
                ),
            )
        );

        // Render quiz answers.
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/render_answers',
            array(
                array(
                    'methods'             => Server::READABLE,
                    'callback'            => array( $this, 'render_question_answers' ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                    'args'                => array_merge(
                        $context_arg,
                        array(
                            'course_id'    => $id_arg,
                            'question_id'  => $id_arg,
                            'question_type' => array(
                                'description' => esc_html__( 'Type of the question.', 'academy' ),
                                'type'        => 'string',
                                'required'    => true,
                            ),
                        )
                    ),
                ),
            )
        );

        // Insert quiz answers (legacy URL — quiz_id in request body).
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/insert_question_answers',
            array(
                array(
                    'methods'             => Server::CREATABLE,
                    'callback'            => array( $this, 'insert_question_answers' ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                    'args'                => $context_arg,
                ),
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

	public function user_quiz_analytics( $values ) {
		$values['total_quizzes'] = Query::get_total_number_of_quizzes_by_instructor_id( get_current_user_id() );
		return $values;
	}
	public function add_author_name_to_rest_response( $item, $post, $request ) {
		$author_data = get_userdata( $item->data['author'] );
		$item->data['author_name'] = $author_data->display_name;
		return $item;
	}
	public function decode_special_characters_from_title( $item, $post, $request ) {
		$item->data['title']['rendered'] = html_entity_decode( $item->data['title']['rendered'] );
		return $item;
	}

	public function render_quiz( $request ) {
		$quiz_id = $request->get_param( 'quiz_id' );
		$course_id = $request->get_param( 'course_id' );
		$user_id   = (int) get_current_user_id();

		if ( ! $quiz_id || ! $course_id ) {
			return new Response( array( 'error' => esc_html__( 'Quiz ID and Course ID is required to parameter.', 'academy' ) ), 400 );
		}

		$has_permission = Helper::has_permission_to_access_curriculum( $course_id, $user_id, $quiz_id, 'quiz' );

		if ( $has_permission ) {
			do_action( 'academy_quizzes/before_render_quiz', $course_id, $quiz_id, $user_id );
			$question_order = get_post_meta( $quiz_id, 'academy_quiz_questions_order', true );
			$questions = Query::get_questions_by_quid_id( $quiz_id, $question_order );

			// type casting
			foreach ( $questions as &$question ) {
				$question->question_id = (int) $question->question_id;
				$question->quiz_id = (int) $question->quiz_id;
				$question->question_order = (int) $question->question_order;
				$question->question_image_id = (int) $question->question_image_id;
				$question->question_score = (float) $question->question_score;
				$question->question_negative_score = (float) $question->question_negative_score;
				$question->question_settings = json_decode( $question->question_settings ?? '{}', true );
			}
			// type casting end
			$layout = get_post_meta( $quiz_id, 'academy_quiz_questions_layout', true );
			$order = get_post_meta( $quiz_id, 'academy_quiz_questions_order', true );
			if ( count( $questions ) && $order ) {
				do_action( 'academy_quizzes/frontend/before_render_quiz', $course_id, $quiz_id );
				if ( 'all' === $layout ) {
					foreach ( $questions as &$question ) {
						$question->answers = Query::get_quiz_answers_by_question_id( $question->question_id, $question->question_type );
					}
				}
				$settings = Query::get_question_settings_by_quiz_id( $quiz_id, $order );
				return new Response(array(
					'questions' => $questions,
					'settings' => $settings,
					'content' => get_post_field( 'post_content', $quiz_id ),
				), 200);
			}
			return new Response( array( 'error' => esc_html__( 'No questions found for this quiz.', 'academy' ) ), 404 );
		} //end if
		return new Response( array( 'error' => esc_html__( 'Access Denied', 'academy' ) ), 403 );
	}

	public function render_question_answers( $request ) {
		$course_id = $request->get_param( 'course_id' );
		$question_id = $request->get_param( 'question_id' );
		$question_type = $request->get_param( 'question_type' );
		$user_id   = (int) get_current_user_id();

		if ( ! $course_id || ! $question_id || ! $question_type ) {
			return new Response( array( 'error' => esc_html__( 'Course ID, Question ID and Question Type is required to parameter.', 'academy' ) ), 400 );
		}

		$is_administrator = current_user_can( 'administrator' );
		$is_instructor    = Helper::is_instructor_of_this_course( $user_id, $course_id );
		$enrolled         = Helper::is_enrolled( $course_id, $user_id );
		$is_public        = Helper::is_public_course( $course_id );

		if ( $is_administrator || $is_instructor || $enrolled || $is_public ) {
			$answers = Query::get_quiz_answers_by_question_id( $question_id, $question_type );
			foreach ( $answers as &$answer ) {
				$answer->answer_id = (int) $answer->answer_id;
				$answer->quiz_id = (int) $answer->quiz_id;
				$answer->image_id = (int) $answer->image_id;
				$answer->answer_order = (int) $answer->answer_order;
			}
			return new Response( $answers, 200 );
		} //end if
		return new Response( array( 'error' => esc_html__( 'Access Denied', 'academy' ) ), 403 );
	}

    public function insert_question_answers( $request ) {
        $quiz_id        = (int) ( ! empty( $request['id'] ) ? $request['id'] : $request->get_param( 'quiz_id' ) );
        $course_id      = (int) $request->get_param( 'course_id' );
        $attempt_id     = (int) $request->get_param( 'attempt_id' );
        $attempt_answers = $request->get_param( 'attempt_answers' );

        // Validate required params.
        if ( ! $course_id || ! $quiz_id || ! $attempt_id || empty( $attempt_answers ) ) {
            return new Response(
                array( 'error' => esc_html__( 'Missing required parameters.', 'academy' ) ),
                400
            );
        }

        $user_id = get_current_user_id();

        // Permission check.
        if ( ! $this->can_attempt_quiz( $user_id, $course_id ) ) {
            return new Response(
                array( 'error' => esc_html__( 'Access Denied', 'academy' ) ),
                403
            );
        }

        if ( ! is_array( $attempt_answers ) ) {
            return new Response(
                array( 'error' => esc_html__( 'Invalid attempt answers format.', 'academy' ) ),
                400
            );
        }

        $results        = array();
        $achieved_score = 0;
        $score_total    = 0;

        foreach ( $attempt_answers as $answer ) {
            $processed = $this->process_answer( $answer );

            if ( empty( $processed ) ) {
                continue;
            }

            list( $question_id, $given_answer, $correct, $question_score, $negative_score ) = $processed;

            $score_total    += $question_score;
            $achieved_score += $correct ? $question_score : $negative_score;

            $results[] = Query::quiz_attempt_answer_insert( array(
                'user_id'       => $user_id,
                'quiz_id'       => $quiz_id,
                'question_id'   => $question_id,
                'attempt_id'    => $attempt_id,
                'answer'        => $given_answer,
                'question_mark' => $question_score,
                'achieved_mark' => $correct ? $question_score : $negative_score,
                'minus_mark'    => '',
                'is_correct'    => $correct,
            ) );
        }

        // Avoid division by zero.
        $percentage = $score_total > 0 ? ( $achieved_score / $score_total ) * 100 : 0;

        $quiz_data = (object) array(
            'user_id'           => $user_id,
            'course_id'         => $course_id,
            'quiz_id'           => $quiz_id,
            'assignment_id'     => null,
            'result_for'        => 'quiz',
            'earned_percentage' => $percentage,
        );

        do_action( 'academy_quizzes/after_quiz_insert', $quiz_data );

        $passing_grade = (float) get_post_meta( $quiz_id, 'academy_quiz_passing_grade', true );

        if ( $percentage >= $passing_grade ) {
            do_action( 'academy_quizzes/after_insert_quiz_status_pass', $quiz_id, $user_id, $course_id );
        } else {
            do_action( 'academy_quizzes/after_insert_quiz_status_failed', $quiz_id, $user_id, $course_id );
        }

        do_action( 'academy_quizzes/after_insert_quiz_status_completed', $quiz_id, $user_id, $course_id );

        return new Response( array( 'results' => $results ), 200 );
    }

    private function can_attempt_quiz( $user_id, $course_id ) {
	    return current_user_can( 'administrator' )
        || Helper::is_instructor_of_this_course( $user_id, $course_id )
        || Helper::is_enrolled( $course_id, $user_id )
        || Helper::is_public_course( $course_id );
    }

    private function process_answer( $data ) {
        $question_id    = (int) $data['question_id'];
        $question_score = (float) $data['question_score'];
        $question_type  = (string) $data['question_type'];
        $given_answer   = $data['given_answer'];

        $correct = 0;

        switch ( $question_type ) {
            case 'imageAnswer':
                $given_answer = $this->normalize_json_answer( $given_answer, true );
                $correct      = (int) Query::is_image_answer_quiz_correct_answer( $given_answer, $question_id );
                $given_answer = wp_json_encode( $given_answer );
                break;

            case 'multipleChoice':
                $ids          = is_array( $given_answer ) ? $given_answer : explode( ',', $given_answer );
                $given_answer = implode( ',', $ids );
                $correct      = (int) Query::is_quiz_correct_answer( $ids, $question_id );
                break;

            case 'fillInTheBlanks':
                $args         = $this->normalize_json_answer( $given_answer );
                $given_answer = implode( ',', $args );
                $correct      = (int) Query::is_fill_in_the_blanks_quiz_correct_answer( $args, $question_id );
                break;

            case 'shortAnswer':
                break;

            default:
                $correct = (int) Query::is_quiz_correct_answer( $given_answer, $question_id );
        }

        $negative_score = $this->calculate_negative_mark( $question_id, $given_answer, $correct );

        return array( $question_id, $given_answer, $correct, $question_score, $negative_score );
    }

    private function normalize_json_answer( $answer, $with_keys = false ) {
        $decoded = is_string( $answer ) ? json_decode( stripslashes( $answer ), true ) : $answer;

        if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
            return $with_keys
                ? wp_list_pluck( $decoded, 'value', 'id' )
                : wp_list_pluck( $decoded, 'value' );
        }

        return (array) $answer;
    }

    private function calculate_negative_mark( $question_id, $given_answer, $correct ) {
        $details = Query::get_question_details_by_question_id( $question_id );
        $negative = ! empty( $details ) ? (float) current( $details )->question_negative_score : 0;

        if ( ! empty( $given_answer ) && $negative > 0 && ! $correct ) {
            return -$negative;
        }

        return 0;
    }
}
