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
	public static function lesson_insert( array $postarr ) : ?int {
		try {
			$ins = LessonApi::create( $postarr );
			$ins->save_data();
			return $ins->id();
		} catch ( Throwable $e ) {
			// @TO-DO
		}
		return null;
	}

	public static function lesson_meta_insert( int $lesson_id, array $items ) : ?int {
		try {
			$ins = LessonApi::get_by_id( $lesson_id );
			$ins->set_meta_data( $items );
			$ins->save_meta_data();
			return $ins->id();
		} catch ( Throwable $e ) {
			// @TO-DO
		}
		return null;
	}

	public static function lesson_meta_update( int $lesson_id, array $items ) : ?int {
		return self::lesson_meta_insert( $lesson_id, $items );
	}
	public static function get_total_number_of_questions_by_instructor_id( int $instructor_id ) : int {
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

	public static function get_total_number_of_questions_by_student_id( int $student_id ) : int {
		global $wpdb;

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
}
