<?php
namespace Academy\Traits;

use Academy\Helper;
use WP_Query;
use function _deprecated_function;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Courses {

	public static function is_course_taxonomy() {
		return is_tax( get_object_taxonomies( 'academy_courses' ) );
	}
	public static function get_course_rating( $course_id ) {
		global $wpdb;

		$ratings = array(
			'rating_count'   => 0,
			'rating_sum'     => 0,
			'rating_avg'     => 0.00,
			'count_by_value' => array(
				5 => 0,
				4 => 0,
				3 => 0,
				2 => 0,
				1 => 0,
			),
		);

		$rating = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(meta_value) AS rating_count,
					SUM(meta_value) AS rating_sum
			FROM	{$wpdb->comments}
					INNER JOIN {$wpdb->commentmeta}
							ON {$wpdb->comments}.comment_ID = {$wpdb->commentmeta}.comment_id
			WHERE 	{$wpdb->comments}.comment_post_ID = %d
					AND {$wpdb->comments}.comment_type = %s
					AND meta_key = %s;
			",
				$course_id,
				'academy_courses',
				'academy_rating'
			)
		);

		if ( $rating->rating_count ) {
			$avg_rating = number_format( ( $rating->rating_sum / $rating->rating_count ), 1 );

			$stars = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT commentmeta.meta_value AS rating, 
						COUNT(commentmeta.meta_value) as rating_count 
				FROM	{$wpdb->comments} comments
						INNER JOIN {$wpdb->commentmeta} commentmeta
								ON comments.comment_ID = commentmeta.comment_id
				WHERE	comments.comment_post_ID = %d 
						AND comments.comment_type = %s
						AND commentmeta.meta_key = %s
				GROUP BY commentmeta.meta_value;
				",
					$course_id,
					'academy_courses',
					'academy_rating'
				)
			);

			$ratings = array(
				5 => 0,
				4 => 0,
				3 => 0,
				2 => 0,
				1 => 0,
			);
			foreach ( $stars as $star ) {
				$index = (int) $star->rating;
				array_key_exists( $index, $ratings ) ? $ratings[ $index ] = $star->rating_count : 0;
			}

			$ratings = array(
				'rating_count'   => $rating->rating_count,
				'rating_sum'     => $rating->rating_sum,
				'rating_avg'     => $avg_rating,
				'count_by_value' => $ratings,
			);
		}//end if

		return (object) $ratings;
	}

	public static function get_courses_reviews( $course_id, $offset = 0, $limit = 200 ) {
		global $wpdb;

		$reviews = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT {$wpdb->comments}.comment_ID, 
					{$wpdb->comments}.comment_post_ID, 
					{$wpdb->comments}.comment_author, 
					{$wpdb->comments}.comment_author_email, 
					{$wpdb->comments}.comment_date, 
					{$wpdb->comments}.comment_content, 
					{$wpdb->comments}.user_id, 
					{$wpdb->commentmeta}.meta_value AS rating,
					{$wpdb->users}.display_name 
			
			FROM 	{$wpdb->comments}
					INNER JOIN {$wpdb->commentmeta} 
					ON {$wpdb->comments}.comment_ID = {$wpdb->commentmeta}.comment_id 
					LEFT JOIN {$wpdb->users}
					ON {$wpdb->comments}.user_id = {$wpdb->users}.ID
			WHERE 	{$wpdb->comments}.comment_post_ID = %d 
					AND comment_type = 'academy_courses' AND meta_key = 'academy_rating'
			ORDER BY comment_ID DESC
			LIMIT 	%d, %d;
			",
				$course_id,
				$offset,
				$limit
			)
		);

		return $reviews;
	}

	public static function get_instructor_ratings( $instructor_id ) {
		global $wpdb;

		$ratings = array(
			'rating_count' => 0,
			'rating_sum'   => 0,
			'rating_avg'   => 0.00,
		);

		$rating = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(rating.meta_value) as rating_count, SUM(rating.meta_value) as rating_sum  
			FROM 	{$wpdb->usermeta} courses
					INNER JOIN {$wpdb->comments} reviews
							ON courses.meta_value = reviews.comment_post_ID
						   AND reviews.comment_type = 'academy_courses'
					INNER JOIN {$wpdb->commentmeta} rating
							ON reviews.comment_ID = rating.comment_id
						   AND rating.meta_key = 'academy_rating'
			WHERE 	courses.user_id = %d
					AND courses.meta_key = %s
			",
				$instructor_id,
				'academy_instructor_course_id'
			)
		);

		if ( $rating->rating_count ) {
			$avg_rating = number_format( ( $rating->rating_sum / $rating->rating_count ), 2 );

			$ratings = array(
				'rating_count' => $rating->rating_count,
				'rating_sum'   => $rating->rating_sum,
				'rating_avg'   => $avg_rating,
			);
		}

		return (object) $ratings;
	}

	public static function single_star_rating_generator( $current_rating = 0.00 ) {
		$output = '<span class="academy-group-star">';
		if ( 5 < $current_rating && 0 > $current_rating ) {
			$output .= '<i class="academy-icon academy-icon--star-half"></i>';
		} elseif ( 0 === $current_rating ) {
			$output .= '<i class="academy-icon academy-icon--star-alt"></i>';
		} else {
			$output .= '<i class="academy-icon academy-icon--star"></i>';
		}
		$output .= '</span>';
		return $output;
	}
	public static function star_rating_generator( $current_rating = 0.00 ) {
		$output = '<span class="academy-group-star">';
		for ( $i = 1; $i <= 5; $i++ ) {
			$intRating = (int) $current_rating;

			if ( $intRating >= $i ) {
				$output .= '<i class="academy-icon academy-icon--star" data-rating-value="' . $i . '"></i>';
			} else {
				if ( ( $current_rating - $i ) === -0.5 ) {
					$output .= '<i class="academy-icon academy-icon--star-half" data-rating-value="' . $i . '"></i>';
				} else {
					$output .= '<i class="academy-icon academy-icon--star-alt" data-rating-value="' . $i . '"></i>';
				}
			}
		}
		$output .= '</span>';
		return $output;
	}

	public static function do_enroll( $course_id, $user_id, $order_id = 0 ) {
		if ( ! $course_id || ! $user_id || self::is_enrolled( $course_id, $user_id, 'any' ) ) {
			return false;
		}

		do_action( 'academy/course/before_enroll', $course_id );

		$title = __( 'Course Enrolled', 'academy' ) . ' ' . gmdate( get_option( 'date_format' ) ) . ' @ ' . gmdate( get_option( 'time_format' ) );

		$enrolment_status = 'completed';

		if ( $order_id ) {
			$enrolment_status = apply_filters( 'academy/course/change_enrollment_status', $enrolment_status );
		}

		$enroll_data = apply_filters(
			'academy/course/enroll_data',
			array(
				'post_type'   => 'academy_enrolled',
				'post_title'  => $title,
				'post_status' => $enrolment_status,
				'post_author' => $user_id,
				'post_parent' => $course_id,
			)
		);

		// Insert the post into the database
		$enroll_id = wp_insert_post( $enroll_data );
		if ( $enroll_id ) {
			// Make Current User as Students
			update_user_meta( $user_id, 'is_academy_student', self::get_time() );

			if ( $order_id ) {
				$product_id = self::get_course_product_id( $course_id );
				update_post_meta( $enroll_id, 'academy_enrolled_by_order_id', $order_id );
				update_post_meta( $enroll_id, 'academy_enrolled_by_product_id', $product_id );
				update_post_meta( $order_id, 'is_academy_order_for_course', self::get_time() );
				update_post_meta( $order_id, 'academy_order_for_course_id_' . $course_id, $enroll_id );
			}

			if ( 'completed' === $enrolment_status ) {
				do_action( 'academy/course/after_enroll', $course_id, $enroll_id, $user_id );
			}

			return $enroll_id;
		}
		return false;
	}

	public static function is_enrolled( $course_id, $user_id, $status = 'completed' ) {
		if ( is_user_logged_in() ) {
			global $wpdb;

			do_action( 'academy/course/is_enrolled_before', $course_id, $user_id );

			$query = $wpdb->prepare(
				"SELECT ID,
					post_author,
					post_date,
					post_date_gmt,
					post_title,
					post_status as enrolled_status
				FROM {$wpdb->posts}
				WHERE post_type = %s
					AND post_parent = %d
					AND post_author = %d",
				'academy_enrolled',
				$course_id,
				$user_id
			);

			if ( 'completed' === $status ) {
				$query .= $wpdb->prepare( ' AND post_status = %s', $status );
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$getEnrolled = $wpdb->get_row( $query );

			if ( $getEnrolled ) {
				return apply_filters( 'academy/course/is_enrolled', $getEnrolled, $course_id, $user_id );
			}
		}//end if

		return false;
	}

	public static function get_course_product_id( $course_id ) {
		$product_id = (int) get_post_meta( $course_id, 'academy_course_product_id', true );
		return apply_filters( 'academy/course/get_course_product_id', $product_id, $course_id );
	}

	public static function update_enrollment_status( $course_id, $enrolled_id, $user_id, $status = 'completed' ) {
		if ( ! $course_id || ! $enrolled_id || ! $user_id ) {
			return false;
		}

		global $wpdb;
		$data = array( 'post_status' => $status );
		$where = array(
			'ID' => $enrolled_id,
			'post_author' => $user_id,
			'post_parent' => $course_id
		);

		$is_update = $wpdb->update( $wpdb->posts, $data, $where );
		if ( $is_update && 'completed' === $status ) {
			do_action( 'academy/course/after_enroll', $course_id, $enrolled_id, $user_id );
		}

		return $is_update;
	}

	public static function is_instructor_of_this_course( $instructor_id, $course_id ) {
		global $wpdb;

		if ( ! $instructor_id || ! $course_id ) {
			return false;
		}

		$instructor = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT umeta_id
			FROM   {$wpdb->usermeta}
			WHERE  user_id = %d
				AND meta_key = 'academy_instructor_course_id'
				AND meta_value = %d
			",
				$instructor_id,
				$course_id
			)
		);

		if ( is_array( $instructor ) && count( $instructor ) ) {
			return $instructor;
		}

		return false;
	}

	public static function is_completed_course( $course_id, $user_id, $allow_details = false ) {
		if ( ! is_user_logged_in() ) {
			return apply_filters( 'academy/is_completed_course', false, $course_id, $user_id );
		}

		global $wpdb;
		$is_completed = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT comment_ID, 
					comment_post_ID AS course_id, 
					comment_author AS completed_user_id, 
					comment_date AS completion_date, 
					comment_content AS completed_hash 
			FROM	{$wpdb->comments} 
			WHERE 	comment_agent = %s 
					AND comment_type = %s 
					AND comment_post_ID = %d 
					AND user_id = %d;
			",
				'academy',
				'course_completed',
				$course_id,
				$user_id
			)
		);

		if ( $is_completed ) {
			return apply_filters( 'academy/is_completed_course', $allow_details ? $is_completed : true, $course_id, $user_id );
		}

		return apply_filters( 'academy/is_completed_course', false, $course_id, $user_id );
	}

	public static function get_the_course_category( $ID ) {
		return get_the_terms( $ID, 'academy_courses_category' );
	}

	public static function get_completed_courses_ids_by_user( $user_id ) {
		global $wpdb;
		$course_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT comment_post_ID AS course_id
			FROM	{$wpdb->comments} 
			WHERE 	comment_agent = %s 
					AND comment_type = %s 
					AND user_id = %d;
			",
				'academy',
				'course_completed',
				$user_id
			)
		);
		return $course_ids;
	}

	public static function get_pending_enrolled_courses_ids_by_user( $user_id ) {
		global $wpdb;
		$course_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_parent
			FROM 	{$wpdb->posts}
			WHERE 	post_type = %s
					AND post_status = %s
					AND post_author = %d;
			",
				'academy_enrolled',
				'processing',
				$user_id
			)
		);

		return $course_ids;
	}

	public static function get_complete_courses_ids_by_user( $user_id ) {
		global $wpdb;
		$course_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT comment_post_ID
			FROM 	{$wpdb->comments}
			WHERE 	comment_type = %s
					AND comment_approved = %s
					AND comment_author = %d
					AND user_id = %d;",
				'course_completed',
				'approved',
				$user_id,
				$user_id
			)
		);

		return $course_ids;
	}

	public static function get_enrolled_courses_by_user( $user_id, $post_status = 'publish', $offset = 0, $per_page = 10 ) {
		$course_ids = self::get_enrolled_courses_ids_by_user( $user_id );
		if ( count( $course_ids ) ) {
			$course_args = array(
				'post_type'      => 'academy_courses',
				'post_status'    => $post_status,
				'post__in'       => $course_ids,
				'offset'         => $offset,
				'posts_per_page' => $per_page,
			);
			return new WP_Query( $course_args );
		}
		return false;
	}

	public static function get_enrolled_courses_ids_by_user( $user_id ) {
		global $wpdb;
		$course_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_parent
			FROM 	{$wpdb->posts}
			WHERE 	post_type = %s
					AND post_status = %s
					AND post_author = %d;
			",
				'academy_enrolled',
				'completed',
				$user_id
			)
		);

		return $course_ids;
	}

	public static function get_wishlist_courses_by_user( $user_id, $post_status = 'publish' ) {
		$course_ids = self::get_wishlist_courses_ids_by_user( $user_id );
		if ( count( $course_ids ) ) {
			$course_args = array(
				'post_type'      => 'academy_courses',
				'post_status'    => $post_status,
				'post__in'       => $course_ids,
				'posts_per_page' => -1,
			);
			return new WP_Query( $course_args );
		}
		return false;
	}

	public static function get_wishlist_courses_ids_by_user( $user_id ) {
		global $wpdb;
		$course_ids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value from {$wpdb->usermeta} WHERE user_id = %d AND meta_key = 'academy_course_wishlist';", $user_id ) );
		return $course_ids;
	}

	public static function get_total_number_of_course_topics( $course_id ) {
		if ( empty( $course_id ) ) {
			return 0;
		}
		$count      = 0;
		$curriculum = get_post_meta( $course_id, 'academy_course_curriculum', true );
		$topics     = wp_list_pluck( $curriculum, 'topics' );
		if ( is_array( $topics ) ) {
			foreach ( $topics as $topics_lists ) {
				$count += count( $topics_lists );
			}
		}
		return (int) $count;
	}

	public static function get_total_number_of_completed_course_lessons( $course_id ) {
		if ( empty( $course_id ) ) {
			return 0;
		}

		_deprecated_function( __METHOD__, '2.0.0', 'get_total_number_of_completed_course_topics_by_student_id' );

		$count      = 0;
		$completed_topics = json_decode( get_user_meta( get_current_user_id(), 'academy_course_' . $course_id . '_completed_topics', true ), true );
		if ( is_array( $completed_topics ) && count( $completed_topics ) ) {
			foreach ( $completed_topics as $topics_item ) {
				if ( is_array( $topics_item ) ) {
					$count += count( $topics_item );
				}
			}
		}
		return (int) $count;
	}

	public static function is_course_purchasable( $course_id ) {
		$course_type = self::get_course_type( $course_id );
		if ( 'paid' === $course_type ) {
			return apply_filters( 'academy/course/is_course_purchasable', true, $course_id );
		}
		return apply_filters( 'academy/course/is_course_purchasable', false, $course_id );
	}

	public static function get_course_type( $course_id ) {
		$course_type = get_post_meta( $course_id, 'academy_course_type', true );
		return apply_filters( 'academy/course/get_course_type', $course_type, $course_id );
	}

	public static function is_product_in_cart( $product_id ) {
		global $woocommerce;
		if ( $woocommerce->cart ) {
			foreach ( $woocommerce->cart->get_cart() as $key => $val ) {
				if ( (int) $product_id === (int) $val['product_id'] ) {
					return true;
				}
			}
		}
		return false;
	}

	public static function product_belongs_with_course( $product_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM 	{$wpdb->postmeta} WHERE	meta_key = %s  AND meta_value = %d  limit 1",
				'academy_course_product_id',
				$product_id
			)
		);
	}

	public static function download_belongs_with_course( $downlaod_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %d LIMIT 1",
				'academy_course_download_id',
				$downlaod_id
			)
		);
	}

	public static function download_belongs_to_course( $download_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM 	{$wpdb->postmeta} WHERE	meta_key = %s  AND meta_value = %d  limit 1",
				'academy_course_download_id',
				$download_id
			)
		);

	}

	public static function is_academy_order( $order_id ) {
		return get_post_meta( $order_id, 'is_academy_order_for_course', true );
	}

	public static function get_course_enrolled_ids_by_order_id( $order_id ) {
		global $wpdb;
		$courses_ids = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE %s", $order_id, 'academy_order_for_course_id_%' ) );
		if ( is_array( $courses_ids ) && count( $courses_ids ) ) {
			$course_enrolled_by_order = array();
			foreach ( $courses_ids as $courses_id ) {
				$course_id                  = str_replace( 'academy_order_for_course_id_', '', $courses_id->meta_key );
				$course_enrolled_by_order[] = array(
					'course_id'   => $course_id,
					'enrolled_id' => $courses_id->meta_value,
					'order_id'    => $courses_id->post_id,
				);
			}
			return $course_enrolled_by_order;
		}
		return false;
	}

	public static function get_course_preview_video( $id ) {
		$output      = '';
		$intro_video = get_post_meta( $id, 'academy_course_intro_video', true );
		if ( $intro_video && is_array( $intro_video ) && count( $intro_video ) > 1 && ! empty( $intro_video[1] ) ) {
			$type = $intro_video[0];
			if ( 'html5' === $type ) {
				$attachment_id = (int) $intro_video[1];
				$att_url       = wp_get_attachment_url( $attachment_id );
				$thumb_id      = get_post_thumbnail_id( $attachment_id );
				$thumb_url     = wp_get_attachment_url( $thumb_id );
				$output       .= sprintf(
					'<video class="academy-plyr" id="academyPlayer" playsinline controls data-poster="%s">
                <source src="%s" type="video/mp4" />
            </video>',
					$thumb_url,
					$att_url
				);
			} elseif ( 'embedded' === $type ) {
				$embed = Helper::parse_embedded_url( $intro_video[1] );
				$output .= sprintf( '<div class="academy-embed-responsive academy-embed-responsive-16by9"><iframe class="academy-embed-responsive-item" src="%s" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe></div>', esc_url( $embed['url'] ) );
			} elseif ( 'youtube' === $type || 'vimeo' === $type ) {
				$embed = Helper::get_basic_url_to_embed_url( $intro_video[1] );
				$output .= sprintf( '<div class="academy-plyr plyr__video-embed" id="academyPlayer"><iframe src="%s" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe></div>', esc_url( $embed['url'] ) );
			} elseif ( 'shortcode' === $type ) {
				$output .= do_shortcode( $intro_video[1] );
			} else {
				$embed = Helper::get_basic_url_to_embed_url( $intro_video[1] );
				$output .= sprintf( '<div class="academy-embed-responsive academy-embed-responsive-16by9"><iframe class="academy-embed-responsive-item" src="%s" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe></div>', esc_url( $embed['url'] ) );
			}//end if
		}//end if
		return $output;
	}

	public static function is_course_fully_booked( $course_id ) {
		$total_enrolled = self::count_course_enrolled( $course_id );
		$max_students   = (int) get_post_meta( $course_id, 'academy_course_max_students', true );
		if ( $max_students ) {
			return $max_students <= $total_enrolled;
		}
		return false;
	}

	public static function count_course_enrolled( $course_id ) {
		global $wpdb;
		$course_ids = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(ID) 
			FROM	{$wpdb->posts} 
			WHERE 	post_type = %s
					AND post_status = %s
					AND post_parent = %d;
			",
				'academy_enrolled',
				'completed',
				$course_id
			)
		);

		return (int) $course_ids;
	}

	public static function get_total_number_of_students() {
		global $wpdb;
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(enrollment.ID)
			FROM 	{$wpdb->posts} enrollment 
					LEFT  JOIN {$wpdb->posts} course
							ON enrollment.post_parent=course.ID
			WHERE 	course.post_type = %s
					AND course.post_status = %s
					AND enrollment.post_type = %s
					AND enrollment.post_status = %s;
			",
				'academy_courses',
				'publish',
				'academy_enrolled',
				'completed'
			)
		);
		return (int) $count;
	}

	public static function get_total_number_of_students_by_instructor( $instructor_id ) {
		global $wpdb;
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT enrollment.post_author)
			FROM 	{$wpdb->posts} enrollment 
					LEFT  JOIN {$wpdb->posts} course
							ON enrollment.post_parent=course.ID
			WHERE 	course.post_author = %d
                    AND course.post_type = %s
					AND course.post_status = %s
					AND enrollment.post_type = %s
					AND enrollment.post_status = %s;
			",
				$instructor_id,
				'academy_courses',
				'publish',
				'academy_enrolled',
				'completed'
			)
		);
		return (int) $count;
	}

	public static function get_reviews_by_user( $user_id, $offset = 0, $limit = 150 ) {
		global $wpdb;
		$reviews = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT {$wpdb->comments}.comment_ID,
					{$wpdb->comments}.comment_post_ID,
					{$wpdb->comments}.comment_author,
					{$wpdb->comments}.comment_author_email,
					{$wpdb->comments}.comment_date,
					{$wpdb->comments}.comment_content,
					{$wpdb->comments}.user_id,
					{$wpdb->commentmeta}.meta_value as rating,
					{$wpdb->users}.display_name
			
			FROM 	{$wpdb->comments}
					INNER JOIN {$wpdb->commentmeta} 
							ON {$wpdb->comments}.comment_ID = {$wpdb->commentmeta}.comment_id 
					INNER  JOIN {$wpdb->users}
							ON {$wpdb->comments}.user_id = {$wpdb->users}.ID
			WHERE 	{$wpdb->comments}.user_id = %d 
					AND comment_type = %s
					AND meta_key = %s
			ORDER BY comment_ID DESC
			LIMIT %d, %d;
			",
				$user_id,
				'academy_courses',
				'academy_rating',
				$offset,
				$limit
			)
		);
		return $reviews;
	}

	public static function get_reviews_by_instructor( $instructor_id, $offset = 0, $limit = 150 ) {
		global $wpdb;
		$results    = array();
		$course_ids = (array) self::get_assigned_courses_ids_by_instructor_id( $instructor_id );
		if ( count( $course_ids ) ) {
			$implode_ids_placeholder = implode( ', ', array_fill( 0, count( $course_ids ), '%d' ) );
			$prepare_values           = array_merge( $course_ids, array(
				'academy_courses',
				'academy_rating',
				$offset,
				$limit,
			));
			// phpcs:disable
			$results     = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT {$wpdb->comments}.comment_ID, 
						{$wpdb->comments}.comment_post_ID, 
						{$wpdb->comments}.comment_author, 
						{$wpdb->comments}.comment_author_email, 
						{$wpdb->comments}.comment_date, 
						{$wpdb->comments}.comment_content, 
						{$wpdb->comments}.user_id, 
						{$wpdb->commentmeta}.meta_value AS rating,
						{$wpdb->users}.display_name 
			
				FROM 	{$wpdb->comments}
						INNER JOIN {$wpdb->commentmeta} 
								ON {$wpdb->comments}.comment_ID = {$wpdb->commentmeta}.comment_id 
						INNER JOIN {$wpdb->users}
								ON {$wpdb->comments}.user_id = {$wpdb->users}.ID
				WHERE 	{$wpdb->comments}.comment_post_ID IN($implode_ids_placeholder) 
						AND comment_type = %s
						AND meta_key = %s
				ORDER BY comment_ID DESC
				LIMIT %d, %d;
				",
					$prepare_values
				)
			);
			// phpcs:enable
		}//end if
		return (array) $results;
	}

	public static function get_assigned_courses_ids_by_instructor_id( $user_id ) {
		global $wpdb;
		$course_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT 	meta_value
			FROM		{$wpdb->usermeta}
			WHERE 		meta_key = %s
						AND user_id = %d
			GROUP BY 	meta_value;
			",
				'academy_instructor_course_id',
				$user_id
			)
		);
		return $course_ids;
	}

	public static function get_orders_by_user_id( $user_id ) {
		global $wpdb;
		$orders = [];

		if ( 'yes' === get_option( 'woocommerce_custom_orders_table_enabled' ) ) {
			$orders = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT
						orders.id AS ID,
						orders.customer_id AS post_author,
						orders.date_created_gmt AS post_date_gmt,
						orders.date_created_gmt AS post_date,
						orders.status AS post_status,
						orders.date_updated_gmt AS post_modified_gmt
					FROM {$wpdb->prefix}wc_orders AS orders
					INNER JOIN {$wpdb->prefix}postmeta AS meta
					ON orders.id = meta.post_id
						AND meta.meta_key = 'is_academy_order_for_course'
					WHERE orders.customer_id = %d",
					$user_id
				)
			);
		} else {
			$post_type = 'shop_order';
			$user_meta = '_customer_user';
			$orders    = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT {$wpdb->posts}.*
				FROM	{$wpdb->posts}
						INNER JOIN {$wpdb->postmeta} customer
							ON id = customer.post_id
							AND customer.meta_key = %s
						INNER JOIN {$wpdb->postmeta} academy_order
							ON id = academy_order.post_id
							AND academy_order.meta_key = 'is_academy_order_for_course'
				WHERE	post_type = %s
						AND customer.meta_value = %d 
				ORDER BY {$wpdb->posts}.id DESC",
					$user_meta,
					$post_type,
					$user_id
				)
			);
		}//end if
		return $orders;
	}

	public static function order_status_context( $status = null ) {
		$status      = str_replace( 'wc-', '', $status );
		$status_name = ucwords( str_replace( '-', ' ', $status ) );
		return "<span class='label-order-status label-status-{$status}'>$status_name</span>";
	}

	public static function get_total_number_of_course_lesson( $ID ) {
		$total_lessons = 0;
		$curriculum    = get_post_meta( $ID, 'academy_course_curriculum', true );
		if ( is_array( $curriculum ) ) {
			if ( is_array( $curriculum ) && count( $curriculum ) ) {
				foreach ( $curriculum as $topic ) {
					if ( isset( $topic['topics'] ) && is_array( $topic['topics'] ) && count( $topic['topics'] ) ) {
						foreach ( $topic['topics'] as $topic_item ) {
							if ( isset( $topic_item['type'] ) && 'lesson' === $topic_item['type'] ) {
								// phpcs:ignore Squiz.Operators.IncrementDecrementUsage.Found
								$total_lessons += 1;
							}
							if ( 'sub-curriculum' === $topic_item['type'] ) {
								foreach ( $topic_item['topics'] as $sub_topic ) {
									if ( isset( $sub_topic['type'] ) && 'lesson' === $sub_topic['type'] ) {
										// phpcs:ignore Squiz.Operators.IncrementDecrementUsage.Found
										$total_lessons += 1;
									}
								}
							}
						}
					}
				}
			}
		}//end if
		return $total_lessons;
	}

	public static function get_course_duration( $ID ) {
		$duration = get_post_meta( $ID, 'academy_course_duration', true );
		if ( is_array( $duration ) && count( $duration ) && ( $duration[0] || $duration[1] || $duration[2] ) ) {
			$duration = array_map(function ( $number ) {
				return sprintf( '%02d', $number );
			}, $duration);
			return implode( ':', $duration );
		}
		return '';
	}

	public static function get_instructors_by_course_id( $course_id, $offset = 0, $per_page = 10 ) {
		global $wpdb;
		$instructors = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID,
					display_name,
					get_course.meta_value AS academy_course_id,
					academy_profile_designation.meta_value AS academy_profile_designation,
					academy_profile_bio.meta_value AS academy_profile_bio
			FROM	{$wpdb->users} 
					INNER JOIN {$wpdb->usermeta} get_course 
					    ON ID = get_course.user_id 
						AND get_course.meta_key = %s 
						AND get_course.meta_value = %d 
					LEFT  JOIN {$wpdb->usermeta} academy_profile_designation 
						    ON ID = academy_profile_designation.user_id 
						   AND academy_profile_designation.meta_key = %s 
					LEFT  JOIN {$wpdb->usermeta} academy_profile_bio 
						    ON ID = academy_profile_bio.user_id 
						   AND academy_profile_bio.meta_key = %s
					LIMIT %d, %d
			",
				'academy_instructor_course_id',
				$course_id,
				'academy_profile_designation',
				'academy_profile_bio',
				$offset,
				$per_page
			)
		);

		if ( count( $instructors ) ) {
			return $instructors;
		}
		return false;
	}

	public static function get_instructor_by_author_id( $author_id ) {
		global $wpdb;
		$instructors = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID,
					display_name,
					academy_profile_designation.meta_value AS academy_profile_designation,
					academy_profile_bio.meta_value AS academy_profile_bio
			FROM	{$wpdb->users} 
					LEFT  JOIN {$wpdb->usermeta} academy_profile_designation 
						ON ID = academy_profile_designation.user_id 
						AND academy_profile_designation.meta_key = %s 
					LEFT  JOIN {$wpdb->usermeta} academy_profile_bio 
						ON ID = academy_profile_bio.user_id 
						AND academy_profile_bio.meta_key = %s 
			WHERE ID = %d
			",
				'academy_profile_designation',
				'academy_profile_bio',
				$author_id
			)
		);

		if ( count( $instructors ) ) {
			return $instructors;
		}
		return false;
	}

	public static function get_course_ids_by_instructor_id( $instructor_id ) {
		global $wpdb;
		$course_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_value
			FROM   {$wpdb->usermeta}
			WHERE  user_id = %d
				AND meta_key = 'academy_instructor_course_id'
			",
				$instructor_id
			)
		);
		if ( count( $course_ids ) ) {
			return $course_ids;
		}
		return false;
	}

	public static function get_user_id_from_course_id( $course_id ) {
		global $wpdb;
		$results = $wpdb->get_var( $wpdb->prepare( "SELECT post_author FROM {$wpdb->posts} WHERE ID = %d ", $course_id ) );
		return $results;
	}

	public static function get_order_status_by_id( $order_id ) {
		global $wpdb;
		$results = $wpdb->get_var( $wpdb->prepare( "SELECT post_status from {$wpdb->posts} where ID = %d ", $order_id ) );
		return $results;
	}

	public static function prepare_course_search_query_args( $data ) {
		$defaults = array(
			'search'         => '',
			'category'       => [],
			'tags'           => [],
			'levels'         => [],
			'type'           => [],
			'paged'          => 1,
			'posts_per_page' => 12,
		);
		$data     = wp_parse_args( $data, $defaults );

		// base
		$args = array(
			'post_type'      => apply_filters( 'academy/get_course_archive_post_types', array( 'academy_courses' ) ),
			'post_status'    => 'publish',
			'posts_per_page' => $data['posts_per_page'],
			'paged'          => $data['paged'],
		);

		// taxonomy
		$tax_query = array();
		if ( count( $data['category'] ) > 0 ) {
			$tax_query[] = array(
				'taxonomy' => 'academy_courses_category',
				'field'    => 'slug',
				'terms'    => $data['category'],
			);
		}
		if ( count( $data['tags'] ) > 0 ) {
			$tax_query[] = array(
				'taxonomy' => 'academy_courses_tag',
				'field'    => 'slug',
				'terms'    => $data['tags'],
			);
		}
		if ( count( $tax_query ) > 0 ) {
			$tax_query['relation'] = 'AND';
			$args['tax_query']     = $tax_query;
		}
		// meta
		$meta_query = array();
		if ( count( $data['levels'] ) > 0 ) {
			$meta_query[] = array(
				'key'     => 'academy_course_difficulty_level',
				'value'   => $data['levels'],
				'compare' => 'IN',
			);
		}
		if ( count( $data['type'] ) > 0 ) {
			$meta_query[] = array(
				'key'     => 'academy_course_type',
				'value'   => $data['type'],
				'compare' => 'IN',
			);
		}
		if ( count( $meta_query ) > 0 ) {
			$tax_query['relation'] = 'AND';
			$args['meta_query']    = $meta_query;
		}

		// search
		if ( ! empty( $data['search'] ) ) {
			$args['s'] = $data['search'];
		}

		// order by
		if ( isset( $data['orderby'] ) ) {
			switch ( $data['orderby'] ) {
				case 'name':
					$args['orderby'] = 'post_title';
					$args['order']   = 'asc';
					break;
				case 'date':
					$args['orderby'] = 'publish_date';
					$args['order']   = 'desc';
					break;
				case 'modified':
					$args['orderby'] = 'modified';
					$args['order']   = 'desc';
					break;
				case 'ratings':
					$args['orderby'] = 'comment_count';
					$args['order']   = 'desc';
					break;
				case 'menu_order':
					$args['orderby'] = 'menu_order';
					$args['order']   = 'desc';
					break;
				default:
					$args['orderby'] = 'ID';
					$args['order']   = 'desc';
			}//end switch
		}//end if
		return apply_filters( 'academy/get_course_archive_search_query_args', $args, $data );
	}

	public static function get_sample_permalink_args( $id, $new_title = null, $new_slug = null ) {
		$response = array();
		$post = get_post( $id );
		if ( ! $post ) {
			return '';
		}
		list($permalink, $post_name) = get_sample_permalink( $post->ID, $new_title, $new_slug );
		$view_link      = false;
		if ( current_user_can( 'read_post', $post->ID ) ) {
			if ( 'draft' === $post->post_status || empty( $post->post_name ) ) {
				$view_link      = get_preview_post_link( $post );
			} else {
				if ( 'publish' === $post->post_status || 'attachment' === $post->post_type ) {
					$view_link = get_permalink( $post );
				} else {
					// Allow non-published (private, future) to be viewed at a pretty permalink, in case $post->post_name is set.
					$view_link = str_replace( array( '%pagename%', '%postname%' ), $post->post_name, $permalink );
				}
			}
		}
		if ( false !== $view_link ) {
			$response['view_link'] = esc_url( $view_link );
		} else {
			$response['permalink'] = $permalink;
		}

		$display_link   = rtrim( str_replace( '%pagename%', $post_name, $permalink ) );
		$response['editable_postname'] = $post_name;
		$response['display_link'] = $display_link;
		$response['post_name'] = $post_name;

		return (array) $response;
	}

	public static function get_last_course_id() {
		global $wpdb;
		$results = $wpdb->get_col( "
			SELECT MAX(ID) FROM {$wpdb->prefix}posts
			WHERE post_type LIKE 'academy_courses'
			AND post_status = 'publish'"
		);
		return reset( $results );
	}

	public static function cancel_course_enroll( $course_id, $user_id ) {
		if ( empty( $course_id ) || empty( $user_id ) ) {
			return;
		}

		$enrolled  = self::is_enrolled( $course_id, $user_id );
		if ( $enrolled ) {
			global $wpdb;
			$wpdb->delete(
				$wpdb->posts,
				array(
					'post_type'   => 'academy_enrolled',
					'post_author' => $user_id,
					'post_parent' => $course_id
				)
			);

			$order_id = get_post_meta( $enrolled->ID, 'academy_enrolled_by_order_id', true );
			delete_post_meta( $enrolled->ID, 'academy_enrolled_by_order_id' );
			delete_post_meta( $enrolled->ID, 'academy_enrolled_by_product_id' );
			delete_post_meta( $order_id, 'is_academy_order_for_course' );
			delete_post_meta( $order_id, 'academy_order_for_course_id_' . $course_id );
			delete_user_meta( $user_id, 'academy_course_' . $course_id . '_completed_topics' );
		}
	}

	public static function delete_enrolled_courses( $course_id ) {
		if ( empty( $course_id ) ) {
			return;
		}
		global $wpdb;
		$enrolled_courses = $wpdb->get_results(
			$wpdb->prepare( "SELECT ID, post_author FROM {$wpdb->posts} WHERE post_type=%s AND post_parent=%d", 'academy_enrolled', $course_id )
		);
		if ( is_array( $enrolled_courses ) && count( $enrolled_courses ) ) {
			foreach ( $enrolled_courses as $enrolled_course ) {
				$wpdb->delete(
					$wpdb->posts,
					array(
						'post_type'   => 'academy_enrolled',
						'ID' => $enrolled_course->ID,
					)
				);
				$order_id = get_post_meta( $enrolled_course->ID, 'academy_enrolled_by_order_id', true );
				delete_post_meta( $enrolled_course->ID, 'academy_enrolled_by_order_id' );
				delete_post_meta( $enrolled_course->ID, 'academy_enrolled_by_product_id' );
				delete_post_meta( $order_id, 'is_academy_order_for_course' );
				delete_post_meta( $order_id, 'academy_order_for_course_id_' . $course_id );
				delete_user_meta( $enrolled_course->post_author, 'academy_course_' . $course_id . '_completed_topics' );
			}
		}
	}

	public static function update_enroll_status_by_course_ids( $status, $enroll_ids ) {
		global $wpdb;
		$enroll_ids = implode( ',', $enroll_ids );
		$status     = 'complete' === $status ? 'completed' : $status;
		$update     = $wpdb->query(
			$wpdb->prepare( "UPDATE {$wpdb->posts} SET post_status = %s WHERE ID IN (%s)", $status, $enroll_ids )
		);
		return $update;
	}

	public static function get_course_curriculum( $course_id, $complete_status = true ) {
		$user_id            = get_current_user_id();
		$is_public          = Helper::is_public_course( $course_id );
		$is_enrolled         = Helper::is_enrolled( $course_id, $user_id );
		$curriculums = get_post_meta( $course_id, 'academy_course_curriculum', true );
		$completed_topics = $complete_status ? json_decode( get_user_meta( $user_id, 'academy_course_' . $course_id . '_completed_topics', true ), true ) : [];

		$results    = [];
		if ( is_array( $curriculums ) ) {
			foreach ( $curriculums as $curriculum ) {
				$temp_topic = [];
				if ( is_array( $curriculum['topics'] ) ) {
					foreach ( $curriculum['topics'] as $topic ) {
						if ( 'quiz' === $topic['type'] && ! Helper::get_addon_active_status( 'quizzes' ) ) {
							continue;
						}
						if ( 'assignment' === $topic['type'] && ! \Academy\Helper::get_addon_active_status( 'assignments', true ) ) {
							continue;
						}
						if ( 'meeting' === $topic['type'] && ! \Academy\Helper::get_addon_active_status( 'meeting', true ) ) {
							continue;
						}
						if ( 'booking' === $topic['type'] && ! \Academy\Helper::get_addon_active_status( 'tutor-booking', true ) ) {
							continue;
						}

						$topic['is_completed'] = false;
						$topic['is_accessible'] = false;

						// if topic type is lesson then set duration
						if ( 'lesson' === $topic['type'] ) {
							$topic['slug']              = \Academy\Helper::get_lesson_slug( $topic['id'] );
							$topic['duration']          = \Academy\Helper::get_lesson_video_duration( $topic['id'] );
							$topic['is_accessible']     = \Academy\Helper::get_lesson_meta( $topic['id'], 'is_previewable' );
						} else {
							$topic['slug'] = basename( get_permalink( $topic['id'] ) );
						}

						if ( $complete_status ) {
							$topic['is_completed'] = ( isset( $completed_topics[ $topic['type'] ][ $topic['id'] ] ) ? $completed_topics[ $topic['type'] ][ $topic['id'] ] : '' );
						}

						if ( $is_public || $is_enrolled ) {
							$topic['is_accessible'] = true;
						}

						// Inner Item
						if ( isset( $topic['topics'] ) && is_array( $topic['topics'] ) ) {
							$temp_child_topic = [];
							foreach ( $topic['topics'] as $child_topic ) {
								if ( 'quiz' === $child_topic['type'] && ! Helper::get_addon_active_status( 'quizzes' ) ) {
									continue;
								}
								if ( 'assignment' === $child_topic['type'] && ! Helper::get_addon_active_status( 'assignments' ) ) {
									continue;
								}
								if ( 'meeting' === $child_topic['type'] && ! Helper::get_addon_active_status( 'meeting' ) ) {
									continue;
								}
								if ( 'booking' === $child_topic['type'] && ! \Academy\Helper::get_addon_active_status( 'tutor-booking', true ) ) {
									continue;
								}

								$child_topic['is_completed'] = false;

								// if topic type is lesson then set duration
								if ( 'lesson' === $child_topic['type'] ) {
									$child_topic['duration']               = Helper::get_lesson_video_duration( $child_topic['id'] );
									$child_topic['is_accessible']         = \Academy\Helper::get_lesson_meta( $child_topic['id'], 'is_previewable' );
								} else {
									$child_topic['slug'] = basename( get_permalink( $child_topic['id'] ) );
								}
								if ( $complete_status ) {
									$child_topic['is_completed'] = ( isset( $completed_topics[ $child_topic['type'] ][ $child_topic['id'] ] ) ? $completed_topics[ $child_topic['type'] ][ $child_topic['id'] ] : '' );
								}

								if ( $is_public || $is_enrolled ) {
									$child_topic['is_accessible'] = true;
								}

								$temp_child_topic[] = $child_topic;

							}//end foreach
							$topic['topics'] = $temp_child_topic;
						}//end if

						$temp_topic[]              = $topic;
					}//end foreach
				}//end if
				$curriculum['topics'] = $temp_topic;
				$results[]           = $curriculum;
			}//end foreach
		}//end if

		return apply_filters( 'academy/get_course_curriculums', $results, $course_id );
	}

	public static function is_public_course( $course_id ) {
		$course_type = self::get_course_type( $course_id );
		return apply_filters( 'academy/is_public_course', false, $course_type );
	}

	public static function is_favorite_course( $course_id ) {
		global $wpdb;
		$user_id = get_current_user_ID();
		$has_data = $wpdb->get_row( $wpdb->prepare( "SELECT * from {$wpdb->usermeta} WHERE user_id = %d AND meta_key = 'academy_course_favorite' AND meta_value = %d;", $user_id, $course_id ) );
		if ( $has_data ) {
			return true;
		}
		return false;
	}

	public static function get_topic_icon_class_name( $type ) {
		$icon_class = 'academy-icon academy-icon--';
		switch ( $type ) {
			case 'quiz':
				$icon_class .= 'quiz-alt';
				break;

			case 'assignment':
				$icon_class .= 'assignment';
				break;

			case 'meeting':
				$icon_class .= 'video';
				break;

			case 'booking':
				$icon_class .= 'add';
				break;

			default:
				$icon_class .= 'lesson-alt';
				break;
		}//end switch
		return $icon_class;
	}

	public static function get_course_difficulty_level( $course_id ) {
		$lavel = get_post_meta( $course_id, 'academy_course_difficulty_level', true );
		$levels = apply_filters('academy/difficulty_level', array(
			'beginner'    => __( 'Beginner', 'academy' ),
			'intermediate' => __( 'Intermediate', 'academy' ),
			'experts'     => __( 'Experts', 'academy' ),
		));

		if ( isset( $levels[ $lavel ] ) ) {
			return $levels[ $lavel ];
		}

		return '';
	}

	public static function get_the_course_thumbnail_url( $size = 'post-thumbnail' ) {
		$post_id           = get_the_ID();
		$post_thumbnail_id = (int) get_post_thumbnail_id( $post_id );
		if ( $post_thumbnail_id ) {
			$size = apply_filters( 'academy/course_thumbnail_size', $size, $post_id );
			return wp_get_attachment_image_url( $post_thumbnail_id, $size );
		}
		return ACADEMY_ASSETS_URI . '/images/thumbnail-placeholder.png';
	}

	public static function get_the_course_thumbnail_url_by_id( $course_id, $size = 'post-thumbnail' ) {
		$course_thumbnail_url = get_the_post_thumbnail_url( $course_id, $size );
		if ( $course_thumbnail_url ) {
			return $course_thumbnail_url;
		}
		return ACADEMY_ASSETS_URI . '/images/thumbnail-placeholder.png';
	}

	public static function prepare_category_results( $terms, $parent_id = 0 ) {
		$category = array();
		foreach ( $terms as $term ) {
			if ( $term->parent === $parent_id ) {
				$term->children = self::prepare_category_results( $terms, $term->term_id );
				$category[]     = $term;
			}
		}
		return $category;
	}

	public static function get_all_courses_category_lists() {
		$categories = get_terms(
			array(
				'taxonomy'   => 'academy_courses_category',
				'hide_empty' => true,
			)
		);
		return self::prepare_category_results( $categories );
	}

	public static function get_topic_play_link( $topic, $course_id = '' ) {
		$type = isset( $topic['type'] ) ? $topic['type'] : '';
		$id = isset( $topic['id'] ) ? $topic['id'] : '';
		$slug = isset( $topic['slug'] ) ? $topic['slug'] : '';
		if ( \Academy\Helper::get_settings( 'is_enabled_lessons_php_render' ) ) {
			$permalinks = \Academy\Helper::get_permalink_structure();
			if ( empty( $course_id ) ) {
				$course_id = ( false === get_the_ID() ) ? \Academy\Helper::get_the_current_course_id() : get_the_ID();
			}
			$course_name = get_post( $course_id )->post_name;
			$course_rewrite_slug = str_replace( '/', '', $permalinks['course_rewrite_slug'] );
			$url = home_url( "/{$course_rewrite_slug}/{$course_name}/{$type}/{$slug}" );
			return add_query_arg( array(), $url );
		}
		return add_query_arg( array( 'source' => "curriculums#/$type/$id" ), get_the_permalink() );
	}

	public static function get_course_curriculums_number_of_counts( $course_id ) {
		$curriculum_counts = [
			'total_lessons' => 0,
			'total_assignments' => 0,
			'total_quizzes' => 0,
			'total_zoom_meetings' => 0,
			'total_tutor_bookings' => 0,
		];

		$course_topic = get_post_meta( $course_id, 'academy_course_curriculum', true );

		if ( is_array( $course_topic ) ) {
			foreach ( $course_topic as $topic ) {
				if ( isset( $topic['topics'] ) && is_array( $topic['topics'] ) ) {
					foreach ( $topic['topics'] as $lesson ) {
						if ( isset( $lesson['type'] ) ) {
							switch ( $lesson['type'] ) {
								case 'lesson':
									$curriculum_counts['total_lessons']++;
									break;
								case 'assignment':
									$curriculum_counts['total_assignments']++;
									break;
								case 'quiz':
									$curriculum_counts['total_quizzes']++;
									break;
								case 'meeting':
									$curriculum_counts['total_zoom_meetings']++;
									break;
								case 'booking':
									$curriculum_counts['total_tutor_bookings']++;
									break;
								case 'sub-curriculum':
									foreach ( $lesson['topics'] as $sub_topic ) {
										switch ( $sub_topic['type'] ) {
											case 'lesson':
												$curriculum_counts['total_lessons']++;
												break;
											case 'assignment':
												$curriculum_counts['total_assignments']++;
												break;
											case 'quiz':
												$curriculum_counts['total_quizzes']++;
												break;
											case 'meeting':
												$curriculum_counts['total_zoom_meetings']++;
												break;
											case 'booking':
												$curriculum_counts['total_tutor_bookings']++;
												break;
											default:
												break;
										}
									}//end foreach
								default:
									break;
							}//end switch
						}//end if
					}//end foreach
				}//end if
			}//end foreach
		}//end if
		$curriculum_counts['total_topics'] = array_sum( $curriculum_counts );
		return $curriculum_counts;
	}

	public static function get_course_download_id( $course_id ) {
		$download_id = (int) get_post_meta( $course_id, 'academy_course_download_id', true );
		return apply_filters( 'academy/course/get_course_download_id', $download_id, $course_id );
	}
	public static function get_the_current_course_id() {
		$course_name = get_query_var( 'course_name' );
		$args = array(
			'name'        => $course_name,
			'post_type'   => 'academy_courses',
			'post_status' => 'publish',
			'numberposts' => 1
		);
		$course = current( get_posts( $args ) );
		if ( $course ) {
			return $course->ID;
		}
		return false;
	}

	public static function get_course_announcements_by_course_id( $course_id ) {
		global $wpdb;

		$announcement_ids = $wpdb->get_col($wpdb->prepare(
			"SELECT post_id 
				FROM {$wpdb->postmeta} 
					WHERE meta_key = %s 
					AND meta_value LIKE %s",
			'academy_announcements_course_ids',
			'%"value";i:' . $course_id . ';%'
		));

		if ( ! empty( $announcement_ids ) ) {
			$args = array(
				'post_type'         => 'academy_announcement',
				'post_status'       => 'publish',
				'post__in'          => $announcement_ids,
				'posts_per_page'    => -1,
			);
			$announcements = get_posts( $args );
			return $announcements;
		}
		return false;
	}

	public static function get_course_qas( $course_id ) {
		$args = array(
			'status' => 'any',
			'post_id' => $course_id,
			'type' => 'academy_qa',
		);

		$comments = get_comments( $args );
		foreach ( $comments as $comment ) {
			$meta = get_comment_meta( $comment->comment_ID );
			$comment->title = $meta['academy_question_title'] ? current( $meta['academy_question_title'] ) : '';
		}

		return $comments;
	}

	public static function is_course_curriculum( $course_id, $topic_id, $topic_type ): bool {
		$course_curriculum = get_post_meta( $course_id, 'academy_course_curriculum', true );

		foreach ( $course_curriculum as $curriculum ) {
			foreach ( $curriculum['topics'] as $topic ) {
				if ( 'sub-curriculum' === $topic['type'] ) {
					foreach ( $topic['topics'] as $sub_topic ) {
						if ( (int) $topic_id === (int) $sub_topic['id'] && $topic_type === $sub_topic['type'] ) {
							return true;
						}
					}
				}
				if ( (int) $topic_id === (int) $topic['id'] && $topic_type === $topic['type'] ) {
					return true;
				}
			}
		}

		return false;
	}
}
