<?php
namespace Academy\Classes;

use Academy\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class StudentProgressExport extends ExportBase {

	public function get_export_rows() {
		global $wpdb;

		$empty_row = [
			'student_id'         => '',
			'student_name'       => '',
			'student_username'   => '',
			'student_email'      => '',
			'course_id'          => '',
			'course_title'       => '',
			'enrollment_date'    => '',
			'progress_percentage' => '',
			'total_topics'       => '',
			'completed_topics'   => '',
			'remaining_topics'   => '',
			'completion_status'  => '',
			'completion_date'    => '',
		];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$enrollments = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_author, post_parent, post_date
				FROM {$wpdb->posts}
				WHERE post_type = %s
				AND post_status = %s",
				'academy_enrolled',
				'completed'
			)
		);

		if ( empty( $enrollments ) ) {
			return [ $empty_row ];
		}

		$student_ids = array_values( array_unique( wp_list_pluck( $enrollments, 'post_author' ) ) );
		$course_ids  = array_values( array_unique( wp_list_pluck( $enrollments, 'post_parent' ) ) );

		$students = $this->get_students_by_ids( $student_ids );
		$courses  = $this->get_courses_by_ids( $course_ids );

		update_meta_cache( 'user', $student_ids );

		$curriculum_counts = [];
		foreach ( $course_ids as $course_id ) {
			$curriculum_counts[ $course_id ] = Helper::get_course_curriculums_number_of_counts( $course_id );
		}

		$completions = $this->get_course_completions();

		$rows = [];
		foreach ( $enrollments as $enrollment ) {
			$student_id = (int) $enrollment->post_author;
			$course_id  = (int) $enrollment->post_parent;

			$student = $students[ $student_id ] ?? null;
			$course  = $courses[ $course_id ] ?? null;

			$total_topics     = $curriculum_counts[ $course_id ]['total_topics'] ?? 0;
			$completed_topics = Helper::get_total_number_of_completed_course_topics_by_course_and_student_id( $course_id, $student_id );
			$percentage       = Helper::calculate_percentage( $total_topics, $completed_topics );

			$completion   = $completions[ $course_id ][ $student_id ] ?? null;
			$is_completed = null !== $completion;

			$rows[] = [
				'student_id'          => $student_id,
				'student_name'        => $student['name'] ?? '',
				'student_username'    => $student['username'] ?? '',
				'student_email'       => $student['email'] ?? '',
				'course_id'           => $course_id,
				'course_title'        => $course['title'] ?? '',
				'enrollment_date'     => $enrollment->post_date,
				'progress_percentage' => $percentage . '%',
				'total_topics'        => $total_topics,
				'completed_topics'    => $completed_topics,
				'remaining_topics'    => max( 0, $total_topics - $completed_topics ),
				'completion_status'   => $is_completed ? __( 'Completed', 'academy' ) : __( 'In Progress', 'academy' ),
				'completion_date'     => $is_completed ? $completion : '',
			];
		}//end foreach

		return $rows;
	}

	private function get_students_by_ids( $student_ids ) {
		global $wpdb;

		if ( empty( $student_ids ) ) {
			return [];
		}

		$placeholders = implode( ',', array_fill( 0, count( $student_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPressVIPMinimum.Variables.RestrictedVariables.user_meta__wpdb__users, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- reporting JOIN on users/usermeta that get_users() cannot express; only $wpdb-prefixed table names / generated placeholders are interpolated; values are prepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT u.ID, u.user_login, u.user_nicename, u.user_email, u.display_name,
					first_name.meta_value AS first_name,
					last_name.meta_value AS last_name
				FROM {$wpdb->users} AS u
				LEFT JOIN {$wpdb->usermeta} AS first_name
					ON u.ID = first_name.user_id AND first_name.meta_key = 'first_name'
				LEFT JOIN {$wpdb->usermeta} AS last_name
					ON u.ID = last_name.user_id AND last_name.meta_key = 'last_name'
				WHERE u.ID IN ($placeholders)",
				$student_ids
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPressVIPMinimum.Variables.RestrictedVariables.user_meta__wpdb__users, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		$students = [];
		foreach ( $results as $student ) {
			$name = trim( trim( $student->first_name ) . ' ' . trim( $student->last_name ) );
			$students[ (int) $student->ID ] = [
				'name'     => $name ? $name : $student->display_name,
				'username' => $student->user_login ? $student->user_login : $student->user_nicename,
				'email'    => $student->user_email,
			];
		}

		return $students;
	}

	private function get_courses_by_ids( $course_ids ) {
		global $wpdb;

		if ( empty( $course_ids ) ) {
			return [];
		}

		$placeholders = implode( ',', array_fill( 0, count( $course_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- only $wpdb-prefixed table names / generated placeholders are interpolated; values are prepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title
				FROM {$wpdb->posts}
				WHERE post_type = %s
				AND ID IN ($placeholders)",
				array_merge( [ 'academy_courses' ], $course_ids )
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$courses = [];
		foreach ( $results as $course ) {
			$courses[ (int) $course->ID ] = [
				'title' => $course->post_title,
			];
		}

		return $courses;
	}

	private function get_course_completions() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT comment_post_ID AS course_id, user_id, comment_date
				FROM {$wpdb->comments}
				WHERE comment_agent = %s
				AND comment_type = %s",
				'academy',
				'course_completed'
			)
		);

		$completions = [];
		foreach ( $results as $completion ) {
			$completions[ (int) $completion->course_id ][ (int) $completion->user_id ] = $completion->comment_date;
		}

		return $completions;
	}
}
