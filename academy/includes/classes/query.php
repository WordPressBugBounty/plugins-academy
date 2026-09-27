<?php
namespace Academy\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Helper;

/**
 * All Helper Method name listed here.
 *
 * Method - lesson_insert
 * Method - lesson_meta_insert
 * Method - lesson_meta_update
 *
 * Method - get_total_number_of_questions_by_instructor_id
 */
use Throwable;
use Academy\Lesson\LessonApi\Lesson as LessonApi;
class Query {
	public static function lesson_insert( array $postarr ): ?int {
		try {
			$ins = LessonApi::create( $postarr );
			$ins->save_data();
			return $ins->id();
		} catch ( Throwable $e ) {// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// @TO-DO
		}
		return null;
	}

	public static function lesson_meta_insert( int $lesson_id, array $items ): ?int {
		try {
			$ins = LessonApi::get_by_id( $lesson_id );
			$ins->set_meta_data( $items );
			$ins->save_meta_data();
			return $ins->id();
		} catch ( Throwable $e ) {// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// @TO-DO
		}
		return null;
	}

	public static function lesson_meta_update( int $lesson_id, array $items ): ?int {
		return self::lesson_meta_insert( $lesson_id, $items );
	}
	public static function get_total_number_of_questions_by_instructor_id( int $instructor_id ): int {
		global $wpdb;
		$instructor_course_ids = \Academy\Helper::get_assigned_courses_ids_by_instructor_id( $instructor_id );
		if ( count( $instructor_course_ids ) === 0 ) {
			return 0;
		}
		$implode_ids_placeholder = implode( ', ', array_fill( 0, count( $instructor_course_ids ), '%d' ) );
		$prepare_values           = array_merge( array( 'academy_qa', 'waiting_for_answer' ), $instructor_course_ids );
		// phpcs:disable
		$results = $wpdb->get_var(
			$wpdb->prepare("SELECT COUNT(comment_ID) 
			FROM {$wpdb->comments}
			WHERE comment_type=%s
			AND comment_approved=%s AND comment_post_ID IN($implode_ids_placeholder)", $prepare_values)
		);
		// phpcs:enable
		return (int) $results;
	}

	public static function get_total_number_of_questions_by_student_id( int $student_id ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_var(
			$wpdb->prepare("SELECT COUNT(comment_ID)
			FROM {$wpdb->comments}
			WHERE comment_type=%s
			AND comment_approved=%s AND user_id = %d",
			'academy_qa', 'waiting_for_answer', $student_id )
		);
		// phpcs:enable
		return (int) $results;
	}

	/**
	 * Records one attachment-download event.
	 *
	 * @param string $object_type 'lesson' or 'assignment'.
	 * @param int    $object_id
	 * @param int    $attachment_id
	 * @param int    $user_id
	 */
	public static function attachment_download_insert( string $object_type, int $object_id, int $attachment_id, int $user_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->insert(
			$wpdb->prefix . ACADEMY_PLUGIN_SLUG . '_attachment_downloads',
			array(
				'object_type'   => $object_type,
				'object_id'     => $object_id,
				'attachment_id' => $attachment_id,
				'user_id'       => $user_id,
				'downloaded_at' => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%d', '%d', '%s' )
		);

		return (bool) $inserted;
	}

	/**
	 * Attachment downloads for one object type, grouped by student + file,
	 * with a per-pair count and the most recent download time. Scoped to one
	 * course via `$object_ids` (the course's lesson/assignment ids) — the
	 * only caller is the Course Details modal, one course at a time.
	 *
	 * @param string $object_type 'lesson' or 'assignment'.
	 * @param int[]  $object_ids  Lesson/assignment ids to restrict to.
	 * @return array<int, object{user_id:int, user_name:string, object_id:int, object_title:string, attachment_id:int, attachment_title:string, download_count:int, last_downloaded_at:string}>
	 */
	public static function get_attachment_downloads( string $object_type, array $object_ids ): array {
		global $wpdb;

		if ( empty( $object_ids ) ) {
			return array();
		}

		$downloads_table = $wpdb->prefix . ACADEMY_PLUGIN_SLUG . '_attachment_downloads';

		if ( 'lesson' === $object_type ) {
			$object_title_join  = "LEFT JOIN {$wpdb->prefix}academy_lessons AS obj ON obj.ID = d.object_id";
			$object_title_field = 'obj.lesson_title';
		} else {
			$object_title_join  = "LEFT JOIN {$wpdb->posts} AS obj ON obj.ID = d.object_id";
			$object_title_field = 'obj.post_title';
		}

		$object_ids_placeholder = implode( ', ', array_fill( 0, count( $object_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPressVIPMinimum.Variables.RestrictedVariables.user_meta__wpdb__users -- only $wpdb-prefixed table names / generated placeholders are interpolated; values are prepared; reporting JOIN on users/usermeta that get_users() cannot express
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					d.user_id,
					u.display_name AS user_name,
					d.object_id,
					{$object_title_field} AS object_title,
					d.attachment_id,
					att.post_title AS attachment_title,
					COUNT(*) AS download_count,
					MAX(d.downloaded_at) AS last_downloaded_at
				FROM {$downloads_table} AS d
				LEFT JOIN {$wpdb->users} AS u ON u.ID = d.user_id
				LEFT JOIN {$wpdb->posts} AS att ON att.ID = d.attachment_id
				{$object_title_join}
				WHERE d.object_type = %s
				AND d.object_id IN ($object_ids_placeholder)
				GROUP BY d.user_id, d.attachment_id
				ORDER BY last_downloaded_at DESC",
				array_merge( array( $object_type ), $object_ids )
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPressVIPMinimum.Variables.RestrictedVariables.user_meta__wpdb__users

		return $results ? $results : array();
	}
}
