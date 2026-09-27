<?php
namespace Academy\Classes;

use Academy\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CourseStatsExport extends ExportBase {

	public function get_export_rows() {
		global $wpdb;

		$empty_row = [
			'course_id'            => '',
			'course_title'         => '',
			'course_status'        => '',
			'course_creation_date' => '',
			'enrolled_students'    => '',
			'total_lessons'        => '',
			'total_topics'         => '',
			'total_assignments'    => '',
			'total_quizzes'        => '',
			'total_zoom_meetings'  => '',
			'total_tutor_bookings' => '',
		];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$courses = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title, post_status, post_date
				FROM {$wpdb->posts}
				WHERE post_type = %s",
				'academy_courses'
			)
		);

		if ( empty( $courses ) ) {
			return [ $empty_row ];
		}

		$enrollment_counts = $this->get_enrollment_counts_by_course();

		$rows = [];
		foreach ( $courses as $course ) {
			$course_id = (int) $course->ID;
			$counts    = Helper::get_course_curriculums_number_of_counts( $course_id );

			$rows[] = [
				'course_id'            => $course_id,
				'course_title'         => $course->post_title,
				'course_status'        => $course->post_status,
				'course_creation_date' => $course->post_date,
				'enrolled_students'    => $enrollment_counts[ $course_id ] ?? 0,
				'total_lessons'        => $counts['total_lessons'] ?? 0,
				'total_topics'         => $counts['total_topics'] ?? 0,
				'total_assignments'    => $counts['total_assignments'] ?? 0,
				'total_quizzes'        => $counts['total_quizzes'] ?? 0,
				'total_zoom_meetings'  => $counts['total_zoom_meetings'] ?? 0,
				'total_tutor_bookings' => $counts['total_tutor_bookings'] ?? 0,
			];
		}

		return $rows;
	}

	private function get_enrollment_counts_by_course() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_parent AS course_id, COUNT(*) AS enrolled_count
				FROM {$wpdb->posts}
				WHERE post_type = %s
				AND post_status = %s
				GROUP BY post_parent",
				'academy_enrolled',
				'completed'
			)
		);

		$counts = [];
		foreach ( $results as $result ) {
			$counts[ (int) $result->course_id ] = (int) $result->enrolled_count;
		}

		return $counts;
	}
}
