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
				'capability' => 'manage_academy_instructor',
			),
			'quiz_answer_manual_review' => array(
				'callback' => array( $this, 'quiz_answer_manual_review' ),
				'capability' => 'manage_academy_instructor',
			),
			'get_student_quiz_courses' => array(
				'callback' => array( $this, 'get_student_quiz_courses' ),
				'capability' => 'manage_academy_instructor',
			),
			'get_student_attempted_quizzes' => array(
				'callback' => array( $this, 'get_student_attempted_quizzes' ),
				'capability' => 'manage_academy_instructor',
			),
			'get_student_quiz_attempts' => array(
				'callback' => array( $this, 'get_student_quiz_attempts' ),
				'capability' => 'manage_academy_instructor',
			),
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
		// manage_academy_instructor is site-wide, so also require the attempt's
		// own course; otherwise any instructor could edit any course's attempts.
		if ( empty( $attempt ) || ! self::can_manage_course( (int) ( $attempt['course_id'] ?? 0 ) ) ) {
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
		], $payload_data );

		$answer_id   = ( isset( $payload['answer_id'] ) ? $payload['answer_id'] : 0 );
		$attempt_id  = ( isset( $payload['attempt_id'] ) ? $payload['attempt_id'] : 0 );
		$question_id = ( isset( $payload['question_id'] ) ? $payload['question_id'] : 0 );
		$quiz_id     = ( isset( $payload['quiz_id'] ) ? $payload['quiz_id'] : 0 );
		$mark_as     = ( isset( $payload['mark_as'] ) ? $payload['mark_as'] : '' );

		// The student and quiz come from the stored attempt, never the request.
		$attempt_row = Query::get_quiz_attempt( $attempt_id );
		if ( empty( $attempt_row ) ) {
			wp_send_json_error( __( 'Invalid attempt.', 'academy' ) );
		}
		if ( ! self::can_manage_course( (int) $attempt_row->course_id ) ) {
			wp_send_json_error( __( 'Sorry, you are not allowed to review this attempt.', 'academy' ) );
		}
		if ( $quiz_id && (int) $attempt_row->quiz_id !== $quiz_id ) {
			wp_send_json_error( __( 'Attempt does not belong to the given quiz.', 'academy' ) );
		}
		$user_id = (int) $attempt_row->user_id;
		$quiz_id = (int) $attempt_row->quiz_id;

		// The answer must be this attempt's answer to this question. A question
		// can be shared by several quizzes, so match it through the answer row.
		$question = Query::get_quiz_question( $question_id );
		$answer   = Query::get_quiz_attempt_answer( $answer_id );
		if ( empty( $question ) || empty( $answer )
			|| (int) $answer->attempt_id !== (int) $attempt_id
			|| (int) $answer->question_id !== (int) $question_id ) {
			wp_send_json_error( __( 'Mismatched answer/question/attempt data.', 'academy' ) );
		}

		$answer->attempt_answer_id = $answer_id;
		$answer->question_mark = $question->question_score;
		$answer->achieved_mark = 'correct' === $mark_as ? $question->question_score : ( - $question->question_negative_score ?? '' );
		$answer->is_correct = 'correct' === $mark_as ? 1 : 0;
		// update attempt answer
		Query::quiz_attempt_answer_insert( (array) $answer );
		// update attempt
		$total_questions_marks = Query::get_total_questions_marks_by_attempt_id( $attempt_id );
		$total_earned_marks = Query::get_quiz_attempt_answers_earned_marks( $user_id, $attempt_id );
		$attempt = (array) Query::get_quiz_attempt( $attempt_id );
		$passing_grade = (int) get_post_meta( $quiz_id, 'academy_quiz_passing_grade', true );
		$earned_percentage  = \Academy\Helper::calculate_percentage( $total_questions_marks, $total_earned_marks );
		$attempt['attempt_id'] = $attempt_id;
		$attempt['total_marks'] = $total_questions_marks;
		$attempt['earned_marks'] = $total_earned_marks;
		$attempt['attempt_status'] = ( $earned_percentage >= $passing_grade ? 'passed' : 'failed' );
		$attempt['attempt_info'] = wp_json_encode( [ 'total_correct_answers' => Query::get_total_quiz_attempt_correct_answers( $attempt['attempt_id'] ) ] );
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

	/**
	 * Courses a given student has at least one quiz attempt in.
	 *
	 * Feeds the "Course" dropdown of the Quiz Answers view in Student Details.
	 *
	 * @param array $payload_data
	 */
	public function get_student_quiz_courses( $payload_data ) {
		$payload = Sanitizer::sanitize_payload( array(
			'student_id' => 'integer',
		), $payload_data );

		$student_id = isset( $payload['student_id'] ) ? $payload['student_id'] : 0;
		if ( ! $student_id ) {
			wp_send_json_error( __( 'Student not found.', 'academy' ) );
		}

		$courses = array();
		foreach ( Query::get_courses_with_quiz_attempts_by_user( $student_id ) as $course_id ) {
			if ( ! self::can_manage_course( (int) $course_id ) ) {
				continue;
			}
			$courses[] = array(
				'id'    => (int) $course_id,
				'title' => html_entity_decode( get_the_title( $course_id ) ),
			);
		}

		wp_send_json_success( $courses );
	}

	/**
	 * Quizzes a given student has attempted within one course.
	 *
	 * Feeds the "Quiz" dropdown of the Quiz Answers view in Student Details,
	 * once a course has been picked.
	 *
	 * @param array $payload_data
	 */
	public function get_student_attempted_quizzes( $payload_data ) {
		$payload = Sanitizer::sanitize_payload( array(
			'student_id' => 'integer',
			'course_id'  => 'integer',
		), $payload_data );

		$student_id = isset( $payload['student_id'] ) ? $payload['student_id'] : 0;
		$course_id  = isset( $payload['course_id'] ) ? $payload['course_id'] : 0;
		if ( ! $student_id || ! $course_id ) {
			wp_send_json_error( __( 'Student or course not found.', 'academy' ) );
		}
		if ( ! self::can_manage_course( $course_id ) ) {
			wp_send_json_error( __( 'Sorry, you are not allowed to view this course.', 'academy' ) );
		}

		$quizzes = array();
		foreach ( Query::get_students_own_quiz_grades_by_course( $student_id, $course_id ) as $grade ) {
			$quizzes[] = array(
				'id'    => (int) $grade->quiz_id,
				'title' => html_entity_decode( get_the_title( $grade->quiz_id ) ),
			);
		}

		wp_send_json_success( $quizzes );
	}

	/**
	 * A given student's attempts on one quiz, newest first.
	 *
	 * Feeds the "Attempt" dropdown of the Quiz Answers view in Student
	 * Details, once a course and quiz have been picked; the answers
	 * themselves are then fetched per-attempt via the existing
	 * `academy_quizzes/get_student_quiz_attempt_details` action.
	 *
	 * @param array $payload_data
	 */
	public function get_student_quiz_attempts( $payload_data ) {
		$payload = Sanitizer::sanitize_payload( array(
			'student_id' => 'integer',
			'course_id'  => 'integer',
			'quiz_id'    => 'integer',
		), $payload_data );

		$student_id = isset( $payload['student_id'] ) ? $payload['student_id'] : 0;
		$course_id  = isset( $payload['course_id'] ) ? $payload['course_id'] : 0;
		$quiz_id    = isset( $payload['quiz_id'] ) ? $payload['quiz_id'] : 0;
		if ( ! $student_id || ! $course_id || ! $quiz_id ) {
			wp_send_json_error( __( 'Student, course or quiz not found.', 'academy' ) );
		}
		if ( ! self::can_manage_course( $course_id ) ) {
			wp_send_json_error( __( 'Sorry, you are not allowed to view this course.', 'academy' ) );
		}

		$attempts = Query::get_quiz_attempt_details_by_quiz_id( array(
			'quiz_id'   => $quiz_id,
			'course_id' => $course_id,
			'user_id'   => $student_id,
			'per_page'  => 50,
			'offset'    => 0,
		) );

		$data = array();
		foreach ( $attempts as $attempt ) {
			$data[] = array(
				'attempt_id'               => (int) $attempt->attempt_id,
				'attempt_started_at'       => $attempt->attempt_started_at,
				'total_questions'          => (int) $attempt->total_questions,
				'total_answered_questions' => (int) $attempt->total_answered_questions,
				'total_correct_answer'     => (int) $attempt->total_correct_answer,
				'total_marks'              => (float) $attempt->total_marks,
				'earned_marks'             => (float) $attempt->earned_marks,
				'attempt_status'           => $attempt->attempt_status,
			);
		}

		wp_send_json_success( $data );
	}

	/**
	 * Whether the current user may manage a course's quiz results: an
	 * administrator, or an instructor of that course.
	 *
	 * @param int $course_id Course ID.
	 * @return bool
	 */
	private static function can_manage_course( $course_id ) {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		return $course_id > 0 && Helper::is_instructor_of_this_course( get_current_user_id(), $course_id );
	}
}
