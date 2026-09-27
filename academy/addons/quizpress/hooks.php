<?php
namespace AcademyQuizpress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Hooks {
	public static function init() {
		$self = new self();
		// Mark as complete
		add_action( 'academy/frontend/before_mark_topic_complete', array( $self, 'mark_quizpress_quiz_complete' ), 11, 4 );
	}

	/**
	 * Mirrors AcademyQuizzes\Hooks::mark_quiz_complete() — blocks marking the
	 * topic complete unless a passing attempt exists. Pass/fail is read live
	 * from QuizPress's own attempts table (QuizPress\API\Query\Attempts) —
	 * this addon never stores its own copy of attempt/grading data.
	 *
	 * @param string $topic_type
	 * @param int    $course_id
	 * @param int    $topic_id
	 * @param int    $user_id
	 */
	public function mark_quizpress_quiz_complete( $topic_type, $course_id, $topic_id, $user_id ) {
		if ( 'quizpress_quiz' !== $topic_type ) {
			return;
		}

		$passed_attempts = \QuizPress\API\Query\Attempts::get_passed_attempt( $topic_id, $user_id );

		if ( empty( $passed_attempts ) ) {
			if ( \Academy\Helper::is_server_learn_page() ) {
				wp_safe_redirect( \Academy\Helper::sanitize_referer_url( wp_get_referer() ) );
				exit;
			}

			$attempts = \QuizPress\API\Query\Attempts::get_attempts_by_quiz_and_user_id( $topic_id, $user_id );

			$awaiting_review = false;
			foreach ( (array) $attempts as $attempt ) {
				if ( ! empty( $attempt->attempt_ended_at ) && 'pending' === $attempt->attempt_status && empty( $attempt->is_manually_reviewed ) ) {
					$awaiting_review = true;
					break;
				}
			}

			if ( $awaiting_review ) {
				$message = __( 'Your quiz answers are awaiting instructor review. This lesson can be marked complete once it has been reviewed.', 'academy' );
			} elseif ( $attempts ) {
				$message = __( 'Pass the quiz before marking it as done.', 'academy' );
			} else {
				$message = __( 'Complete the quiz before marking it as done.', 'academy' );
			}
			wp_send_json_error( $message );
		}//end if
	}
}
