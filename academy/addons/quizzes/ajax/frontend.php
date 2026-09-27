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
			'get_resume_attempt_answers' => array(
				'callback' => array( $this, 'get_resume_attempt_answers' ),
				'capability' => 'read'
			),
			'update_resume_progress' => array(
				'callback' => array( $this, 'update_resume_progress' ),
				'capability' => 'read'
			),
		);
	}

	public function mark_quiz_complete( $topic_type, $course_id, $topic_id, $user_id ) {
		if ( 'quiz' === $topic_type && ! \AcademyQuizzes\Classes\Query::has_attempt_quiz( $course_id, $topic_id, $user_id ) ) {
			if ( \Academy\Helper::is_server_learn_page() ) {
				$referer_url = \Academy\Helper::sanitize_referer_url( wp_get_referer() );
				wp_safe_redirect( $referer_url );
				exit;
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
			$layout = get_post_meta( $quiz_id, 'academy_quiz_questions_layout', true );
			$order = get_post_meta( $quiz_id, 'academy_quiz_questions_order', true );
			if ( count( $questions ) && $order ) {
				do_action( 'academy_quizzes/frontend/before_render_quiz', $course_id, $quiz_id );
				if ( 'all' === $layout ) {
					foreach ( $questions as &$question ) {
						$question->answers = \AcademyQuizzes\Classes\Query::get_quiz_answers_by_question_id( $question->question_id, $question->question_type );
					}
					unset( $question );
				}
				$settings = \AcademyQuizzes\Classes\Query::get_question_settings_by_quiz_id( $quiz_id, $order );
				$settings['quiz_proctoring_browser_lock'] = (bool) \Academy\Helper::get_settings( 'quiz_proctoring_browser_lock', false );
				wp_send_json_success([
					'questions' => $questions,
					'settings' => $settings,
					'content' => get_post_field( 'post_content', $quiz_id ),
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

		$is_administrator = current_user_can( 'manage_options' );
		$is_instructor    = \Academy\Helper::is_instructor_of_this_course( $user_id, $course_id );
		$enrolled         = \Academy\Helper::is_enrolled( $course_id, $user_id );
		$is_public        = \Academy\Helper::is_public_course( $course_id );

		// Access above is scoped to $course_id (attacker-controlled and independent
		// of the question), so also verify $question_id actually belongs to a quiz
		// in that course's curriculum — otherwise an enrolled/public/*own-taught*
		// course_id lets the caller read the answer key of a question from a
		// different, private course by supplying any course they happen to have
		// access to alongside someone else's question_id. Only a real site
		// administrator bypasses this; being an instructor of $course_id is NOT
		// enough, since $course_id itself is the attacker-controlled parameter.
		$question_in_course = $is_administrator;
		if ( ! $question_in_course ) {
			foreach ( \AcademyQuizzes\Classes\Query::get_quizzes_using_question( $question_id ) as $quiz ) {
				if ( \Academy\Helper::is_course_curriculum( $course_id, (int) $quiz['id'], 'quiz' ) ) {
					$question_in_course = true;
					break;
				}
			}
		}

		if ( ( $is_administrator || $is_instructor || $enrolled || $is_public ) && $question_in_course ) {
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
		$is_administrator = current_user_can( 'manage_options' );
		$is_instructor    = \Academy\Helper::is_instructor_of_this_course( $user_id, $course_id );
		$enrolled         = \Academy\Helper::is_enrolled( $course_id, $user_id );
		$is_public = \Academy\Helper::is_public_course( $course_id );

		// Every answer row below is written with THIS user's id regardless of whose
		// attempt_id was submitted, so without this check a caller could pass
		// another student's attempt_id — course_id/enrollment alone would still
		// pass above, and being an instructor of the SUBMITTED course_id is not
		// enough either, since course_id is attacker-controlled — and overwrite
		// that other attempt's saved answers/scores. Only a real administrator
		// bypasses this.
		$attempt = \AcademyQuizzes\Classes\Query::get_quiz_attempt( $attempt_id );
		$owns_attempt = $attempt && (int) $attempt->user_id === $user_id
			&& (int) $attempt->quiz_id === (int) $quiz_id
			&& (int) $attempt->course_id === (int) $course_id;

		if ( ( $is_administrator || $is_instructor || $enrolled || $is_public ) && ( $is_administrator || $owns_attempt ) ) {
			// Check if JSON data was received
			if ( ! empty( $attempt_answers ) ) {
				// Decode the JSON string into a PHP array
				$attempt_answers = json_decode( $attempt_answers, true );
				$results = [];
				if ( is_array( $attempt_answers ) && count( $attempt_answers ) ) {
					$achieved_score = 0;
					$score_total = 0;
					$quiz_questions = \AcademyQuizzes\Classes\Query::get_quiz_questions_for_scoring( $quiz_id );
					foreach ( $attempt_answers as $attempt_answer ) {
						$question_id = (int) ( $attempt_answer['question_id'] ?? 0 );
						// Only this quiz's questions count, scored as stored.
						if ( ! isset( $quiz_questions[ $question_id ] ) ) {
							continue;
						}
						$question_score = (float) $quiz_questions[ $question_id ]->question_score;
						$question_type = (string) $quiz_questions[ $question_id ]->question_type;
						$given_answer = $attempt_answer['given_answer'] ?? '';
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

						$negative_score = 0;
						$negative_mark = floatval( current( \AcademyQuizzes\Classes\Query::get_question_details_by_question_id( $question_id ) )->question_negative_score );
						if ( ! empty( $given_answer ) && $negative_mark > 0 && empty( $correct_answer ) ) {
							$negative_score -= $negative_mark;
						}

						$score_total   += $question_score;
						$achieved_score += $correct_answer ? $question_score : $negative_score;

						// Upsert: a Resume Mode attempt may already have a saved
						// row for this question (from autosave or a pre-pause
						// session), so reuse it instead of inserting a
						// duplicate row on final submit.
						$existing_answer = \AcademyQuizzes\Classes\Query::get_quiz_attempt_answer_by_question( $attempt_id, $question_id );

						$answer_args = array(
							'user_id'           => $user_id,
							'quiz_id'           => $quiz_id,
							'question_id'       => $question_id,
							'attempt_id'        => $attempt_id,
							'answer'            => $given_answer,
							'question_mark'     => $question_score,
							'achieved_mark'     => $correct_answer ? $question_score : ( $negative_score ?? '' ),
							'minus_mark'        => '',
							'is_correct'        => $correct_answer,
						);
						if ( $existing_answer ) {
							$answer_args['attempt_answer_id'] = $existing_answer->attempt_answer_id;
						}

						$results[] = \AcademyQuizzes\Classes\Query::quiz_attempt_answer_insert( $answer_args );
					}//end foreach

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
			// Resume Mode: current wizard step + remaining timer seconds,
			// piggybacked on the same autosave call so no extra round-trip
			// is needed just to keep resume position/timer in sync.
			'current_step_index' => 'integer',
			'remaining_seconds' => 'integer',
		], $payload_data );

		$course_id = $payload['course_id'];
		$quiz_id = $payload['quiz_id'];
		$attempt_id = $payload['attempt_id'];
		$question_id = $payload['question_id'];
		$question_score = $payload['question_score'];
		$question_type = $payload['question_type'];
		$given_answer = $payload['given_answer'];

		$user_id   = (int) get_current_user_id();
		$is_administrator = current_user_can( 'manage_options' );
		$is_instructor    = \Academy\Helper::is_instructor_of_this_course( $user_id, $course_id );
		$enrolled         = \Academy\Helper::is_enrolled( $course_id, $user_id );
		$is_public = \Academy\Helper::is_public_course( $course_id );

		// Same as insert_quiz_answers(): the attempt must be the caller's own,
		// for this quiz and course, or they could overwrite another student's
		// saved answers.
		$attempt = \AcademyQuizzes\Classes\Query::get_quiz_attempt( $attempt_id );
		$owns_attempt = $attempt && (int) $attempt->user_id === $user_id
			&& (int) $attempt->quiz_id === (int) $quiz_id
			&& (int) $attempt->course_id === (int) $course_id;
		$quiz_questions = $owns_attempt ? \AcademyQuizzes\Classes\Query::get_quiz_questions_for_scoring( $quiz_id ) : array();

		if ( ( $is_administrator || $is_instructor || $enrolled || $is_public ) && $owns_attempt && isset( $quiz_questions[ $question_id ] ) ) {
			// Scored as stored, never as the request says.
			$question_score = (float) $quiz_questions[ $question_id ]->question_score;
			$question_type  = (string) $quiz_questions[ $question_id ]->question_type;
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

			// Upsert: reuse the existing saved-answer row for this
			// question if one already exists, instead of inserting a
			// duplicate row every time the student changes their answer
			// (this endpoint is now also used for Resume Mode autosave,
			// which calls it repeatedly for the same question).
			$existing_answer = \AcademyQuizzes\Classes\Query::get_quiz_attempt_answer_by_question( $attempt_id, $question_id );

			$answer_args = array(
				'user_id'           => $user_id,
				'quiz_id'           => $quiz_id,
				'question_id'       => $question_id,
				'attempt_id'        => $attempt_id,
				'answer'            => $given_answer,
				'question_mark'     => $question_score,
				'achieved_mark'     => $correct_answer ? $question_score : '',
				'minus_mark'        => '',
				'is_correct'        => $correct_answer,
			);
			if ( $existing_answer ) {
				$answer_args['attempt_answer_id'] = $existing_answer->attempt_answer_id;
			}

			$attempt_answer = \AcademyQuizzes\Classes\Query::quiz_attempt_answer_insert( $answer_args );

			if ( isset( $payload['current_step_index'] ) || isset( $payload['remaining_seconds'] ) ) {
				$resume_partial = array();
				if ( isset( $payload['current_step_index'] ) ) {
					$resume_partial['current_step_index'] = $payload['current_step_index'];
				}
				if ( isset( $payload['remaining_seconds'] ) ) {
					$resume_partial['remaining_seconds'] = $payload['remaining_seconds'];
				}
				$resume_partial['last_saved_at'] = current_time( 'mysql' );
				$resume_partial['submitted']     = false;
				\AcademyQuizzes\Classes\Query::update_quiz_attempt_resume_info( $attempt_id, $resume_partial );
			}

			wp_send_json_success( $attempt_answer );
		}//end if
		wp_send_json_error( esc_html__( 'Access Denied', 'academy' ) );
		wp_die();
	}

	/**
	 * Resume Mode: lightweight heartbeat that persists only the current
	 * step + remaining timer seconds, independent of insert_quiz_answer()
	 * -- that endpoint only fires when an answer actually changes, so a
	 * student who leaves mid-countdown without answering anything new
	 * would otherwise resume to a stale/reset timer.
	 *
	 * @param array $payload_data
	 */
	public function update_resume_progress( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
			'quiz_id' => 'integer',
			'attempt_id' => 'integer',
			'current_step_index' => 'integer',
			'remaining_seconds' => 'integer',
		], $payload_data );

		$attempt_id = $payload['attempt_id'] ?? 0;
		$user_id    = (int) get_current_user_id();
		$attempt    = \AcademyQuizzes\Classes\Query::get_quiz_attempt( $attempt_id );

		if ( ! $attempt || (int) $attempt->user_id !== $user_id ) {
			wp_send_json_error( esc_html__( 'Access Denied', 'academy' ) );
		}

		$resume_partial = array(
			'last_saved_at' => current_time( 'mysql' ),
			'submitted'     => false,
		);
		if ( isset( $payload['current_step_index'] ) ) {
			$resume_partial['current_step_index'] = $payload['current_step_index'];
		}
		if ( isset( $payload['remaining_seconds'] ) ) {
			$resume_partial['remaining_seconds'] = $payload['remaining_seconds'];
		}
		\AcademyQuizzes\Classes\Query::update_quiz_attempt_resume_info( $attempt_id, $resume_partial );

		wp_send_json_success();
	}

	public function get_student_quiz_attempt_details( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
			'attempt_id' => 'integer',
			'user_id' => 'integer',
		], $payload_data );

		$attempt_id = $payload['attempt_id'];

		// Authorize against the attempt's real owner and course, never the
		// user_id/course_id in the request: its owner, an instructor of its
		// course, or an administrator.
		$attempt          = \AcademyQuizzes\Classes\Query::get_quiz_attempt( $attempt_id );
		$current_user_id  = get_current_user_id();
		$is_administrator = current_user_can( 'manage_options' );
		$is_owner         = ! empty( $attempt ) && (int) $attempt->user_id === (int) $current_user_id;
		$is_instructor    = ! empty( $attempt ) && (bool) \Academy\Helper::is_instructor_of_this_course( $current_user_id, (int) $attempt->course_id );

		if ( ! empty( $attempt ) && ( $is_administrator || $is_owner || $is_instructor ) ) {
			$user_id = (int) $attempt->user_id;
			$prepare_response = [];
			$attempt_details = \AcademyQuizzes\Classes\Query::get_quiz_attempt_details( $attempt_id, $user_id );
			$quiz_id = $attempt->quiz_id;
			$is_enable_skip_question = get_post_meta( $quiz_id, 'academy_quiz_skip_question_showing', true );
			$is_hide_see_more_button = (bool) get_post_meta( $quiz_id, 'academy_quiz_show_full_answer_content', true );
			if ( $is_enable_skip_question ) {
				$skip_questions = \AcademyQuizzes\Classes\Query::get_quiz_attempt_skip_questions( $attempt_id, $user_id, $quiz_id );
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
				$attempt_item->academy_quiz_show_full_answer_content = $is_hide_see_more_button;
				$prepare_response[ $attempt_answer_id ] = $attempt_item;
			}

			wp_send_json_success( array_values( $prepare_response ) );
		}//end if
		wp_send_json_error( esc_html__( 'Access Denied', 'academy' ) );
		wp_die();
	}

	/**
	 * Resume Mode: raw saved answers for an in-progress attempt, so the
	 * frontend can prefill the live editable quiz (not the display-only
	 * shape get_student_quiz_attempt_details() returns for the results page).
	 *
	 * @param array $payload_data
	 */
	public function get_resume_attempt_answers( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
			'attempt_id' => 'integer',
		], $payload_data );

		$attempt_id = $payload['attempt_id'];
		$course_id = $payload['course_id'];
		$user_id = get_current_user_id();

		$is_administrator = current_user_can( 'manage_options' );
		$is_instructor    = \Academy\Helper::is_instructor_of_this_course( $user_id, $course_id );
		$enrolled         = \Academy\Helper::is_enrolled( $course_id, $user_id );
		$is_public = \Academy\Helper::is_public_course( $course_id );

		if ( $is_administrator || $is_instructor || $enrolled || $is_public ) {
			$attempt = \AcademyQuizzes\Classes\Query::get_quiz_attempt( $attempt_id );
			// Only the attempt's own owner may pull its saved answers back
			// into a live quiz session.
			if ( ! $attempt || (int) $attempt->user_id !== (int) $user_id ) {
				wp_send_json_error( esc_html__( 'Access Denied', 'academy' ) );
				wp_die();
			}
			$answers = \AcademyQuizzes\Classes\Query::get_quiz_attempt_raw_answers( $attempt_id, $user_id );
			wp_send_json_success( $answers );
		}//end if
		wp_send_json_error( esc_html__( 'Access Denied', 'academy' ) );
		wp_die();
	}
}
