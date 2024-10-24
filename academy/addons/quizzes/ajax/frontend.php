<?php
namespace AcademyQuizzes\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use Academy\Classes\Sanitizer;
use Academy\Classes\AbstractAjaxHandler;

class Frontend extends AbstractAjaxHandler {
	protected $namespace = ACADEMY_PLUGIN_SLUG . '_quizzes';
	public function __construct() {
		// mark as complete
		add_action( 'academy/frontend/before_mark_topic_complete', array( $this, 'mark_quiz_complete' ), 11, 4 );

		$this->actions = array(
			'render_quiz' => array(
				'callback' => array( $this, 'render_quiz' ),
				'capability' => 'read'
			),
			'render_quiz_answers' => array(
				'callback' => array( $this, 'render_quiz_answers' ),
				'capability' => 'read'
			),
			'insert_quiz_answers' => array(
				'callback' => array( $this, 'insert_quiz_answers' ),
				'capability' => 'read'
			),
			'insert_quiz_answer' => array(
				'callback' => array( $this, 'insert_quiz_answer' ),
				'capability' => 'read'
			),
			'get_student_quiz_attempt_details' => array(
				'callback' => array( $this, 'get_student_quiz_attempt_details' ),
				'capability' => 'read'
			),
		);
	}

	public function mark_quiz_complete( $topic_type, $course_id, $topic_id, $user_id ) {
		if ( 'quiz' === $topic_type && ! \AcademyQuizzes\Classes\Query::has_attempt_quiz( $course_id, $topic_id, $user_id ) ) {
			if ( \Academy\Helper::get_settings( 'is_enabled_lessons_php_render' ) ) {
				$referer_url = \Academy\Helper::sanitize_referer_url( wp_get_referer() );
				wp_safe_redirect( $referer_url );
			}
			wp_send_json_error( __( 'Complete the quiz before marking it as done.', 'academy' ) );
		}
	}

