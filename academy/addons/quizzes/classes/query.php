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
 *  - get_total_questions_marks_by_quiz_id
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
 *  - get_quiz_attempt_answers_earned_marks
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
			'quiz_id'               => '',
			'question_title'        => '',
			'question_name'         => '',
			'question_content'      => '',
			'question_status'       => 'publish',
			'question_level'        => '',
			'question_type'         => '',
			'question_score'        => 0,
			'question_settings'     => '',
			'question_order'        => 0,
			'question_created_at'   => current_time( 'mysql' ),
			'question_updated_at' => current_time( 'mysql' ),
		);

		$question_arr = wp_parse_args( $postarr, $defaults );
		$question_arr['question_title'] = html_entity_decode( $question_arr['question_title'] );
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
					'%f',
					'%s',
					'%d',
					'%s',
					'%s',
				),
				array( '%d' )
			);
			return $question_ID;
		} else {
			$wpdb->insert(
				$table_name,
				array(
					'quiz_id' => $question_arr['quiz_id'],
					'question_title' => $question_arr['question_title'],
					'question_name' => $question_arr['question_name'],
					'question_content' => $question_arr['question_content'],
					'question_status' => $question_arr['question_status'],
					'question_level' => $question_arr['question_level'],
					'question_type' => $question_arr['question_type'],
					'question_score' => $question_arr['question_score'],
					'question_settings'     => $question_arr['question_settings'],
					'question_order' => $question_arr['question_order'],
					'question_created_at' => $question_arr['question_created_at'],
					'question_updated_at' => $question_arr['question_updated_at'],
				),
				array(
					'%d',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%f',
					'%s',
					'%d',
					'%s',
					'%s',
				)
			);
			return $wpdb->insert_id;
		}//end if
		return null;
	}
	public static function get_quiz_questions( $args ) {
		global $wpdb;
		$defaults = array(
			'limit' => 10,
			'offset' => 0,
		);
		$args = wp_parse_args( $args, $defaults );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}academy_quiz_questions ORDER BY question_created_at DESC LIMIT %d, %d;",
				$args['offset'],
				$args['limit']
			),
			OBJECT
		);
	}
	public static function get_quiz_question( $ID ) {
		global $wpdb;
		$question   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}academy_quiz_questions WHERE question_id=%d", $ID ), OBJECT );
		return current( $question );
	}

	public static function get_questions_by_quid_id( $quiz_id, $order = 'rand' ) {
		global $wpdb;
		$validOrders = [ 'rand', 'ASC', 'DESC' ];
		$order = in_array( $order, $validOrders, true ) ? $order : 'DESC';
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}academy_quiz_questions WHERE quiz_id = %d ORDER BY " . ( 'rand' === $order ? 'RAND()' : 'question_created_at ' . $order ), //phpcs:ignore
			$quiz_id
		), OBJECT );
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
		];
	}

	public static function get_total_questions_marks_by_quiz_id( $ID ) {
		global $wpdb;
		$questions_marks = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT sum(question_score) as total_marks FROM {$wpdb->prefix}academy_quiz_questions WHERE quiz_id=%d;",
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
			'attempt_info'        => '',
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
			$courseIds = implode( ',', $courseIds );
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

	public static function get_quiz_attempt( $ID ) {
		global $wpdb;
		$attempt   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}academy_quiz_attempts WHERE attempt_id=%d", $ID ), OBJECT );
		return current( $attempt );
	}

	public static function get_quiz_answer( $ID ) {
		global $wpdb;
		$answer   = $wpdb->get_results( $wpdb->prepare( "SELECT answer_id, quiz_id, answer_title, answer_content, image_id, view_format, answer_order, answer_created_at, answer_updated_at FROM {$wpdb->prefix}academy_quiz_answers WHERE answer_id=%d", $ID ), OBJECT );
		return current( $answer );
	}

	public static function get_quiz_all_answer_title_by_ids( $IDs ) {
		global $wpdb;
		$implode_ids_placeholder = implode( ', ', array_fill( 0, count( $IDs ), '%d' ) );
		// phpcs:disable
		$answer   = $wpdb->get_results( $wpdb->prepare( "SELECT answer_title, image_id FROM {$wpdb->prefix}academy_quiz_answers WHERE answer_id IN($implode_ids_placeholder)", $IDs ), OBJECT );
		// phpcs:enable
		return $answer;
	}

	public static function get_quiz_answers_by_question_id( $question_id, $question_type ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT answer_id, quiz_id, answer_title, image_id, view_format, answer_order, answer_created_at, answer_updated_at  FROM {$wpdb->prefix}academy_quiz_answers WHERE question_id=%d AND question_type=%s",
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
		$answers   = $wpdb->get_results( $wpdb->prepare( "SELECT is_correct FROM {$wpdb->prefix}academy_quiz_answers WHERE question_id=%d AND is_correct=%d", $ID, 1 ), OBJECT );
		return count( $answers );
	}

	public static function get_quiz_total_answer_by_question_id( $ID ) {
		global $wpdb;
		$total_questions = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(question_id) FROM {$wpdb->prefix}academy_quiz_answers WHERE question_id=%d", $ID ) );
		return $total_questions;
	}

	public static function get_total_quiz_attempt_correct_answers( $ID ) {
		global $wpdb;
		$correct_answers = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(is_correct) FROM {$wpdb->prefix}academy_quiz_attempt_answers WHERE attempt_id=%d AND is_correct=%d", $ID, 1 ) );
		return (int) $correct_answers;
	}

	public static function is_image_answer_quiz_correct_answer( $IDs, $question_id = '' ) {
		global $wpdb;
		if ( is_array( $IDs ) ) {
			$correct_answer_count = 0;
			foreach ( $IDs as $ID => $value ) {
				$correct_answer_count  += $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(answer_id) FROM {$wpdb->prefix}academy_quiz_answers WHERE answer_id=%d AND answer_title=%s", $ID, $value ) );
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
			$given_answer_args = array_map(function( $answer ) {
				return preg_replace( '/\s*\|\s*/', '|', trim( $answer ) );
			}, $given_answer_args);

			// Join the answers with a pipe symbol
			$given_answer = implode( '|', $given_answer_args );

			// Query for a match ignoring any spaces around the pipe symbol in both the database and given answer
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
		if ( is_array( $IDs ) ) {
			$correct_answer_count = 0;
			$has_wrong_answer = false;
			foreach ( $IDs as $ID ) {
				$answers   = $wpdb->get_results( $wpdb->prepare( "SELECT is_correct FROM {$wpdb->prefix}academy_quiz_answers WHERE answer_id=%d", $ID ), OBJECT );
				if ( (bool) current( $answers )->is_correct === true ) {
					$correct_answer_count++;
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
		$answers   = $wpdb->get_results( $wpdb->prepare( "SELECT is_correct FROM {$wpdb->prefix}academy_quiz_answers WHERE answer_id=%d", $IDs ), OBJECT );
		return (bool) current( $answers )->is_correct;
	}

	public static function get_quiz_attempt_answer( $ID ) {
		global $wpdb;
		$attemp_answer   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}academy_quiz_attempt_answers WHERE attempt_answer_id=%d", $ID ), OBJECT );
		return current( $attemp_answer );
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
		}
		// insert attempt answer
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

	public static function get_quiz_attempt_answers_earned_marks( $user_id, $attempt_id ) {
		global $wpdb;
		$earned_marks = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT sum(achieved_mark) as total_marks FROM {$wpdb->prefix}academy_quiz_attempt_answers WHERE user_id=%d AND attempt_id=%d;",
				$user_id,
				$attempt_id
			),
			OBJECT
		);
		return (float) current( $earned_marks )->total_marks;
	}

	public static function get_quiz_attempt_details( $attempt_id, $user_id ) {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare( "SELECT
            attempt_answers.attempt_answer_id, 
            attempt_answers.attempt_id, 
            attempt_answers.user_id, 
            attempt_answers.question_id,
			attempt_answers.is_correct, 
            attempt_answers.answer as given_answer,
            quiz_answers.answer_title as correct_answer,
            quiz_answers.answer_content,
            quiz_answers.answer_id,
			quiz_answers.is_correct as is_correct_answer,
            quiz_questions.quiz_id,
            quiz_questions.question_title, 
            quiz_questions.question_type,
			quiz_attempts.is_manually_reviewed
            FROM {$wpdb->prefix}academy_quiz_attempt_answers as attempt_answers 
            LEFT JOIN {$wpdb->prefix}academy_quiz_questions as quiz_questions ON attempt_answers.question_id = quiz_questions.question_id
            LEFT JOIN {$wpdb->prefix}academy_quiz_answers as quiz_answers ON attempt_answers.question_id = quiz_answers.question_id
            LEFT JOIN {$wpdb->prefix}academy_quiz_attempts as quiz_attempts ON attempt_answers.attempt_id = quiz_attempts.attempt_id
            WHERE attempt_answers.attempt_id=%d AND attempt_answers.user_id=%d", $attempt_id, $user_id ), OBJECT );
	}

	public static function delete_quiz_attempt( $attempt_id ) {
		global $wpdb;
		$is_delete_attempts = $wpdb->delete( $wpdb->prefix . 'academy_quiz_attempts', array( 'attempt_id' => $attempt_id ), array( '%d' ) );
		$is_delete_attempt_answers = $wpdb->delete( $wpdb->prefix . 'academy_quiz_attempt_answers', array( 'attempt_id' => $attempt_id ), array( '%d' ) );
		return $is_delete_attempts === $is_delete_attempt_answers;
	}

	public static function delete_question( $question_id ) {
		global $wpdb;
		$is_deleted = $wpdb->delete( $wpdb->prefix . 'academy_quiz_questions', array( 'question_id' => $question_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'academy_quiz_answers', array( 'question_id' => $question_id ), array( '%d' ) );
		$attempt_id = $wpdb->delete( $wpdb->prefix . 'academy_quiz_attempt_answers', array( 'question_id' => $question_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'academy_quiz_attempts', array( 'attempt_id' => $attempt_id ), array( '%d' ) );
		return $is_deleted;
	}

	public static function delete_answer( $answer_id ) {
		global $wpdb;
		return $wpdb->delete( $wpdb->prefix . 'academy_quiz_answers', array( 'answer_id' => $answer_id ), array( '%d' ) );
	}

	public static function get_total_number_of_quizzes() {
		global $wpdb;
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
		return $wpdb->get_results( $wpdb->prepare( "SELECT answer_id, quiz_id, answer_title, image_id, view_format, answer_order, answer_created_at, answer_updated_at FROM {$wpdb->prefix}academy_quiz_answers WHERE question_id=%d AND question_type=%s AND is_correct=%d", $question_id, $quiz_type, 1 ), OBJECT );
	}
	public static function get_total_number_of_attempts( $user_id = 0 ) {
		global $wpdb;
		$query = "SELECT COUNT(attempt_id)
			FROM {$wpdb->prefix}academy_quiz_attempts qa
			INNER JOIN {$wpdb->prefix}posts p ON p.ID = qa.quiz_id
			AND p.post_type = 'academy_quiz'
		";
		if ( $user_id ) {
			$query .= $wpdb->prepare( ' AND p.post_author = %d', $user_id );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_var( $query );
	}
	public static function get_question_details_by_question_id( $question_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}academy_quiz_questions WHERE question_id = %d LIMIT 1", $question_id )
		);
	}
	public static function get_total_pending_attempt_by_attempt_id( $attempt_id ) {
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(quiz_id) FROM {$wpdb->prefix}academy_quiz_attempt_answers WHERE attempt_id = %d",
				array(
					$attempt_id
				)
			)
		);
	}
}
