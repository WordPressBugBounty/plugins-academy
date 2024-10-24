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
			),
			'quiz_answer_manual_review' => array(
				'callback' => array( $this, 'quiz_answer_manual_review' ),
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
		$attempt_info = json_decode( $attempt['attempt_info'], true );
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
			'answer_id' => 'integer',
			'attempt_id' => 'integer',
			'question_id' => 'integer',
			'quiz_id' => 'integer',
			'user_id' => 'integer',
			'user_id' => 'string',
			'mark_as' => 'string',
		], $payload_data );

		$answer_id = ( isset( $payload['answer_id'] ) ? $payload['answer_id'] : 0 );
		$attempt_id = ( isset( $payload['attempt_id'] ) ? $payload['attempt_id'] : 0 );
		$question_id = ( isset( $payload['question_id'] ) ? $payload['question_id'] : 0 );
		$quiz_id = ( isset( $payload['quiz_id'] ) ? $payload['quiz_id'] : 0 );
		$user_id = ( isset( $payload['user_id'] ) ? $payload['user_id'] : 0 );
		$mark_as = ( isset( $payload['mark_as'] ) ? $payload['mark_as'] : '' );
		// get question
		$question = Query::get_quiz_question( $question_id );
		$answer = Query::get_quiz_attempt_answer( $answer_id );
		$answer->attempt_answer_id = $answer_id;
		$answer->question_mark = $question->question_score;
		$answer->achieved_mark = 'correct' === $mark_as ? $question->question_score : '';
		$answer->is_correct = 'correct' === $mark_as ? 1 : 0;
		// update attempt answer
		Query::quiz_attempt_answer_insert( (array) $answer );
		// update attempt
		$total_questions_marks = Query::get_total_questions_marks_by_quiz_id( $quiz_id );
		$total_earned_marks = Query::get_quiz_attempt_answers_earned_marks( $user_id, $attempt_id );
		$attempt = (array) Query::get_quiz_attempt( $attempt_id );
		$passing_grade = (int) get_post_meta( $quiz_id, 'academy_quiz_passing_grade', true );
		$earned_percentage  = \Academy\Helper::calculate_percentage( $total_questions_marks, $total_earned_marks );
		$attempt['attempt_id'] = $attempt_id;
		$attempt['total_marks'] = $total_questions_marks;
		$attempt['earned_marks'] = $total_earned_marks;
		$attempt['attempt_status'] = ( $earned_percentage >= $passing_grade ? 'passed' : 'failed' );
		$attempt_info = json_decode( $attempt['attempt_info'], true );
		$attempt_info = wp_json_encode( [ 'total_correct_answers' => Query::get_total_quiz_attempt_correct_answers( $attempt['attempt_id'] ) ] );
		$attempt['attempt_info'] = wp_json_encode( $attempt_info );
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
				'title' => get_the_title( $attempt['course_id'] ),
				'permalink' => get_the_permalink( $attempt['course_id'] )
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
				$user = $user_data->data;
				$user->admin_permalink = get_edit_user_link( $attempt['user_id'] );
				$attempt['_user'] = $user;
			}
		}

		do_action( 'academy_quizzes/after_quiz_attempt_manual_review', $attempt );

		wp_send_json_success( $attempt );
	}

}