	public function render_quiz( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
			'quiz_id' => 'integer',
		], $payload_data );

		$course_id = $payload['course_id'];
		$quiz_id = $payload['quiz_id'];
		$user_id   = (int) get_current_user_id();

		$has_permission = \Academy\Helper::has_permission_to_access_curriculum( $course_id, $user_id, $quiz_id, 'quiz' );

		if ( $has_permission ) {
			do_action( 'academy_quizzes/before_render_quiz', $course_id, $quiz_id, $user_id );
			$question_order = get_post_meta( $quiz_id, 'academy_quiz_questions_order', true );
			$questions = \AcademyQuizzes\Classes\Query::get_questions_by_quid_id( $quiz_id, $question_order );
			$order = get_post_meta( $quiz_id, 'academy_quiz_questions_order', true );
			if ( count( $questions ) && $order ) {
				do_action( 'academy_quizzes/frontend/before_render_quiz', $course_id, $quiz_id );
				$settings = \AcademyQuizzes\Classes\Query::get_question_settings_by_quiz_id( $quiz_id, $order );
				wp_send_json_success([
					'questions' => $questions,
					'settings' => $settings,
				]);
			}
			wp_send_json_error( esc_html__( 'Sorry, something went wrong!', 'academy' ) );
		}//end if
		wp_send_json_error( esc_html__( 'Access Denied', 'academy' ) );
	}

	public function render_quiz_answers( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
			'question_type' => 'string',
			'question_id' => 'integer',
		], $payload_data );

		$course_id = $payload['course_id'];
		$question_id = $payload['question_id'];
		$question_type = $payload['question_type'];
		$user_id   = (int) get_current_user_id();

		$is_administrator = current_user_can( 'administrator' );
		$is_instructor    = \Academy\Helper::is_instructor_of_this_course( $user_id, $course_id );
		$enrolled         = \Academy\Helper::is_enrolled( $course_id, $user_id );
		$is_public        = \Academy\Helper::is_public_course( $course_id );

		if ( $is_administrator || $is_instructor || $enrolled || $is_public ) {
			$answers = \AcademyQuizzes\Classes\Query::get_quiz_answers_by_question_id( $question_id, $question_type );
			wp_send_json_success( $answers );
		}//end if
		wp_send_json_error( esc_html__( 'Access Denied', 'academy' ) );
		wp_die();
	}

	public function insert_quiz_answers( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
			'quiz_id' => 'integer',
			'attempt_id' => 'integer',
			'attempt_answers' => 'string',
		], $payload_data );

		$course_id = $payload['course_id'];
		$quiz_id = $payload['quiz_id'];
		$attempt_id = $payload['attempt_id'];
		$attempt_answers = isset( $payload['attempt_answers'] ) ? $payload['attempt_answers'] : '';

		$user_id   = (int) get_current_user_id();
		$is_administrator = current_user_can( 'administrator' );
		$is_instructor    = \Academy\Helper::is_instructor_of_this_course( $user_id, $course_id );
		$enrolled         = \Academy\Helper::is_enrolled( $course_id, $user_id );
		$is_public = \Academy\Helper::is_public_course( $course_id );

		if ( $is_administrator || $is_instructor || $enrolled || $is_public ) {
			// Check if JSON data was received
			if ( ! empty( $attempt_answers ) ) {
				// Decode the JSON string into a PHP array
				$attempt_answers = json_decode( $attempt_answers, true );
				$results = [];
				if ( is_array( $attempt_answers ) && count( $attempt_answers ) ) {
					$achieved_score = 0;
					$score_total = 0;
					foreach ( $attempt_answers as $attempt_answer ) {
						$question_id = (int) $attempt_answer['question_id'];
						$question_score = (float) $attempt_answer['question_score'];
						$question_type = (string) $attempt_answer['question_type'];
						$given_answer = $attempt_answer['given_answer'];

						$correct_answer = 0;
						if ( 'imageAnswer' === $question_type ) {
							$given_answer = wp_list_pluck( json_decode( stripslashes( $given_answer ) ), 'value', 'id' );
							$correct_answer = (int) \AcademyQuizzes\Classes\Query::is_image_answer_quiz_correct_answer( $given_answer, $question_id );
							// Insert JSON Data
							$given_answer = wp_json_encode( $given_answer );
						} elseif ( 'multipleChoice' === $question_type ) {
							$IDs = ( is_array( $given_answer ) ? $given_answer : explode( ',', $given_answer ) );
							$given_answer = implode( ',', $IDs );
							$correct_answer = (int) \AcademyQuizzes\Classes\Query::is_quiz_correct_answer( $IDs, $question_id );
						} elseif ( 'fillInTheBlanks' === $question_type ) {
							$given_answer_args = wp_list_pluck( json_decode( stripslashes( $given_answer ) ), 'value' );
							$given_answer = implode( ',', $given_answer_args );
							$correct_answer = (int) \AcademyQuizzes\Classes\Query::is_fill_in_the_blanks_quiz_correct_answer( $given_answer_args, $question_id );
						} elseif ( 'shortAnswer' !== $question_type ) {
							$correct_answer = (int) \AcademyQuizzes\Classes\Query::is_quiz_correct_answer( $given_answer, $question_id );
						}

						$score = $correct_answer ? $question_score : 0;

						$score_total   += $question_score;
						$achieved_score += $score;

						$results[] = \AcademyQuizzes\Classes\Query::quiz_attempt_answer_insert(array(
							'user_id'           => $user_id,
							'quiz_id'           => $quiz_id,
							'question_id'       => $question_id,
							'attempt_id'        => $attempt_id,
							'answer'            => $given_answer,
							'question_mark'     => $question_score,
							'achieved_mark'     => $correct_answer ? $question_score : '',
							'minus_mark'        => '',
							'is_correct'        => $correct_answer,
						));
					}//end foreach

					$percentage = ( $achieved_score / $score_total ) * 100;

					$quiz_data = (object) array(
						'user_id'           => $user_id,
						'course_id'         => $course_id,
						'quiz_id'           => $quiz_id,
						'assignment_id'     => null,
						'result_for'        => 'quiz',
						'earned_percentage' => $percentage,
					);

					do_action( 'academy_quizzes/after_quiz_insert', $quiz_data );

					// GamiPress integration hooks
					$passing_grade = get_post_meta( $quiz_id, 'academy_quiz_passing_grade', true );
					if ( $percentage >= $passing_grade ) {
						do_action( 'academy_quizzes/after_insert_quiz_status_pass', $quiz_id, $user_id, $course_id );
					} else {
						do_action( 'academy_quizzes/after_insert_quiz_status_failed', $quiz_id, $user_id, $course_id );
					}
				}//end if
				do_action( 'academy_quizzes/after_insert_quiz_status_completed', $quiz_id, $user_id, $course_id );
				wp_send_json_success( $results );
			}//end if
			wp_send_json_error( esc_html__( 'Empty Submission', 'academy' ) );
		}//end if
		wp_send_json_error( esc_html__( 'Access Denied', 'academy' ) );
	}

	public function insert_quiz_answer( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
			'quiz_id' => 'integer',
			'attempt_id' => 'integer',
			'question_id' => 'integer',
			'question_score' => 'float',
			'question_type' => 'string',
			'given_answer' => 'string',
		], $payload_data );

		$course_id = $payload['course_id'];
		$quiz_id = $payload['quiz_id'];
		$attempt_id = $payload['attempt_id'];
		$question_id = $payload['question_id'];
		$question_score = $payload['question_score'];
		$question_type = $payload['question_type'];
		$given_answer = $payload['given_answer'];

		$user_id   = (int) get_current_user_id();
		$is_administrator = current_user_can( 'administrator' );
		$is_instructor    = \Academy\Helper::is_instructor_of_this_course( $user_id, $course_id );
		$enrolled         = \Academy\Helper::is_enrolled( $course_id, $user_id );
		$is_public = \Academy\Helper::is_public_course( $course_id );

		if ( $is_administrator || $is_instructor || $enrolled || $is_public ) {
			$correct_answer = 0;
			if ( 'imageAnswer' === $question_type ) {
				$given_answer = wp_list_pluck( json_decode( stripslashes( $given_answer ) ), 'value', 'id' );
				$correct_answer = (int) \AcademyQuizzes\Classes\Query::is_image_answer_quiz_correct_answer( $given_answer, $question_id );
				// Insert JSON Data
				$given_answer = wp_json_encode( $given_answer );
			} elseif ( 'multipleChoice' === $question_type ) {
				$IDs = explode( ',', $given_answer );
				$correct_answer = (int) \AcademyQuizzes\Classes\Query::is_quiz_correct_answer( $IDs, $question_id );
			} elseif ( 'fillInTheBlanks' === $question_type ) {
				$given_answer_args = wp_list_pluck( json_decode( stripslashes( $given_answer ) ), 'value' );
				$given_answer = implode( ',', $given_answer_args );
				$correct_answer = (int) \AcademyQuizzes\Classes\Query::is_fill_in_the_blanks_quiz_correct_answer( $given_answer_args, $question_id );
			} elseif ( 'shortAnswer' !== $question_type ) {
				$correct_answer = (int) \AcademyQuizzes\Classes\Query::is_quiz_correct_answer( $given_answer, $question_id );
			}

			$attempt_answer = \AcademyQuizzes\Classes\Query::quiz_attempt_answer_insert(array(
				'user_id'           => $user_id,
				'quiz_id'           => $quiz_id,
				'question_id'       => $question_id,
				'attempt_id'        => $attempt_id,
				'answer'            => $given_answer,
				'question_mark'     => $question_score,
				'achieved_mark'     => $correct_answer ? $question_score : '',
				'minus_mark'        => '',
				'is_correct'        => $correct_answer,
			));

			wp_send_json_success( $attempt_answer );
		}//end if
		wp_send_json_error( esc_html__( 'Access Denied', 'academy' ) );
		wp_die();
	}

	public function get_student_quiz_attempt_details( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
			'attempt_id' => 'integer',
			'user_id' => 'integer',
		], $payload_data );

		$attempt_id = $payload['attempt_id'];
		$user_id = ( isset( $payload['user_id'] ) ? $payload['user_id'] : 0 );
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		$course_id = $payload['course_id'];

		$is_administrator = current_user_can( 'administrator' );
		$is_instructor    = \Academy\Helper::is_instructor_of_this_course( $user_id, $course_id );
		$enrolled         = \Academy\Helper::is_enrolled( $course_id, $user_id );
		$is_public = \Academy\Helper::is_public_course( $course_id );
		if ( $is_administrator || $is_instructor || $enrolled || $is_public ) {
			$prepare_response = [];
			$attempt_details = \AcademyQuizzes\Classes\Query::get_quiz_attempt_details( $attempt_id, $user_id );
			foreach ( $attempt_details as $attempt_item ) {
				$attempt_item->given_answer = \AcademyQuizzes\Helper::prepare_given_answer( $attempt_item->question_type, $attempt_item );
				$attempt_item->is_correct = (bool) $attempt_item->is_correct;
				$attempt_item->correct_answer = \AcademyQuizzes\Helper::prepare_correct_answer( $attempt_item->question_type, $attempt_item );
				$attempt_item->question_title = html_entity_decode( $attempt_item->question_title );
				$prepare_response[ $attempt_item->attempt_answer_id ] = $attempt_item;
			}
			wp_send_json_success( array_values( $prepare_response ) );
		}
		wp_send_json_error( esc_html__( 'Access Denied', 'academy' ) );
		wp_die();
	}
}
