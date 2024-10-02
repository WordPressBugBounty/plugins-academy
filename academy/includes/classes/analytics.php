<?php
namespace Academy\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Analytics {

	public function get_analytics() {
		$analytics = [
			'total_courses'          => $this->get_total_number_of_courses(),
			'total_enrolled_courses' => $this->get_total_number_of_enrolled_courses(),
			'total_lessons'          => $this->get_total_number_of_lessons(),
			'total_questions'        => $this->get_total_number_of_questions(),
			'total_instructors'      => $this->get_total_number_of_instructors(),
			'total_students'         => $this->get_total_number_of_students(),
			'total_reviews'          => $this->get_total_number_of_reviews(),
			'enrolled_info'          => $this->get_enrolled_info(),
		];

		return apply_filters( 'academy/get_analytics', $analytics );
	}

	public function get_total_number_of_courses() {
		global $wpdb;
		$results = $wpdb->get_var(
			$wpdb->prepare("SELECT COUNT(ID) 
            FROM {$wpdb->posts} 
            WHERE post_type = %s 
            AND post_status = %s", 'academy_courses', 'publish')
		);
		return (int) $results;
	}

	public function get_total_number_of_enrolled_courses( $user_id = 0 ) {
		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT COUNT(ID) 
			FROM {$wpdb->posts} 
			WHERE post_type = %s 
			AND post_status = %s",
			'academy_enrolled',
			'completed'
		);
		if ( $user_id ) {
			$query .= $wpdb->prepare( ' AND post_author = %d', $user_id );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_var( $query );
		return (int) $results;
	}
	public static function get_total_number_of_completed_courses() {
		global $wpdb;
		$number_of_completed = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(comment_ID), 
			FROM	{$wpdb->comments} 
			WHERE 	comment_agent = %s 
					AND comment_type = %s 
			",
				'academy',
				'course_completed'
			)
		);
		return $number_of_completed;
	}
	public static function get_total_number_of_completed_courses_by_student_id( $user_id ) {
		global $wpdb;
		$number_of_completed = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(comment_ID) 
				FROM	{$wpdb->comments} 
				WHERE	comment_agent = %s 
				AND comment_type = %s 
				AND user_id = %d;",
				'academy',
				'course_completed',
				$user_id
			)
		);
		return $number_of_completed;
	}
	public function get_total_number_of_enrolled_by_course_id( $course_id ) {
		global $wpdb;
		$results = $wpdb->get_var(
			$wpdb->prepare("SELECT COUNT(ID) 
            FROM {$wpdb->posts} 
            WHERE post_type =%s
			AND post_status = %s 
            AND post_parent = %d", 'academy_enrolled', 'completed', $course_id)
		);
		return (int) $results;
	}

	public function get_total_number_of_lessons() {
		global $wpdb;
		$results = $wpdb->get_var(
			$wpdb->prepare("SELECT COUNT(ID) 
            FROM {$wpdb->prefix}academy_lessons
            WHERE lesson_status=%s", 'publish')
		);
		return (int) $results;
	}

	public function get_total_number_of_questions() {
		global $wpdb;
		$results = $wpdb->get_var(
			$wpdb->prepare("SELECT COUNT(comment_ID) 
            FROM {$wpdb->comments}
            WHERE comment_type=%s
            AND comment_approved=%s", 'academy_qa', 'waiting_for_answer')
		);
		return (int) $results;
	}

	public function get_total_number_of_instructors( $course_id = 0 ) {
		global $wpdb;
		if ( $course_id ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare("SELECT COUNT(umeta_id) 
					FROM {$wpdb->usermeta} 
					WHERE meta_key = %s 
					AND meta_value = %d", 'academy_instructor_course_id', $course_id)
			);
		}
		return (int) $wpdb->get_var(
			$wpdb->prepare("SELECT COUNT(umeta_id) 
            FROM {$wpdb->usermeta} 
            WHERE meta_key = %s", 'is_academy_instructor')
		);
	}

	public function get_total_number_of_students() {
		global $wpdb;
		$results = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(umeta_id) 
            FROM {$wpdb->usermeta} 
            WHERE meta_key = %s", 'is_academy_student')
		);
		return (int) $results;
	}

	public function get_total_number_of_student_by_course_id( $course_id ) {
		global $wpdb;
		$total_student = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT post_author) 
				FROM {$wpdb->posts}
				WHERE post_type = %s
				AND post_status = %s
				AND post_parent = %d ORDER BY post_date DESC;",
				'academy_enrolled', 'completed', $course_id
			)
		);
		return $total_student;
	}

	public function get_total_number_of_reviews( $user_id = 0 ) {
		global $wpdb;
		$query = $wpdb->prepare( "SELECT COUNT(comment_ID) FROM {$wpdb->comments} WHERE comment_type =%s", 'academy_courses' );

		if ( $user_id ) {
			$query .= $wpdb->prepare( ' AND user_id =%d', $user_id );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_var( $query );
		return (int) $results;
	}

	public function get_enrolled_info() {
		global $wpdb;
		$from    = gmdate( 'Y-m-d', strtotime( ' - 30 days' ) );
		$to      = gmdate( 'Y-m-d' );
		$results = $wpdb->get_results(
			$wpdb->prepare("SELECT COUNT(ID) as total, DATE(post_date)  as date_format 
            FROM {$wpdb->posts} 
            WHERE post_type = %s 
			AND post_status = %s
            AND (post_date BETWEEN %s AND %s )
            GROUP BY date_format
            ORDER BY post_date ASC;", 'academy_enrolled', 'completed', $from, $to)
		);
		$results = $this->format_query_results_to_chart_data( $from, $to, $results );
		return $results;
	}

	public function format_query_results_to_chart_data( $from, $to, $data ) {
		$datesPeriod        = $this->get_empty_collection_date_by_period( $from, $to );
		$total     = wp_list_pluck( $data, 'total' );
		$queried_date       = wp_list_pluck( $data, 'date_format' );
		$date_wise_enrolled = array_combine( $queried_date, $total );

		$results = array_merge( $datesPeriod, $date_wise_enrolled );
		foreach ( $results as $key => $TotalCount ) {
			unset( $results[ $key ] );
			$format_date             = gmdate( 'Y-m-d', strtotime( $key ) );
			$results[ $format_date ] = intval( $TotalCount );
		}
		return $results;
	}

	public function get_empty_collection_date_by_period( $begin, $end ) {
		$begin     = new \DateTime( $begin );
		$end       = new \DateTime( $end );
		$interval  = \DateInterval::createFromDateString( '1 day' );
		$period    = new \DatePeriod( $begin, $interval, $end );
		$day_lists = array();
		foreach ( $period as $value ) {
			$day_lists[ $value->format( 'Y-m-d' ) ] = 0;
		}
		return $day_lists;
	}

	public function get_number_of_questions_by_course_id( $course_id ) {
		global $wpdb;
		$results = $wpdb->get_var(
			$wpdb->prepare("SELECT COUNT(comment_ID) 
                     FROM {$wpdb->comments}
                     WHERE comment_post_ID =%d
                     AND comment_approved=%s",
				$course_id, 'waiting_for_answer'
			)
		);
		return (int) $results;
	}

	public function get_total_number_of_reviews_by_course_id( $id ) {
		global $wpdb;
		$results = $wpdb->get_var(
			$wpdb->prepare("SELECT COUNT(comment_ID) 
                     FROM {$wpdb->comments} 
                     WHERE comment_type =%s
	              AND comment_post_ID = %d",
				'academy_courses', $id
			)
		);
		return (int) $results;
	}

	public function get_total_earning_by_course_id( $course_id ) {
		global $wpdb;
		$product_id = \Academy\Helper::get_course_product_id( $course_id );
		if ( $product_id && \Academy\Helper::is_active_woocommerce() ) {
			$total_earning = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT SUM(meta_value)
					FROM {$wpdb->prefix}woocommerce_order_itemmeta AS item_meta
					INNER JOIN {$wpdb->prefix}woocommerce_order_items AS order_items
					ON item_meta.order_item_id = order_items.order_item_id
					WHERE order_items.order_item_type = 'line_item'
					AND item_meta.meta_key = '_line_total'
					AND order_items.order_id IN (
						SELECT order_id 
						FROM {$wpdb->prefix}woocommerce_order_items AS items
						INNER JOIN {$wpdb->prefix}wc_orders AS orders
						ON items.order_id = orders.id
						WHERE orders.status = %s
					)
					AND order_items.order_item_id IN (
						SELECT order_item_id
						FROM {$wpdb->prefix}woocommerce_order_itemmeta
						WHERE meta_key = '_product_id'
						AND meta_value = %d
					)",
					'wc-completed', $product_id
				)
			);

			$currency_symbol = html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' );
			return $currency_symbol . (float) $total_earning;
		}//end if
		return 0;
	}
}
