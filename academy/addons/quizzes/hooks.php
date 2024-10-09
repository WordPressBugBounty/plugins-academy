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
				if ( \Academy\Helper::get_settings( 'is_enabled_lessons_php_render' ) ) {
					wp_safe_redirect( \Academy\Helper::sanitize_referer_url( wp_get_referer() ) );
					exit;
				}

				$message = $attempts ? __( 'Pass the quiz before marking it as done.', 'academy' ) : __( 'Complete the quiz before marking it as done.', 'academy' );
				wp_send_json_error( $message );
			}
		}//end if
	}
}
