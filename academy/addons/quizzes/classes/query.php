<?php
namespace AcademyQuizzes\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Quiz
 *  - quiz_question_insert
 *  - get_quiz_questions
 *  - get_quiz_question
 *  - get_questions_by_quiz_id
 *  - get_question_settings_by_quiz_id
 *  - get_total_questions_marks_by_attempt_id
 *  - quiz_attempt_insert
 *  - has_attempt_quiz
 *  - get_quiz_attempts
 *  - get_quiz_attempt
 *  - get_quiz_answer
 *  - get_quiz_all_answer_title_by_ids
 *  - get_quiz_answers_by_question_id
 *  - quiz_answer_insert
 *  - get_quiz_total_correct_answer_by_question_id
 *  - get_quiz_total_answer_by_question_id
 *  - get_total_quiz_attempt_correct_answers
 *  - is_quiz_correct_answer
 *  - quiz_attempt_answer_insert
 *  - get_quiz_attempt_answer_by_question
 *  - update_quiz_attempt_resume_info
 *  - update_quiz_attempt_answer_ai_feedback
 *  - get_quiz_attempt_answers_earned_marks
 *  - get_quiz_attempt_raw_answers
 *  - get_quiz_attempt_details
 * - delete_quiz_attempt
 *  - delete_question
 *  - delete_answer
 *  - get_total_number_of_quizzes
 *  - get_total_number_of_quizzes_by_instructor_id
 *  - is_required_manually_reviewed
 *  - get_quiz_correct_answers
 *  - get_total_number_of_attempts
 */
