<?php
namespace AcademyQuizzes\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy;
use Academy\Helper;
use Academy\Classes\Sanitizer;
use Academy\Classes\AbstractAjaxHandler;
use AcademyQuizzes\Classes\Query;

class Admin extends AbstractAjaxHandler {
	protected $namespace = ACADEMY_PLUGIN_SLUG . '_quizzes';
	public function __construct() {
		$this->actions = array(
			'update_quiz_attempt_instructor_feedback' => array(
				'callback' => array( $this, 'update_quiz_attempt_instructor_feedback' ),
				'capability' => 'manage_academy_instructor'
			),
			'quiz_answer_manual_review' => array(
				'callback' => array( $this, 'quiz_answer_manual_review' ),
				'capability' => 'manage_academy_instructor',
			)
		);
	}

	public function update_quiz_attempt_instructor_feedback( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'attempt_id' => 'integer',
			'instructor_feedback' => 'string',
		], $payload_data );

		$attempt_id = ( isset( $payload['attempt_id'] ) ? $payload['attempt_id'] : 0 );
		$instructor_feedback = ( isset( $payload['instructor_feedback'] ) ? $payload['instructor_feedback'] : '' );
		// get exising attempt
		$attempt = (array) Query::get_quiz_attempt( $attempt_id );
		// Only an administrator or an instructor of the attempt's own course may add
		// feedback — the handler is gated on the site-wide manage_academy_instructor
		// capability, which alone would allow cross-course tampering.
		if ( empty( $attempt )
			|| ( ! current_user_can( 'manage_options' )
				&& ! \Academy\Helper::is_instructor_of_this_course( get_current_user_id(), (int) ( $attempt['course_id'] ?? 0 ) ) ) ) {
			wp_send_json_error( __( 'Sorry, you are not allowed to update this attempt.', 'academy' ) );
		}
		$attempt_info[] = json_decode( $attempt['attempt_info'], true );
		// prepare
		$attempt_info['instructor_feedback'] = $instructor_feedback;
		$attempt['attempt_info'] = wp_json_encode( $attempt_info );

		do_action( 'academy/frontend/quiz_attempt_status_' . $attempt['attempt_status'], $attempt );
		// update attempt
		$update = Query::quiz_attempt_insert( $attempt );
		if ( $update ) {
			wp_send_json_success( __( 'Successfully updated instructor feedback.', 'academy' ) );
		}
		wp_send_json_error( __( 'Sorry, Failed to update instructor feedback.', 'academy' ) );
	}

	public function quiz_answer_manual_review( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'answer_id'   => 'integer',
			'attempt_id'  => 'integer',
			'question_id' => 'integer',
			'quiz_id'     => 'integer',
			'mark_as'     => 'string',
			// NOTE: 'user_id' intentionally removed from client-controlled input
		], $payload_data );

		$answer_id   = ( isset( $payload['answer_id'] ) ? $payload['answer_id'] : 0 );
		$attempt_id  = ( isset( $payload['attempt_id'] ) ? $payload['attempt_id'] : 0 );
		$question_id = ( isset( $payload['question_id'] ) ? $payload['question_id'] : 0 );
		$quiz_id     = ( isset( $payload['quiz_id'] ) ? $payload['quiz_id'] : 0 );
		$mark_as     = ( isset( $payload['mark_as'] ) ? $payload['mark_as'] : '' );

		// Fetch the attempt FIRST and derive user_id/quiz_id from the trusted DB record,
		// never from client input.
		$attempt_row = Query::get_quiz_attempt( $attempt_id );
		if ( empty( $attempt_row ) ) {
			wp_send_json_error( __( 'Invalid attempt.', 'academy' ) );
		}

		// The reviewer must be an administrator or an instructor of the attempt's
		// own course. manage_academy_instructor is a site-wide capability, so
		// without this an instructor could re-grade attempts in other instructors'
		// courses.
		if ( ! current_user_can( 'manage_options' )
			&& ! \Academy\Helper::is_instructor_of_this_course( get_current_user_id(), (int) $attempt_row->course_id ) ) {
			wp_send_json_error( __( 'Sorry, you are not allowed to review this attempt.', 'academy' ) );
		}

		$user_id       = (int) $attempt_row->user_id;
		$real_quiz_id  = (int) $attempt_row->quiz_id;

		// Defense in depth: if a quiz_id was supplied, it must match the attempt's actual quiz.
		if ( $quiz_id && $quiz_id !== $real_quiz_id ) {
			wp_send_json_error( __( 'Attempt does not belong to the given quiz.', 'academy' ) );
		}
		$quiz_id = $real_quiz_id;

		// get question
		$question = Query::get_quiz_question( $question_id );
		$answer   = Query::get_quiz_attempt_answer( $answer_id );

		// Ownership checks: make sure answer/question actually belong to this attempt/quiz
		// to prevent cross-record tampering via mismatched IDs.
		if ( empty( $question ) || empty( $answer )
			|| (int) $answer->attempt_id !== (int) $attempt_id
			|| (int) $question->quiz_id !== (int) $quiz_id ) {
			wp_send_json_error( __( 'Mismatched answer/question/attempt data.', 'academy' ) );
		}

		$answer->attempt_answer_id = $answer_id;
		$answer->question_mark     = $question->question_score;
		$answer->achieved_mark     = 'correct' === $mark_as ? $question->question_score : ( - $question->question_negative_score ?? '' );
		$answer->is_correct        = 'correct' === $mark_as ? 1 : 0;

		// update attempt answer
		Query::quiz_attempt_answer_insert( (array) $answer );

		// update attempt
		$total_questions_marks = Query::get_total_questions_marks_by_attempt_id( $attempt_id );
		$total_earned_marks    = Query::get_quiz_attempt_answers_earned_marks( $user_id, $attempt_id );
		$attempt               = (array) Query::get_quiz_attempt( $attempt_id );
		$passing_grade         = (int) get_post_meta( $quiz_id, 'academy_quiz_passing_grade', true );
		$earned_percentage     = \Academy\Helper::calculate_percentage( $total_questions_marks, $total_earned_marks );

		$attempt['attempt_id']      = $attempt_id;
		$attempt['total_marks']     = $total_questions_marks;
		$attempt['earned_marks']    = $total_earned_marks;
		$attempt['attempt_status']  = ( $earned_percentage >= $passing_grade ? 'passed' : 'failed' );

		$attempt_info = wp_json_encode( [
			'total_correct_answers' => Query::get_total_quiz_attempt_correct_answers( $attempt['attempt_id'] ),
		] );
		$attempt['attempt_info']         = $attempt_info;
		$attempt['is_manually_reviewed'] = 1;
		$attempt['manually_reviewed_at'] = current_time( 'mysql' );

		// update attempt manually
		Query::update_quiz_attempt_by_manual_review( $attempt );

		// get updated attempt
		$attempt = (array) Query::get_quiz_attempt( $attempt_id );
		if ( isset( $attempt['attempt_info'] ) ) {
			$attempt['attempt_info'] = json_decode( $attempt['attempt_info'], true );
		}
		if ( isset( $attempt['course_id'] ) ) {
			$attempt['_course'] = array(
				'title'     => get_the_title( $attempt['course_id'] ),
				'permalink' => get_the_permalink( $attempt['course_id'] ),
			);
		}
		if ( isset( $attempt['quiz_id'] ) ) {
			$attempt['_quiz'] = array(
				'title' => get_the_title( $attempt['quiz_id'] ),
			);
		}
		if ( isset( $attempt['user_id'] ) ) {
			$user_data = get_userdata( $attempt['user_id'] );
			if ( $user_data ) {
				// Don't leak full user object (contains password hash etc). Expose a safe subset.
				$attempt['_user'] = array(
					'ID'             => $user_data->ID,
					'display_name'   => $user_data->display_name,
					'user_email'     => $user_data->user_email,
					'admin_permalink'=> get_edit_user_link( $attempt['user_id'] ),
				);
			}
		}

		do_action( 'academy_quizzes/after_quiz_attempt_manual_review', $attempt );

		wp_send_json_success( $attempt );
	}

}
