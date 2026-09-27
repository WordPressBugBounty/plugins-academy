<?php

namespace AcademyQuizzes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Hooks {
	public static function init() {
		$self = new self();
		// Mark as complete
		add_action( 'academy/frontend/before_mark_topic_complete', array( $self, 'mark_quiz_complete' ), 11, 4 );
		// AI feedback per question, once the attempt is graded
		add_action( 'academy_quizzes/api/after_quiz_attempt_finished', array( $self, 'generate_ai_feedback_for_attempt' ) );
	}

	public function mark_quiz_complete( $topic_type, $course_id, $topic_id, $user_id ) {
		if ( 'quiz' === $topic_type ) {
			$attempts = \AcademyQuizzes\Helper::has_attempt_quiz( $course_id, $topic_id, $user_id );

			$is_passed = false;
			if ( $attempts ) {
				foreach ( $attempts as $attempt ) {
					if ( 'passed' === $attempt->attempt_status ) {
						$is_passed = true;
						break;
					}
				}
			}

			if ( ! $attempts || ! $is_passed ) {
				if ( \Academy\Helper::is_server_learn_page() ) {
					wp_safe_redirect( \Academy\Helper::sanitize_referer_url( wp_get_referer() ) );
					exit;
				}

				$message = $attempts ? __( 'Pass the quiz before marking it as done.', 'academy' ) : __( 'Complete the quiz before marking it as done.', 'academy' );
				wp_send_json_error( $message );
			}
		}//end if
	}

	/**
	 * Generates and stores a short AI feedback message for each answered
	 * question of a just-finished attempt. Silently does nothing if no AI
	 * provider is available (see Classes\AiFeedback).
	 *
	 * @param object $attempt The row from academy_quiz_attempts, as returned by Classes\Query::get_quiz_attempt().
	 */
	public function generate_ai_feedback_for_attempt( $attempt ) {
		if ( empty( $attempt->attempt_id ) || ! \AcademyQuizzes\Classes\AiFeedback::is_available() ) {
			return;
		}

		$questions = \AcademyQuizzes\Helper::get_quiz_attempt_answer_details_by_attempt_id( $attempt->attempt_id );

		foreach ( $questions as $question ) {
			if ( empty( $question->attempt_answer_id ) ) {
				continue; // skipped question — nothing answered, nothing to feed back on
			}

			if ( 'shortAnswer' === $question->question_type && empty( $question->is_manually_reviewed ) ) {
				continue; // still awaiting a teacher's review — there's no verdict to explain yet
			}

			$feedback = \AcademyQuizzes\Classes\AiFeedback::generate(
				array(
					'question'       => wp_strip_all_tags( html_entity_decode( $question->question_title ) ),
					'given_answer'   => \AcademyQuizzes\Classes\AiFeedback::format_answer( $question->given_answer ),
					'correct_answer' => \AcademyQuizzes\Classes\AiFeedback::format_answer( $question->correct_answer ),
					'is_correct'     => (bool) $question->is_correct,
				)
			);

			if ( null !== $feedback ) {
				\AcademyQuizzes\Classes\Query::update_quiz_attempt_answer_ai_feedback( $question->attempt_answer_id, $feedback );
			}
		}//end foreach
	}
}
