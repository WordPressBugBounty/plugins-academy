<?php
namespace AcademyQuizpress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class API {
	public static function init() {
		$self = new self();
		add_filter( 'rest_pre_dispatch', array( $self, 'gate_attempt_start' ), 10, 3 );
		add_filter( 'academy/export-import/get_quizpress_quiz_data', array( $self, 'get_quizpress_quiz_export_data' ) );
	}

	/**
	 * QuizPress's own attempt-start endpoint only checks "logged in (or
	 * guests-allowed)" — it has no concept of an Academy course/enrollment,
	 * and its `before_quiz_attempt_start` action is a non-blocking do_action
	 * fired after the insert is already prepared. rest_pre_dispatch is the
	 * only clean point to reject a start request for a course-linked quiz
	 * when the current user isn't enrolled.
	 *
	 * @param mixed            $result
	 * @param mixed            $server
	 * @param \WP_REST_Request $request
	 */
	public function gate_attempt_start( $result, $server, $request ) {
		if ( null !== $result ) {
			return $result;
		}

		if ( 'POST' !== $request->get_method() || false === strpos( $request->get_route(), '/quizpress/v1/attempts' ) ) {
			return $result;
		}

		$quiz_id = (int) $request->get_param( 'quiz_id' );
		if ( ! $quiz_id ) {
			return $result;
		}

		$course_id = self::get_linked_course_id( $quiz_id );
		if ( ! $course_id ) {
			// Not linked to any Academy course — normal QuizPress behavior applies.
			return $result;
		}

		if ( \Academy\Helper::is_public_course( $course_id ) || \Academy\Helper::is_enrolled( $course_id, get_current_user_id() ) ) {
			return $result;
		}

		return new \WP_Error(
			'academy_quizpress_not_enrolled',
			esc_html__( 'You must be enrolled in this course to take this quiz.', 'academy' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Which Academy course (if any) currently links to this QuizPress quiz.
	 * A quiz can only meaningfully gate against one course at a time here —
	 * if it's attached to more than one, the first link found is used.
	 *
	 * @param int $quiz_id
	 */
	public static function get_linked_course_id( $quiz_id ) {
		global $wpdb;
		$table = $wpdb->prefix . ACADEMY_PLUGIN_SLUG . '_quizpress_quiz_links';
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT course_id FROM {$table} WHERE quiz_id = %d LIMIT 1", $quiz_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- only $wpdb-prefixed table names / generated placeholders are interpolated; values are prepared
	}

	/**
	 * Whether this course has at least one quizpress_quiz topic attached —
	 * used by Frontend to decide whether to enqueue QuizPress's player bundle.
	 *
	 * @param int $course_id
	 */
	public static function course_has_linked_quiz( $course_id ) {
		global $wpdb;
		$table = $wpdb->prefix . ACADEMY_PLUGIN_SLUG . '_quizpress_quiz_links';
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$table} WHERE course_id = %d LIMIT 1", $course_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- only $wpdb-prefixed table names / generated placeholders are interpolated; values are prepared
	}

	/**
	 * CSV export — quizpress_quiz topics are references, not clones. Only
	 * identifying data (title) is needed so import can re-resolve the same
	 * existing quiz by title, mirroring how quiz/assignment topics behave
	 * (see Academy\Helper::get_topic_id_by_topic_name_and_topic_type()).
	 *
	 * @param mixed $topic
	 */
	public function get_quizpress_quiz_export_data( $topic ) {
		return array(
			'quizpress_quiz_title' => get_the_title( $topic['id'] ),
		);
	}
}
