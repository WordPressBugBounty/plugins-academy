<?php
namespace Academy\Guardian;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Free-tier guardian notifications. Course completion is recorded as an
 * `academy` `course_completed` comment, so we watch comment inserts and email
 * the learner's linked guardians. Academy Pro layers digests / inactivity
 * alerts on top of the same `academy/guardian/notify` action.
 */
class Notifications {

	public static function init() {
		$self = new self();
		add_action( 'wp_insert_comment', array( $self, 'on_comment_insert' ), 20, 2 );
	}

	public function on_comment_insert( $comment_id, $comment ) {
		if ( empty( $comment->comment_type ) || 'course_completed' !== $comment->comment_type ) {
			return;
		}
		if ( 'academy' !== $comment->comment_agent ) {
			return;
		}
		$student_id = (int) $comment->user_id;
		$course_id  = (int) $comment->comment_post_ID;
		if ( ! $student_id || ! $course_id ) {
			return;
		}
		$this->notify_course_completed( $student_id, $course_id );
	}

	public function notify_course_completed( $student_id, $course_id ) {
		$guardians = Store::get_guardians( $student_id );
		if ( empty( $guardians ) ) {
			return;
		}

		$student = get_user_by( 'id', $student_id );
		$title   = get_the_title( $course_id );

		/**
		 * Lets Academy Pro suppress the plain core email in favour of its own
		 * templated notification. Return false to skip the core wp_mail.
		 */
		$send = apply_filters( 'academy/guardian/send_completion_email', true, $student_id, $course_id );

		foreach ( $guardians as $guardian_id ) {
			$guardian = get_user_by( 'id', $guardian_id );
			if ( ! $guardian || ! is_email( $guardian->user_email ) ) {
				continue;
			}

			/**
			 * Sync/digest seam — Academy Pro hooks this to queue digest data or
			 * send its own rich email.
			 */
			do_action( 'academy/guardian/notify', 'course_completed', array(
				'guardian_id' => (int) $guardian_id,
				'student_id'  => (int) $student_id,
				'course_id'   => (int) $course_id,
			) );

			if ( ! $send ) {
				continue;
			}

			$subject = sprintf(
				/* translators: %s: learner name */
				esc_html__( '%s completed a course 🎉', 'academy' ),
				$student ? $student->display_name : esc_html__( 'Your child', 'academy' )
			);
			$body = sprintf(
				/* translators: 1: learner name, 2: course title */
				esc_html__( 'Good news! %1$s just completed "%2$s". Log in to your dashboard to see their progress.', 'academy' ),
				$student ? $student->display_name : esc_html__( 'Your child', 'academy' ),
				$title
			);

			wp_mail( $guardian->user_email, $subject, $body ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_wp_mail -- single transactional email
		}//end foreach
	}
}