class Query {
	public static function quiz_question_insert( $postarr ) {
		if ( ! is_array( $postarr ) ) {
			return null;
		}

		global $wpdb;
		$defaults = array(
			'quiz_id'               => 0,
			'question_title'        => '',
			'question_title_type'   => 'plain',
			'question_name'         => '',
			'question_content'      => '',
			'question_explanation'  => '',
			'question_status'       => 'publish',
			'question_level'        => '',
			'question_type'         => '',
			'question_score'        => 0,
			'question_negative_score' => 0,
			'question_image_id'     => 0,
			'question_audio_id'     => 0,
			'question_settings'     => [
				'display_points' => true,
				'answer_required' => false,
				'randomize' => false
			],
			'question_order'        => 0,
			'question_created_at'   => current_time( 'mysql' ),
			'question_updated_at' => current_time( 'mysql' ),
		);

		$question_arr = wp_parse_args( $postarr, $defaults );
		// Only decode entities for plain titles. A rich-text title's HTML may
		// legitimately contain entities (e.g. &amp; in link text, or &lt; used
		// to escape a literal "<" in visible text); decoding those here would
		// re-expose raw "<"/"&" characters into markup that later gets rendered
		// with dangerouslySetInnerHTML, which can corrupt the intended formatting.
		if ( 'rich' !== $question_arr['question_title_type'] ) {
			$question_arr['question_title'] = html_entity_decode( $question_arr['question_title'] );
		}
		// Are we updating or creating?
		$question_ID = 0;
		$update    = false;

		if ( ! empty( $postarr['question_id'] ) ) {
			$question_ID = $postarr['question_id'];
			$update    = true;
			unset( $question_arr['question_id'] );
			$question_arr['question_updated_at'] = current_time( 'mysql' );
		}

		// post insert will be here
		$table_name = $wpdb->prefix . 'academy_quiz_questions';
		if ( $update ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table_name,
				$question_arr,
				array( 'question_id' => $question_ID ),
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
					'%f',
					'%f',
					'%d',
					'%d',
					'%s',
					'%d',
					'%s',
					'%s',
				),
				array( '%d' )
			);
			return $question_ID;
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert(
				$table_name,
				array(
					'quiz_id' => $question_arr['quiz_id'],
					'question_title' => $question_arr['question_title'],
					'question_title_type' => $question_arr['question_title_type'],
					'question_name' => $question_arr['question_name'],
					'question_content' => $question_arr['question_content'],
					'question_explanation' => $question_arr['question_explanation'],
					'question_status' => $question_arr['question_status'],
					'question_level' => $question_arr['question_level'],
					'question_type' => $question_arr['question_type'],
					'question_score' => $question_arr['question_score'],
					'question_negative_score' => $question_arr['question_negative_score'],
					'question_settings'     => $question_arr['question_settings'],
					'question_order' => $question_arr['question_order'],
					'question_created_at' => $question_arr['question_created_at'],
					'question_updated_at' => $question_arr['question_updated_at'],
					'question_image_id' => $question_arr['question_image_id'],
					'question_audio_id' => $question_arr['question_audio_id'],
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
					'%f',
					'%f',
					'%s',
					'%d',
					'%s',
					'%s',
					'%d',
					'%d',
				)
			);
			return $wpdb->insert_id;
		}//end if
		return null;
	}
	public static function get_quiz_questions( $args ) {
		global $wpdb;
		$defaults = [
			'limit'          => 10,
			'offset'         => 0,
			'search'         => '',
			'question_type'  => '',
			'question_level' => '',
			'orderby'        => 'question_created_at',
			'order'          => 'DESC',
			// null: no restriction (admin). array (possibly empty): restrict
			// to questions whose quiz_id is in this list.
			'quiz_id__in'    => null,
		];
		$args = wp_parse_args( $args, $defaults );

		$where        = [];
		$where_values = [];

		if ( ! empty( $args['search'] ) ) {
			$where[]        = 'question_title LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		}
		if ( ! empty( $args['question_type'] ) ) {
			$where[]        = 'question_type = %s';
			$where_values[] = $args['question_type'];
		}
		if ( ! empty( $args['question_level'] ) ) {
			$where[]        = 'question_level = %s';
			$where_values[] = $args['question_level'];
		}
		if ( is_array( $args['quiz_id__in'] ) ) {
			if ( empty( $args['quiz_id__in'] ) ) {
				$where[] = '1 = 0';
			} else {
				$where[]      = 'quiz_id IN (' . implode( ', ', array_fill( 0, count( $args['quiz_id__in'] ), '%d' ) ) . ')';
				$where_values = array_merge( $where_values, array_map( 'intval', $args['quiz_id__in'] ) );
			}
		}

		$where_sql = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

		$valid_orderby = [ 'question_created_at', 'question_title', 'question_id' ];
		$orderby       = in_array( $args['orderby'], $valid_orderby, true ) ? $args['orderby'] : 'question_created_at';
		$order         = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$where_values[] = (int) $args['offset'];
		$where_values[] = (int) $args['limit'];

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- only $wpdb-prefixed table names / generated placeholders are interpolated; values are prepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}academy_quiz_questions {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d, %d",
				$where_values
			),
			OBJECT
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
	}

	public static function get_quiz_questions_count( $args = [] ) {
		global $wpdb;
		$defaults = [
			'search'         => '',
			'question_type'  => '',
			'question_level' => '',
			'quiz_id__in'    => null,
		];
		$args = wp_parse_args( $args, $defaults );

		$where        = [];
		$where_values = [];

		if ( ! empty( $args['search'] ) ) {
			$where[]        = 'question_title LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		}
		if ( ! empty( $args['question_type'] ) ) {
			$where[]        = 'question_type = %s';
			$where_values[] = $args['question_type'];
		}
		if ( ! empty( $args['question_level'] ) ) {
			$where[]        = 'question_level = %s';
			$where_values[] = $args['question_level'];
		}
		if ( is_array( $args['quiz_id__in'] ) ) {
			if ( empty( $args['quiz_id__in'] ) ) {
				$where[] = '1 = 0';
			} else {
				$where[]      = 'quiz_id IN (' . implode( ', ', array_fill( 0, count( $args['quiz_id__in'] ), '%d' ) ) . ')';
				$where_values = array_merge( $where_values, array_map( 'intval', $args['quiz_id__in'] ) );
			}
		}

		$where_sql = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

		if ( ! empty( $where_values ) ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- only $wpdb-prefixed table names / generated placeholders are interpolated; values are prepared
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}academy_quiz_questions {$where_sql}",
					$where_values
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		}
		if ( ! empty( $where ) ) {
			// Clauses without values: only the literal `1 = 0` (an instructor
			// with no accessible quizzes), which matches nothing.
			return 0;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}academy_quiz_questions" );
	}
	public static function get_quiz_question( $ID ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$question   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}academy_quiz_questions WHERE question_id=%d", $ID ), OBJECT );
		return current( $question );
	}

	/**
	 * The questions a quiz is made of, keyed by question ID, for scoring a
	 * submission: only these may be answered, and each one's score and type
	 * come from here, never from the request.
	 *
	 * @param int $quiz_id Quiz ID.
	 * @return array<int, object>
	 */
	public static function get_quiz_questions_for_scoring( $quiz_id ) {
		global $wpdb;

		$question_ids = array_filter(
			array_map(
				function ( $question ) {
					return isset( $question['id'] ) ? (int) $question['id'] : 0;
				},
				(array) get_post_meta( $quiz_id, 'academy_quiz_questions', true )
			)
		);
		if ( empty( $question_ids ) ) {
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $question_ids ), '%d' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders are generated above.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT question_id, question_type, question_score FROM {$wpdb->prefix}academy_quiz_questions WHERE question_id IN ({$placeholders})", $question_ids ) );

		$questions = array();
		foreach ( (array) $rows as $row ) {
			$questions[ (int) $row->question_id ] = $row;
		}
		return $questions;
	}

	public static function get_questions_by_quid_id( $quiz_id, $order = 'rand' ) {
		global $wpdb;

		$questions = get_post_meta( $quiz_id, 'academy_quiz_questions', true );

		$question_ids = array_filter(
			array_map(
				function ( $question ) {
					return isset( $question['id'] ) ? (int) $question['id'] : 0;
				},
				(array) $questions
			)
		);

		if ( empty( $question_ids ) ) {
			return array();
		}

		$order = ( 'ASC' === $order || 'DESC' === $order ) ? $order : 'RAND()';

		$sql = "SELECT * FROM {$wpdb->prefix}academy_quiz_questions 
			WHERE question_id IN (" . implode( ',', array_fill( 0, count( $question_ids ), '%d' ) ) . ')';

		$sql .= ( 'RAND()' === $order )
			? ' ORDER BY RAND()'
			: " ORDER BY question_created_at {$order}";

		$limit = (int) get_post_meta( $quiz_id, 'academy_quiz_max_questions_for_answer', true );

		if ( $limit > 0 ) {
			$sql .= $wpdb->prepare( ' LIMIT %d', $limit );
		}

		return $wpdb->get_results( $wpdb->prepare( $sql, $question_ids ), OBJECT );// phpcs:ignore
	}

	public static function question_title_exists( string $title, int $exclude_id = 0 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'academy_quiz_questions';

		if ( $exclude_id > 0 ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- only $wpdb-prefixed table names / generated placeholders are interpolated; values are prepared
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(question_id) FROM {$table_name} WHERE question_title = %s AND question_id != %d",
					$title,
					$exclude_id
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} else {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- only $wpdb-prefixed table names / generated placeholders are interpolated; values are prepared
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(question_id) FROM {$table_name} WHERE question_title = %s",
					$title
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		return (int) $count > 0;
	}

	public static function get_question_settings_by_quiz_id( $ID ) {
		return [
			'quiz_time' => (int) get_post_meta( $ID, 'academy_quiz_time', true ),
			'quiz_time_unit' => get_post_meta( $ID, 'academy_quiz_time_unit', true ),
			'quiz_hide_quiz_time' => (bool) get_post_meta( $ID, 'academy_quiz_hide_quiz_time', true ),
			'quiz_feedback_mode' => get_post_meta( $ID, 'academy_quiz_feedback_mode', true ),
			'quiz_passing_grade' => (int) get_post_meta( $ID, 'academy_quiz_passing_grade', true ),
			'quiz_max_questions_for_answer' => (int) get_post_meta( $ID, 'academy_quiz_max_questions_for_answer', true ),
			'quiz_max_attempts_allowed' => (int) get_post_meta( $ID, 'academy_quiz_max_attempts_allowed', true ),
			'quiz_auto_start' => (bool) get_post_meta( $ID, 'academy_quiz_auto_start', true ),
			'quiz_questions_order' => get_post_meta( $ID, 'academy_quiz_questions_order', true ),
			'quiz_hide_question_number' => (bool) get_post_meta( $ID, 'academy_quiz_hide_question_number', true ),
			'quiz_short_answer_characters_limit' => (int) get_post_meta( $ID, 'academy_quiz_short_answer_characters_limit', true ),
			'quiz_explanation_enabled' => (bool) get_post_meta( $ID, 'academy_quiz_explanation_enabled', true ),
			'quiz_skip_question_showing' => (bool) get_post_meta( $ID, 'academy_quiz_skip_question_showing', true ),
			'quiz_hide_see_more_button' => (bool) get_post_meta( $ID, 'academy_quiz_show_full_answer_content', true ),
			'quiz_questions_layout' => get_post_meta( $ID, 'academy_quiz_questions_layout', true ),
		];
	}

	public static function get_total_questions_marks_by_attempt_id( $ID ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$questions_marks = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT sum(question_mark) as total_marks FROM {$wpdb->prefix}academy_quiz_attempt_answers WHERE attempt_id=%d;",
				$ID
			),
			OBJECT
		);
		return (float) current( $questions_marks )->total_marks;
	}

	public static function quiz_attempt_insert( $postarr ) {
		if ( ! is_array( $postarr ) ) {
			return null;
		}

		global $wpdb;
		$defaults = array(
			'course_id'       => '',
			'quiz_id'        => '',
			'user_id'         => get_current_user_id(),
			'total_questions'      => '',
			'total_answered_questions'       => '',
			'total_marks'        => '',
			'earned_marks'         => '',
			'attempt_info'        => wp_json_encode( array( 'total_correct_answers' => 0 ) ),
			'attempt_status'     => 'pending',
			'attempt_ip'        => \Academy\Helper::get_client_ip_address(),
			'attempt_started_at'   => current_time( 'mysql' ),
			'attempt_ended_at' => current_time( 'mysql' ),
		);

		$attempt = wp_parse_args( $postarr, $defaults );

		// Are we updating or creating?
		$attempt_id = 0;
		$update    = false;

		if ( ! empty( $postarr['attempt_id'] ) ) {
			$attempt_id = $postarr['attempt_id'];
			$update    = true;
			unset( $attempt['attempt_id'] );
			$attempt['attempt_ended_at'] = current_time( 'mysql' );
		}

		// post insert will be here
		$table_name = $wpdb->prefix . 'academy_quiz_attempts';
		if ( $update ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table_name,
				$attempt,
				array( 'attempt_id' => $attempt_id ),
				array(
					'%d',
					'%d',
					'%d',
					'%d',
					'%d',
					'%f',
					'%f',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
				),
				array( '%d' )
			);
			return $attempt_id;
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert(
				$table_name,
				array(
					'course_id' => $attempt['course_id'],
					'quiz_id' => $attempt['quiz_id'],
					'user_id' => $attempt['user_id'],
					'total_questions' => $attempt['total_questions'],
					'total_answered_questions' => $attempt['total_answered_questions'],
					'total_marks' => $attempt['total_marks'],
					'earned_marks' => $attempt['earned_marks'],
					'attempt_info' => $attempt['attempt_info'],
					'attempt_status'     => $attempt['attempt_status'],
					'attempt_ip' => $attempt['attempt_ip'],
					'attempt_started_at' => $attempt['attempt_started_at'],
					'attempt_ended_at' => $attempt['attempt_ended_at']
				),
				array(
					'%d',
					'%d',
					'%d',
					'%d',
					'%d',
					'%f',
					'%f',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s'
				)
			);
			return $wpdb->insert_id;
		}//end if
		return null;
	}

	/**
	 * Resume Mode: persists just the `resume` bookkeeping sub-object
	 * (current step, remaining timer seconds, submitted flag) inside
	 * `attempt_info`, merged alongside whatever other keys are already
	 * there (e.g. `proctoring`). Deliberately bypasses `quiz_attempt_insert()`
	 * — that method also stamps `attempt_status`/`attempt_ended_at` on every
	 * update, which would incorrectly mark a still-in-progress attempt as
	 * finished on every autosave tick.
	 *
	 * @param int   $attempt_id
	 * @param mixed $resume_partial
	 */
	public static function update_quiz_attempt_resume_info( $attempt_id, $resume_partial ) {
		global $wpdb;
		$attempt = self::get_quiz_attempt( $attempt_id );
		if ( ! $attempt ) {
			return false;
		}

		$attempt_info = json_decode( $attempt->attempt_info, true );
		if ( ! is_array( $attempt_info ) ) {
			$attempt_info = array();
		}

		$attempt_info['resume'] = array_merge(
			isset( $attempt_info['resume'] ) && is_array( $attempt_info['resume'] ) ? $attempt_info['resume'] : array(),
			$resume_partial
		);

		$table_name = $wpdb->prefix . 'academy_quiz_attempts';

		// A heartbeat/autosave tick always writes `submitted => false` (it has
		// no way to know the quiz was just finished). If the student pressed
		// Finish while this request was already in flight, the row may have
		// been marked submitted by the time this write actually lands — that
		// has to be checked by MySQL at write time via the `NOT LIKE` guard
		// below, not against the `$attempt` read above, which can itself
		// already be stale. Without this, a late autosave/heartbeat can
		// silently un-submit a finished attempt and the quiz gets offered as
		// resumable again right after the student finished it.
		if ( array_key_exists( 'submitted', $resume_partial ) && empty( $resume_partial['submitted'] ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}academy_quiz_attempts SET attempt_info = %s WHERE attempt_id = %d AND attempt_info NOT LIKE %s",
					wp_json_encode( $attempt_info ),
					$attempt_id,
					'%"submitted":true%'
				)
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->update(
			$table_name,
			array( 'attempt_info' => wp_json_encode( $attempt_info ) ),
			array( 'attempt_id' => $attempt_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	public static function update_quiz_attempt_by_manual_review( $postarr ) {
		if ( ! is_array( $postarr ) ) {
			return null;
		}

		global $wpdb;
		$defaults = array(
			'course_id'       => '',
			'quiz_id'        => '',
			'user_id'         => '',
			'total_questions'      => '',
			'total_answered_questions'       => '',
			'total_marks'        => '',
			'earned_marks'         => '',
			'attempt_info'        => '',
			'attempt_status'        => '',
			'attempt_ip'            => '',
			'attempt_started_at'        => '',
			'attempt_ended_at'        => '',
			'is_manually_reviewed' => 1,
			'manually_reviewed_at' => current_time( 'mysql' ),
		);

		$attempt = wp_parse_args( $postarr, $defaults );

		$attempt_id = $attempt['attempt_id'];
		unset( $attempt['attempt_id'] );

		// post insert will be here
		$table_name = $wpdb->prefix . 'academy_quiz_attempts';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table_name,
			$attempt,
			array( 'attempt_id' => $attempt_id ),
			array(
				'%d',
				'%d',
				'%d',
				'%d',
				'%d',
				'%f',
				'%f',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%s',
			),
			array( '%d' )
		);
		return $attempt_id;
	}

	public static function has_attempt_quiz( $course_id, $quiz_id, $user_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_var( $wpdb->prepare( "SELECT attempt_id FROM {$wpdb->prefix}academy_quiz_attempts WHERE course_id=%d AND quiz_id=%d AND user_id=%d LIMIT 1", $course_id, $quiz_id, $user_id ) );
	}

	public static function get_quiz_attempts( $args ) {
		global $wpdb;
		$defaults = array(
			'attempt_status' => 'any',
			'per_page' => 10,
			'offset' => 0,
			'quiz_id' => 0,
			'course_id' => 0,
			'user_id' => get_current_user_id()
		);
		$args = wp_parse_args( $args, $defaults );
		if ( $args['quiz_id'] ) {
			return self::get_quiz_attempt_details_by_quiz_id( $args );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$query = $wpdb->prepare(
			"SELECT 
				attempt_id, 
				course_id, 
				quiz_id, 
				user_id, 
				total_questions, 
				total_answered_questions, 
				total_marks, 
				earned_marks, 
				attempt_info, 
				attempt_status, 
				attempt_ip, 
				attempt_started_at, 
				attempt_ended_at,  
				(SELECT COUNT(attempt_answer_id) FROM {$wpdb->prefix}academy_quiz_attempt_answers AS attempt_answers WHERE attempt_answers.attempt_id = {$wpdb->prefix}academy_quiz_attempts.attempt_id AND attempt_answers.is_correct = 1) AS total_correct_answer 
			FROM 
				{$wpdb->prefix}academy_quiz_attempts
			INNER JOIN {$wpdb->posts} AS post WHERE post.ID = {$wpdb->prefix}academy_quiz_attempts.quiz_id"
			// phpcs:ignore
		);

		// Scope the result set to a single user when requested. This prevents
		// non-privileged callers from reading every user's quiz attempts.
		if ( ! empty( $args['restrict_user_id'] ) ) {
			$query .= $wpdb->prepare( ' AND user_id = %d', $args['restrict_user_id'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$wild = '%';
			$like = $wild . $wpdb->esc_like( $args['search'] ) . $wild;
			$query .= $wpdb->prepare( ' AND post.post_title LIKE %s', $like );
		}

		if ( 'any' !== $args['attempt_status'] ) {
			$query .= $wpdb->prepare( ' AND attempt_status = %s', $args['attempt_status'] );
		}
		$query .= $wpdb->prepare( ' ORDER BY attempt_started_at DESC LIMIT %d, %d;', $args['offset'], $args['per_page'] );
		// phpcs:ignore
		return $wpdb->get_results( $query );
	}

	public static function get_quiz_attempts_for_instructors( $args ) {
		global $wpdb;
		$defaults = array(
			'per_page' => 10,
			'offset' => 0,
			'quiz_id' => 0,
			'course_id' => 0,
			'user_id' => get_current_user_id()
		);
		$args = wp_parse_args( $args, $defaults );
		if ( $args['quiz_id'] ) {
			return self::get_quiz_attempt_details_by_quiz_id( $args );
		}
		$courseIds = \Academy\Helper::get_course_ids_by_instructor_id( get_current_user_id() );
		if ( false !== $courseIds ) {
			$courseIds = implode( ',', array_map( 'absint', $courseIds ) );
			$query = "SELECT 
				attempt_id, 
				course_id, 
				quiz_id, 
				user_id, 
				total_questions, 
				total_answered_questions, 
				total_marks, 
				earned_marks, 
				attempt_info, 
				attempt_status, 
				attempt_ip, 
				attempt_started_at, 
				attempt_ended_at,  
				( SELECT COUNT(attempt_answer_id) FROM {$wpdb->prefix}academy_quiz_attempt_answers AS attempt_answers WHERE attempt_answers.attempt_id = {$wpdb->prefix}academy_quiz_attempts.attempt_id AND attempt_answers.is_correct = 1) AS total_correct_answer 
			FROM 
				{$wpdb->prefix}academy_quiz_attempts
			INNER JOIN {$wpdb->posts} AS post ON post.ID = {$wpdb->prefix}academy_quiz_attempts.quiz_id";

			if ( ! empty( $args['search'] ) ) {
				$wild = '%';
				$like = $wild . $wpdb->esc_like( $args['search'] ) . $wild;
				// phpcs:ignore
				$query .= $wpdb->prepare( " WHERE course_id IN ({$courseIds}) AND post.post_title LIKE %s", $like );
			}

			if ( empty( $args['search'] ) ) {
				if ( 'any' !== $args['attempt_status'] && ! empty( $args['attempt_status'] ) ) {
					// phpcs:ignore
					$query .= $wpdb->prepare( " WHERE course_id IN ({$courseIds}) AND attempt_status = %s", $args['attempt_status'] );
				} else {
					// phpcs:ignore
					$query .= $wpdb->prepare( " WHERE course_id IN ({$courseIds})" );
				}
			}
			$query .= $wpdb->prepare( ' ORDER BY attempt_started_at DESC LIMIT %d, %d;', $args['offset'], $args['per_page'] );
			// phpcs:ignore
			return $wpdb->get_results( $query, OBJECT );
		}//end if
		return false;
	}

	public static function get_quiz_attempt_details_by_quiz_id( $args ) {
		global $wpdb;
		$defaults = array(
			'per_page' => 10,
			'offset' => 0,
			'quiz_id' => 0,
			'course_id' => 0,
			'user_id' => get_current_user_id()
		);
		$args = wp_parse_args( $args, $defaults );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT attempt_id, course_id, quiz_id, user_id, total_questions, total_answered_questions, total_marks, earned_marks, attempt_info, attempt_status, attempt_ip, attempt_started_at, attempt_ended_at,  
					(select COUNT(attempt_answer_id) from {$wpdb->prefix}academy_quiz_attempt_answers as attempt_answers where attempt_answers.attempt_id = {$wpdb->prefix}academy_quiz_attempts.attempt_id AND attempt_answers.is_correct=1) as total_correct_answer 
				FROM {$wpdb->prefix}academy_quiz_attempts WHERE quiz_id=%d AND course_id=%d AND user_id=%d ORDER BY attempt_started_at DESC LIMIT %d, %d;",
				$args['quiz_id'],
				$args['course_id'],
				$args['user_id'],
				$args['offset'],
				$args['per_page']
			),
			OBJECT
		);
	}

	/**
	 * Quiz ids that belong to a course's curriculum.
	 *
	 * Quiz->course membership lives in the course's `academy_course_curriculum`
	 * post meta, not on the quiz post itself, so this reads the same flattened
	 * curriculum list the course builder/learn page already use.
	 *
	 * @param int $course_id
	 *
	 * @return int[]
	 */
	public static function get_course_quiz_ids( $course_id ) {
		$curriculum_items = \Academy\Helper::get_course_curriculums( $course_id );
		$quiz_ids = [];
		foreach ( (array) $curriculum_items as $item ) {
			if ( isset( $item['type'], $item['id'] ) && 'quiz' === $item['type'] && $item['id'] ) {
				$quiz_ids[] = (int) $item['id'];
			}
		}
		return $quiz_ids;
	}

	/**
	 * Quiz ids an instructor may read from the question bank.
	 *
	 * The `academy_quiz_questions` table has no author/owner column of its
	 * own — a question's only ownership signal is the quiz it was created
	 * in, and that quiz's course membership. So "this instructor's
	 * questions" is derived the same way {@see get_quiz_attempts_for_instructors()}
	 * derives "this instructor's attempts": via the courses they are
	 * authoring or assigned to (`academy_instructor_course_id` covers both,
	 * since course save already backfills it for the author), then the
	 * quizzes in each of those courses' curriculum.
	 *
	 * @param int $instructor_id
	 *
	 * @return int[]
	 */
	public static function get_quiz_ids_by_instructor_id( $instructor_id ) {
		$course_ids = \Academy\Helper::get_course_ids_by_instructor_id( $instructor_id );
		if ( false === $course_ids ) {
			return [];
		}
		$quiz_ids = [];
		foreach ( $course_ids as $course_id ) {
			$quiz_ids = array_merge( $quiz_ids, self::get_course_quiz_ids( (int) $course_id ) );
		}
		return array_values( array_unique( $quiz_ids ) );
	}

	/**
	 * A student's latest attempt per quiz within one course.
	 *
	 * Explicitly scoped by both user_id and course_id (unlike the general
	 * branch of get_quiz_attempts(), which has no user_id filter) so this is
	 * safe to call for a self-service, student-facing grade view.
	 *
	 * @param int $user_id
	 * @param int $course_id
	 */
	public static function get_students_own_quiz_grades_by_course( $user_id, $course_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT qa.attempt_id, qa.quiz_id, qa.total_marks, qa.earned_marks, qa.attempt_status, qa.attempt_started_at
				FROM {$wpdb->prefix}academy_quiz_attempts qa
				INNER JOIN (
					SELECT quiz_id, MAX(attempt_id) AS max_attempt_id
					FROM {$wpdb->prefix}academy_quiz_attempts
					WHERE user_id = %d AND course_id = %d
					GROUP BY quiz_id
				) latest ON qa.attempt_id = latest.max_attempt_id
				ORDER BY qa.attempt_started_at DESC",
				$user_id,
				$course_id
			),
			OBJECT
		);
	}

	/**
	 * Course IDs a student has at least one quiz attempt in.
	 *
	 * Feeds the course dropdown of the admin/instructor "Quiz Answers" review
	 * in Student Details, which only lists courses actually worth reviewing.
	 *
	 * @param int $user_id
	 */
	public static function get_courses_with_quiz_attempts_by_user( $user_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT course_id FROM {$wpdb->prefix}academy_quiz_attempts WHERE user_id = %d",
				$user_id
			)
		);
	}

	public static function get_quiz_attempt( $ID ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$attempt   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}academy_quiz_attempts WHERE attempt_id=%d", $ID ), OBJECT );
		return current( $attempt );
	}

	public static function get_all_quiz_attempts() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$attempt = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}academy_quiz_attempts", OBJECT );
		return $attempt;
	}

	public static function get_quiz_answer( $ID ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$answer   = $wpdb->get_results( $wpdb->prepare( "SELECT answer_id, quiz_id, answer_title, answer_content, image_id, view_format, answer_order, answer_created_at, answer_updated_at FROM {$wpdb->prefix}academy_quiz_answers WHERE answer_id=%d", $ID ), OBJECT );
		return current( $answer );
	}

	public static function get_quiz_all_answer_title_by_ids( $IDs ) {
		global $wpdb;
		if ( empty( $IDs ) || ! is_array( $IDs ) ) {
			return [];
		}
		$implode_ids_placeholder = implode( ', ', array_fill( 0, count( $IDs ), '%d' ) );
		// phpcs:disable
		$answer   = $wpdb->get_results( $wpdb->prepare( "SELECT answer_title, image_id FROM {$wpdb->prefix}academy_quiz_answers WHERE answer_id IN($implode_ids_placeholder)", $IDs ), OBJECT );
		// phpcs:enable
		return $answer;
	}

	public static function get_quiz_answers_by_question_id( $question_id, $question_type ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$question_settings_raw = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT question_settings FROM {$wpdb->prefix}academy_quiz_questions WHERE question_id=%d",
				$question_id
			)
		);

		$question_settings = json_decode( $question_settings_raw ?? '{}', true );
		$order_by          = ! empty( $question_settings['randomize'] ) ? 'RAND()' : 'answer_order ASC';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$answers = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT answer_id, quiz_id, answer_title, image_id, view_format, answer_order, answer_created_at, answer_updated_at  FROM {$wpdb->prefix}academy_quiz_answers WHERE question_id=%d AND question_type=%s",
				$question_id,
				$question_type
			),
			OBJECT
		);

		// Answer titles are always rich-text HTML (there's no plain/rich distinction
		// for answers like there is for question titles), and the REST builder already
		// stores them entity-decoded (see AcademyQuizzes\Api\QuizAnswers). Decode here
		// too so any legacy/entity-encoded rows render as real markup instead of
		// literal "&lt;ul&gt;..." text wherever this shared lookup is consumed
		// (frontend ajax player, PHP-rendered curriculum templates, attempt results).
		foreach ( $answers as $answer ) {
			$answer->answer_title = html_entity_decode( $answer->answer_title );
		}

		return $answers;
	}

	public static function get_question_answers_by_question_id( $question_id, $question_type ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}academy_quiz_answers WHERE question_id=%d AND question_type=%s",
				$question_id,
				$question_type
			),
			OBJECT
		);
	}

	public static function quiz_answer_insert( $postarr ) {
		if ( ! is_array( $postarr ) ) {
			return null;
		}

		global $wpdb;
		$defaults = array(
			'quiz_id'               => '',
			'question_id'               => '',
			'question_type'               => '',
			'answer_title'        => '',
			'answer_content'         => '',
			'is_correct'      => '',
			'image_id'       => '',
			'view_format'        => '',
			'answer_order'         => '',
			'answer_created_at'   => current_time( 'mysql' ),
			'answer_updated_at' => current_time( 'mysql' ),
		);

		$question_arr = wp_parse_args( $postarr, $defaults );

		// Are we updating or creating?
		$answer_ID = 0;
		$update    = false;

		if ( ! empty( $postarr['answer_id'] ) ) {
			$answer_ID = $postarr['answer_id'];
			$update    = true;
			unset( $question_arr['answer_id'] );
			$question_arr['answer_updated_at'] = current_time( 'mysql' );
		}

		// post insert will be here
		$table_name = $wpdb->prefix . 'academy_quiz_answers';
		if ( $update ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table_name,
				$question_arr,
				array( 'answer_id' => $answer_ID ),
				array(
					'%d',
					'%d',
					'%s',
					'%s',
					'%s',
					'%d',
					'%d',
					'%s',
					'%d',
					'%s',
					'%s',
				),
				array( '%d' )
			);
			return $answer_ID;
		} else {

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert(
				$table_name,
				array(
					'quiz_id' => $question_arr['quiz_id'],
					'question_id' => $question_arr['question_id'],
					'question_type' => $question_arr['question_type'],
					'answer_title' => $question_arr['answer_title'],
					'answer_content' => $question_arr['answer_content'],
					'is_correct' => $question_arr['is_correct'],
					'image_id' => $question_arr['image_id'],
					'view_format' => $question_arr['view_format'],
					'answer_order' => $question_arr['answer_order'],
					'answer_created_at' => $question_arr['answer_created_at'],
					'answer_updated_at' => $question_arr['answer_updated_at']
				),
				array(
					'%d',
					'%d',
					'%s',
					'%s',
					'%s',
					'%d',
					'%d',
					'%s',
					'%d',
					'%s',
					'%s'
				)
			);
			return $wpdb->insert_id;
		}//end if
		return null;
	}

	public static function get_quiz_total_correct_answer_by_question_id( $ID ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$answers   = $wpdb->get_results( $wpdb->prepare( "SELECT is_correct FROM {$wpdb->prefix}academy_quiz_answers WHERE question_id=%d AND is_correct=%d", $ID, 1 ), OBJECT );
		return count( $answers );
	}

	public static function get_quiz_total_answer_by_question_id( $ID ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_questions = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(question_id) FROM {$wpdb->prefix}academy_quiz_answers WHERE question_id=%d", $ID ) );
		return $total_questions;
	}

	public static function get_total_quiz_attempt_correct_answers( $ID ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$correct_answers = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(is_correct) FROM {$wpdb->prefix}academy_quiz_attempt_answers WHERE attempt_id=%d AND is_correct=%d", $ID, 1 ) );
		return (int) $correct_answers;
	}

	/**
	 * How many questions actually have a saved answer row for this attempt —
	 * one row per question (insert_quiz_answer() upserts), so a plain count
	 * is exact. Used to finalize `total_answered_questions` server-side
	 * (see API\QuizAttempts::update_item()) instead of trusting whatever the
	 * client happened to send, which Resume Mode's "start new after
	 * abandoning" flow never included at all.
	 *
	 * @param int $ID Attempt id.
	 * @return int
	 */
	public static function get_total_quiz_attempt_answered_questions( $ID ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$answered = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(attempt_answer_id) FROM {$wpdb->prefix}academy_quiz_attempt_answers WHERE attempt_id=%d", $ID ) );
		return (int) $answered;
	}

	public static function is_image_answer_quiz_correct_answer( $IDs, $question_id = '' ) {
		global $wpdb;
		if ( is_array( $IDs ) ) {
			$correct_answer_count = 0;
			foreach ( $IDs as $ID => $value ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$correct_answer_count  += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(answer_id) FROM {$wpdb->prefix}academy_quiz_answers WHERE answer_id=%d AND question_id=%d AND answer_title=%s", $ID, $question_id, $value ) );
			}
			$total_correct_answer = (int) self::get_quiz_total_answer_by_question_id( $question_id );
			return ( $total_correct_answer === $correct_answer_count ? true : false );
		}
		return false;
	}

	public static function is_fill_in_the_blanks_quiz_correct_answer( $given_answer_args, $question_id = '' ) {
		global $wpdb;

		if ( is_array( $given_answer_args ) ) {
			// Trim leading/trailing spaces from each answer and normalize spaces around the pipe symbol
			$given_answer_args = array_map(function ( $answer ) {
				return preg_replace( '/\s*\|\s*/', '|', trim( $answer ) );
			}, $given_answer_args);

			// Join the answers with a pipe symbol
			$given_answer = implode( '|', $given_answer_args );

			// Query for a match ignoring any spaces around the pipe symbol in both the database and given answer
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$is_correct = $wpdb->get_var( $wpdb->prepare(
				"SELECT count(answer_id) FROM {$wpdb->prefix}academy_quiz_answers 
				WHERE question_id=%d AND REPLACE(TRIM(answer_content), ' ', '') LIKE REPLACE(TRIM(%s), ' ', '')",
				$question_id, $given_answer
			));

			return (bool) $is_correct;
		}

		return false;
	}

	public static function is_quiz_correct_answer( $IDs, $question_id = '' ) {
		global $wpdb;
		// Answers only count for the question they belong to, and each once:
		// otherwise another question's correct answer ID, or the same correct
		// ID sent twice, would score.
		if ( is_array( $IDs ) ) {
			$correct_answer_count = 0;
			$has_wrong_answer = false;
			foreach ( array_unique( array_map( 'absint', $IDs ) ) as $ID ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$answers   = $wpdb->get_results( $wpdb->prepare( "SELECT is_correct FROM {$wpdb->prefix}academy_quiz_answers WHERE answer_id=%d AND question_id=%d", $ID, $question_id ), OBJECT );
				if ( ! empty( $answers ) && (bool) current( $answers )->is_correct === true ) {
					++$correct_answer_count;
				} else {
					$has_wrong_answer = true;
					break;
				}
			}
			if ( $has_wrong_answer ) {
				return false;
			}
			$total_correct_answer = (int) self::get_quiz_total_correct_answer_by_question_id( $question_id );
			return ( $total_correct_answer === $correct_answer_count ? true : false );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$answers   = $wpdb->get_results( $wpdb->prepare( "SELECT is_correct FROM {$wpdb->prefix}academy_quiz_answers WHERE answer_id=%d AND question_id=%d", $IDs, $question_id ), OBJECT );
		return ! empty( $answers ) && (bool) current( $answers )->is_correct;
	}

	public static function get_quiz_attempt_answer( $ID ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$attemp_answer   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}academy_quiz_attempt_answers WHERE attempt_answer_id=%d", $ID ), OBJECT );
		return current( $attemp_answer );
	}

	/**
	 * Looks up the existing saved-answer row for one question within one
	 * attempt, so autosave (Resume Mode) can upsert instead of inserting a
	 * duplicate row every time the student changes the same answer.
	 *
	 * @param int $attempt_id
	 * @param int $question_id
	 */
	public static function get_quiz_attempt_answer_by_question( $attempt_id, $question_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$attempt_answer = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}academy_quiz_attempt_answers WHERE attempt_id=%d AND question_id=%d",
				$attempt_id,
				$question_id
			),
			OBJECT
		);
		return $attempt_answer;
	}

	public static function quiz_attempt_answer_insert( $postarr ) {
		if ( ! is_array( $postarr ) ) {
			return null;
		}

		global $wpdb;
		$defaults = array(
			'user_id'            => get_current_user_id(),
			'quiz_id'            => '',
			'question_id'        => '',
			'attempt_id'         => '',
			'answer'             => '',
			'question_mark'      => '',
			'achieved_mark'      => '',
			'minus_mark'         => '',
			'is_correct'         => '',
		);

		$attempt = wp_parse_args( $postarr, $defaults );
		$table_name = $wpdb->prefix . 'academy_quiz_attempt_answers';

		// Are we updating or creating?
		$attempt_answer_id = 0;
		$update    = false;

		if ( ! empty( $postarr['attempt_answer_id'] ) ) {
			$attempt_answer_id = $postarr['attempt_answer_id'];
			$update    = true;
			unset( $attempt['attempt_answer_id'] );
		}
		// update attempt answer
		if ( $update ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table_name,
				$attempt,
				array( 'attempt_answer_id' => $attempt_answer_id ),
				array(
					'%d',
					'%d',
					'%d',
					'%d',
					'%s',
					'%f',
					'%f',
					'%f',
					'%d',
				),
				array( '%d' )
			);
			return $attempt_answer_id;
		}//end if
		// insert attempt answer
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table_name,
			array(
				'user_id' => $attempt['user_id'],
				'quiz_id' => $attempt['quiz_id'],
				'question_id' => $attempt['question_id'],
				'attempt_id' => $attempt['attempt_id'],
				'answer' => $attempt['answer'],
				'question_mark' => $attempt['question_mark'],
				'achieved_mark' => $attempt['achieved_mark'],
				'minus_mark' => $attempt['minus_mark'],
				'is_correct' => $attempt['is_correct'],
			),
			array(
				'%d',
				'%d',
				'%d',
				'%d',
				'%s',
				'%f',
				'%f',
				'%f',
				'%d',
			)
		);
		return $wpdb->insert_id;
	}

	/**
	 * Stores the AI-generated feedback for one answered question.
	 *
	 * @param int   $attempt_answer_id
	 * @param mixed $feedback
	 */
	public static function update_quiz_attempt_answer_ai_feedback( $attempt_answer_id, $feedback ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->update(
			$wpdb->prefix . 'academy_quiz_attempt_answers',
			array( 'ai_feedback' => $feedback ),
			array( 'attempt_answer_id' => $attempt_answer_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	public static function get_quiz_attempt_answers_earned_marks( $user_id, $attempt_id ) {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom query with no WP API equivalent
		$total_marks = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(achieved_mark) FROM {$wpdb->prefix}academy_quiz_attempt_answers WHERE user_id = %d AND attempt_id = %d",
				$user_id,
				$attempt_id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (float) ( $total_marks ?? 0 );
	}

	/**
	 * Resume Mode: the raw (unformatted) saved answer per question for an
	 * attempt — unlike get_quiz_attempt_details() / prepare_given_answer(),
	 * which reshape `answer` into a display-only structure for the results
	 * page, this keeps the exact wire format insert_quiz_answer() wrote so
	 * the frontend can feed it straight back into the live, editable quiz
	 * (via the same parseGivenAnswerData() that mirrors prepareGivenAnswerData()).
	 *
	 * @param int $attempt_id
	 * @param int $user_id
	 */
	public static function get_quiz_attempt_raw_answers( $attempt_id, $user_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT attempt_answers.question_id, attempt_answers.answer, quiz_questions.question_type
				FROM {$wpdb->prefix}academy_quiz_attempt_answers as attempt_answers
				LEFT JOIN {$wpdb->prefix}academy_quiz_questions as quiz_questions ON attempt_answers.question_id = quiz_questions.question_id
				WHERE attempt_answers.attempt_id=%d AND attempt_answers.user_id=%d",
				$attempt_id,
				$user_id
			),
			OBJECT
		);
	}

	public static function get_quiz_attempt_details( $attempt_id, $user_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results($wpdb->prepare( "SELECT
            attempt_answers.attempt_answer_id, 
            attempt_answers.attempt_id, 
            attempt_answers.user_id, 
            attempt_answers.question_id,
			attempt_answers.is_correct,
            attempt_answers.answer as given_answer,
            attempt_answers.ai_feedback,
            quiz_answers.answer_title as correct_answer,
            quiz_answers.answer_content,
            quiz_answers.answer_id,
			quiz_answers.is_correct as is_correct_answer,
            quiz_questions.quiz_id,
            quiz_questions.question_title, 
            quiz_questions.question_explanation, 
            quiz_questions.question_type,
			quiz_questions.question_image_id,
			quiz_questions.question_audio_id,
			quiz_attempts.is_manually_reviewed
            FROM {$wpdb->prefix}academy_quiz_attempt_answers as attempt_answers 
            LEFT JOIN {$wpdb->prefix}academy_quiz_questions as quiz_questions ON attempt_answers.question_id = quiz_questions.question_id
            LEFT JOIN {$wpdb->prefix}academy_quiz_answers as quiz_answers ON attempt_answers.question_id = quiz_answers.question_id
            LEFT JOIN {$wpdb->prefix}academy_quiz_attempts as quiz_attempts ON attempt_answers.attempt_id = quiz_attempts.attempt_id
            WHERE attempt_answers.attempt_id=%d AND attempt_answers.user_id=%d", $attempt_id, $user_id ), OBJECT );
	}

	public static function get_quiz_attempt_skip_questions( $attempt_id, $user_id, $quiz_id ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT
				attempt_answers.attempt_answer_id,
				attempt_answers.attempt_id,
				attempt_answers.user_id,
				q.question_id,
				attempt_answers.is_correct,
				attempt_answers.answer AS given_answer,

				ans.answer_title AS correct_answer,
				ans.answer_content,
				ans.answer_id,
				ans.is_correct AS is_correct_answer,

				q.quiz_id,
				q.question_title,
				q.question_explanation,
				q.question_type,
				q.question_image_id,

				attempts.is_manually_reviewed

			FROM {$wpdb->prefix}academy_quiz_questions q

			LEFT JOIN {$wpdb->prefix}academy_quiz_attempt_answers attempt_answers
				ON attempt_answers.question_id = q.question_id
				AND attempt_answers.attempt_id = %d
				AND attempt_answers.user_id = %d

			LEFT JOIN {$wpdb->prefix}academy_quiz_answers ans
				ON ans.question_id = q.question_id
				AND ans.is_correct = 1

			LEFT JOIN {$wpdb->prefix}academy_quiz_attempts attempts
				ON attempts.attempt_id = %d

			WHERE q.quiz_id = %d
				AND attempt_answers.attempt_answer_id IS NULL
			",
			$attempt_id,
			$user_id,
			$attempt_id,
			$quiz_id
		);

		return $wpdb->get_results( $query, OBJECT ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- custom query with no WP API equivalent; only $wpdb-prefixed table names / generated placeholders are interpolated; values are prepared
	}

	public static function delete_quiz_attempt( $attempt_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$is_delete_attempts = $wpdb->delete( $wpdb->prefix . 'academy_quiz_attempts', array( 'attempt_id' => $attempt_id ), array( '%d' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$is_delete_attempt_answers = $wpdb->delete( $wpdb->prefix . 'academy_quiz_attempt_answers', array( 'attempt_id' => $attempt_id ), array( '%d' ) );
		return $is_delete_attempts === $is_delete_attempt_answers;
	}

	/**
	 * Build a map of question_id => list of quizzes referencing it.
	 *
	 * Quiz→question membership lives in each quiz's `academy_quiz_questions`
	 * post meta (a list of { id, title } entries), so the same question id can
	 * appear in many quizzes — this scans every quiz once and inverts that into
	 * a per-question usage list. Used for the "used in N quizzes" column and the
	 * reverse-lookup endpoint; no junction table required.
	 *
	 * @return array<int, array<int, array{id:int,title:string}>>
	 */
	public static function get_question_usage_map() {
		// phpcs:disable WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- needs the complete (small, bounded) set
		$quiz_ids = get_posts(
			array(
				'post_type'      => 'academy_quiz',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		// phpcs:enable WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page

		$map = array();
		foreach ( $quiz_ids as $quiz_id ) {
			$list = get_post_meta( $quiz_id, 'academy_quiz_questions', true );
			if ( ! is_array( $list ) ) {
				continue;
			}
			$title = get_the_title( $quiz_id );
			foreach ( $list as $item ) {
				$qid = isset( $item['id'] ) ? (int) $item['id'] : 0;
				if ( ! $qid ) {
					continue;
				}
				$map[ $qid ][] = array(
					'id'    => (int) $quiz_id,
					'title' => $title,
				);
			}
		}
		return $map;
	}

	/**
	 * List the quizzes that reference a single question.
	 *
	 * @param int $question_id
	 *
	 * @return array<int, array{id:int,title:string}>
	 */
	public static function get_quizzes_using_question( $question_id ) {
		$map = self::get_question_usage_map();
		return $map[ (int) $question_id ] ?? array();
	}

	/**
	 * Remove a question id from every quiz's `academy_quiz_questions` meta list.
	 * Called on hard-delete so no quiz keeps a dangling reference.
	 *
	 * @param int $question_id
	 */
	public static function unlink_question_from_all_quizzes( $question_id ) {
		$question_id = (int) $question_id;
		// phpcs:disable WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- needs the complete (small, bounded) set
		$quiz_ids    = get_posts(
			array(
				'post_type'      => 'academy_quiz',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		// phpcs:enable WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page

		foreach ( $quiz_ids as $quiz_id ) {
			$list = get_post_meta( $quiz_id, 'academy_quiz_questions', true );
			if ( ! is_array( $list ) ) {
				continue;
			}
			$filtered = array_values(
				array_filter(
					$list,
					function ( $item ) use ( $question_id ) {
						return isset( $item['id'] ) && (int) $item['id'] !== $question_id;
					}
				)
			);
			if ( count( $filtered ) !== count( $list ) ) {
				update_post_meta( $quiz_id, 'academy_quiz_questions', $filtered );
			}
		}
	}

	public static function delete_question( $question_id ) {
		global $wpdb;
		// A question can be shared across quizzes (its id lives in each quiz's
		// meta list), so a hard delete must also strip it from every quiz.
		self::unlink_question_from_all_quizzes( $question_id );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$is_deleted = $wpdb->delete( $wpdb->prefix . 'academy_quiz_questions', array( 'question_id' => $question_id ), array( '%d' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $wpdb->prefix . 'academy_quiz_answers', array( 'question_id' => $question_id ), array( '%d' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$attempt_id = $wpdb->delete( $wpdb->prefix . 'academy_quiz_attempt_answers', array( 'question_id' => $question_id ), array( '%d' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $wpdb->prefix . 'academy_quiz_attempts', array( 'attempt_id' => $attempt_id ), array( '%d' ) );
		return $is_deleted;
	}

	public static function delete_answer( $answer_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->delete( $wpdb->prefix . 'academy_quiz_answers', array( 'answer_id' => $answer_id ), array( '%d' ) );
	}

	public static function get_total_number_of_quizzes() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_var(
			$wpdb->prepare("SELECT COUNT(ID) 
            FROM {$wpdb->posts} 
            WHERE post_type = %s 
            AND post_status = %s", 'academy_quiz', 'publish')
		);
		return (int) $results;
	}
	public static function get_total_number_of_quizzes_by_instructor_id( $instructor_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_var(
			$wpdb->prepare("SELECT COUNT(ID) 
            FROM {$wpdb->posts} 
            WHERE post_type = %s 
            AND post_author = %d
			AND post_status = %s", 'academy_quiz', $instructor_id, 'publish')
		);
		return (int) $results;
	}
	public static function is_required_manually_reviewed( $quiz_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(question_id) FROM {$wpdb->prefix}academy_quiz_questions WHERE quiz_id=%d AND question_type=%s;",
				$quiz_id,
				'shortAnswer'
			)
		);
		return (int) $results;
	}
	public static function get_quiz_correct_answers( $question_id, $quiz_type ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results( $wpdb->prepare( "SELECT answer_id, quiz_id, answer_title, image_id, view_format, answer_order, answer_created_at, answer_updated_at FROM {$wpdb->prefix}academy_quiz_answers WHERE question_id=%d AND question_type=%s AND is_correct=%d", $question_id, $quiz_type, 1 ), OBJECT );
	}
	public static function get_total_number_of_attempts( $user_id = 0 ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$query = "SELECT COUNT(attempt_id)
			FROM {$wpdb->prefix}academy_quiz_attempts qa
			INNER JOIN {$wpdb->prefix}posts p ON p.ID = qa.quiz_id
			AND p.post_type = 'academy_quiz'
		";
		if ( $user_id ) {
			$query .= $wpdb->prepare( ' AND p.post_author = %d', $user_id );
		}
		return $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom query with no WP API equivalent
	}
	public static function get_question_details_by_question_id( $question_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}academy_quiz_questions WHERE question_id = %d LIMIT 1", $question_id )
		);
	}
	public static function get_total_pending_attempt_by_attempt_id( $attempt_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(quiz_id) FROM {$wpdb->prefix}academy_quiz_attempt_answers WHERE attempt_id = %d",
				array(
					$attempt_id
				)
			)
		);
	}
	public static function get_attempt_id_by_user_and_course_id( $course_id, $user_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT attempt_id FROM {$wpdb->prefix}academy_quiz_attempts WHERE course_id = %d AND user_id= %d",
				array(
					$course_id,
					$user_id
				)
			)
		);
	}
}
