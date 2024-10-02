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
class Query {
	public static function lesson_insert( $postarr ) {
		if ( ! is_array( $postarr ) ) {
			return null;
		}

		global $wpdb;
		$user_id  = get_current_user_id();
		$defaults = array(
			'lesson_author'       => $user_id,
			'lesson_date'         => '',
			'lesson_date_gmt'     => '',
			'lesson_title'        => '',
			'lesson_name'        => '',
			'lesson_content'      => '',
			'lesson_excerpt'      => '',
			'lesson_status'       => 'draft',
			'comment_status'      => 'close',
			'comment_count'       => 0,
			'lesson_password'     => '',
			'lesson_modified'     => current_time( 'mysql' ),
			'lesson_modified_gmt' => current_time( 'mysql' ),
		);

		$lessonarr = wp_parse_args( $postarr, $defaults );

		// Are we updating or creating?
		$lesson_ID = 0;
		$update    = false;

		if ( empty( $lessonarr['lesson_name'] ) ) {
			$lessonarr['lesson_name'] = \Academy\Helper::generate_unique_lesson_slug( $lessonarr['lesson_title'] );
		}

		if ( ! empty( $postarr['ID'] ) ) {
			$lesson_ID = $postarr['ID'];
			$update    = true;
			unset( $lessonarr['ID'] );
		} else {
			$lessonarr['lesson_date']     = current_time( 'mysql' );
			$lessonarr['lesson_date_gmt'] = current_time( 'mysql' );
		}

		// post insert will be here
		$table_name = $wpdb->prefix . 'academy_lessons';
		if ( $update ) {
			$wpdb->update(
				$table_name,
				$lessonarr,
				array( 'ID' => $lesson_ID ),
				array(
					'%d',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%d',
					'%s',
					'%s',
					'%s',
				),
				array( '%d' )
			);
			return $lesson_ID;
		} else {
			$wpdb->insert(
				$table_name,
				array(
					'lesson_author' => $lessonarr['lesson_author'],
					'lesson_date' => $lessonarr['lesson_date'],
					'lesson_date_gmt' => $lessonarr['lesson_date_gmt'],
					'lesson_title' => $lessonarr['lesson_title'],
					'lesson_name' => $lessonarr['lesson_name'],
					'lesson_content' => $lessonarr['lesson_content'],
					'lesson_excerpt' => $lessonarr['lesson_excerpt'],
					'lesson_status' => $lessonarr['lesson_status'],
					'comment_status' => $lessonarr['comment_status'],
					'comment_count' => $lessonarr['comment_count'],
					'lesson_password' => $lessonarr['lesson_password'],
					'lesson_modified' => $lessonarr['lesson_modified'],
					'lesson_modified_gmt' => $lessonarr['lesson_modified_gmt'],
				),
				array(
					'%d',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%d',
					'%s',
					'%s',
					'%s',
				)
			);
			return $wpdb->insert_id;
		}//end if
		return null;
	}

	public static function lesson_meta_insert( $lesson_id, $items ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'academy_lessonmeta';
		if ( is_array( $items ) && count( $items ) > 0 ) {
			foreach ( $items as $key => $value ) {
				if ( is_array( $value ) ) {
					$value = wp_json_encode( $value );
				}
				$wpdb->insert(
					$table_name,
					array(
						'lesson_id'     => $lesson_id,
						'meta_key'      => $key,
						'meta_value'    => $value,
					),
					array(
						'%d',
						'%s',
						'%s',
					)
				);
			}
		}
	}

	public static function lesson_meta_update( $lesson_id, $items ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'academy_lessonmeta';
		if ( is_array( $items ) && count( $items ) > 0 ) {
			foreach ( $items as $key => $value ) {
				if ( is_array( $value ) ) {
					$value = wp_json_encode( $value );
				}

				$have_meta = $wpdb->get_results(
					$wpdb->prepare( "SELECT lesson_id, meta_key  FROM {$wpdb->prefix}academy_lessonmeta WHERE lesson_id=%d AND meta_key=%s", intval( $lesson_id ), $key )
				);
				if ( count( $have_meta ) === 0 ) {
					// if not exists then insert new one
					$wpdb->insert(
						$table_name,
						array(
							'lesson_id'     => $lesson_id,
							'meta_key'      => $key,
							'meta_value'    => $value,
						),
						array(
							'%d',
							'%s',
							'%s',
						)
					);
				} else {
					// update will be here
					$wpdb->update(
						$table_name,
						array(
							'meta_key'   => $key,
							'meta_value' => $value,
						),
						array(
							'lesson_id' => $lesson_id,
							'meta_key'  => $key,
						),
						array(
							'%s',
							'%s',
						),
						array( '%d', '%s' )
					);
				}//end if
			}//end foreach
		}//end if
	}
	public static function get_total_number_of_questions_by_instructor_id( $instructor_id ) {
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

	public static function get_total_number_of_questions_by_student_id( $student_id ) {
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
