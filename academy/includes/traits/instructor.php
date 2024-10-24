<?php
namespace Academy\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Instructor {

	public static function get_author() {
		global $wp_query;
		$user = get_user_by( 'login', $wp_query->query['author_name'] );
		return $user;
	}

	public static function get_the_author_id() {
		$user = self::get_author();
		if ( ! empty( $user ) ) {
			return $user->ID;
		}
		return null;
	}
	public static function get_the_author_name( $user_id = null ) {
		if ( null === $user_id ) {
			return;
		}
		$first_name = get_the_author_meta( 'first_name', $user_id );
		$last_name  = get_the_author_meta( 'last_name', $user_id );
		$nickname   = get_the_author_meta( 'nickname', $user_id );
		if ( $first_name || $last_name ) {
			return $first_name . ' ' . $last_name;
		}
		return $nickname;
	}
	public static function get_the_author_info( $user_id = null ) {
		if ( null === $user_id ) {
			return;
		}
		$profile_bio = get_the_author_meta( 'academy_profile_bio', $user_id );
		if ( $profile_bio ) {
			return $profile_bio;
		}
		return get_the_author_meta( 'description', $user_id );
	}
	public static function get_the_author_thumbnail_url( $user_id = null ) {
		if ( null === $user_id ) {
			return;
		}
		$profile_photo = get_the_author_meta( 'academy_profile_photo', $user_id );
		if ( $profile_photo ) {
			return $profile_photo;
		}
		return apply_filters( 'academy/get_author_thumnail_url', get_avatar_url( $user_id, [ 'size' => 250 ] ) );
	}
	public static function get_all_instructors( $offset = 0, $per_page = 10, $search_keyword = '' ) {
		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT ID, display_name, user_nicename, user_email
			FROM {$wpdb->users}
			INNER JOIN {$wpdb->usermeta}
			ON ({$wpdb->users}.ID = {$wpdb->usermeta}.user_id)
			WHERE {$wpdb->usermeta}.meta_key = %s",
		'is_academy_instructor');

		if ( ! empty( $search_keyword ) ) {
			$wild = '%';
			$like = $wild . $wpdb->esc_like( $search_keyword ) . $wild;
			$query .= $wpdb->prepare( 'AND (display_name LIKE %s OR user_nicename LIKE %s OR user_email LIKE %s)', $like, $like, $like );
		}
		$query .= $wpdb->prepare( ' ORDER BY ID DESC LIMIT %d, %d;', $offset, $per_page );
		// phpcs:ignore 
		$results = $wpdb->get_results( $query );
		return $results;
	}
	public static function get_instructor( $ID ) {
		global $wpdb;
		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT ID, display_name, user_nicename, user_email
			FROM {$wpdb->users}
			WHERE ID = %d",
			$ID,
		) );
		return current( $results );
	}
	public static function get_all_instructors_by_status( $instructor_status ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, display_name, user_nicename, user_email
			FROM 	{$wpdb->users} 
				INNER JOIN {$wpdb->usermeta} 
					ON ( {$wpdb->users}.ID = {$wpdb->usermeta}.user_id )
			WHERE   {$wpdb->usermeta}.meta_key = %s AND 
                    {$wpdb->usermeta}.meta_value = %s;",
				'academy_instructor_status',
				$instructor_status
			)
		);
		if ( count( $results ) ) {
			return $results;
		}
		return false;
	}
	public static function get_all_approved_instructors() {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, display_name, user_nicename, user_email
			FROM 	{$wpdb->users} 
				INNER JOIN {$wpdb->usermeta} 
					ON ( {$wpdb->users}.ID = {$wpdb->usermeta}.user_id )
			WHERE   {$wpdb->usermeta}.meta_key = %s AND 
                    {$wpdb->usermeta}.meta_value = %s;",
				'academy_instructor_status',
				'approved'
			)
		);
		if ( count( $results ) ) {
			return $results;
		}
		return false;
	}
	public static function get_current_instructor() {
		global $wpdb;
		$user_id = get_current_user_id();
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, display_name, user_nicename, user_email
			FROM 	{$wpdb->users} 
                INNER JOIN {$wpdb->usermeta} 
						ON ( {$wpdb->users}.ID = {$wpdb->usermeta}.user_id )
			WHERE   {$wpdb->users}.ID = %d AND {$wpdb->usermeta}.meta_key = %s;",
				$user_id,
				'is_academy_instructor'
			)
		);
		if ( count( $results ) ) {
			return $results;
		}
		return false;
	}
	public static function prepare_all_instructors_response( $instructors ) {
		$instructor_fields = self::get_form_builder_fields( 'instructor' );
		$results = [];
		if ( is_array( $instructors ) ) {
			foreach ( $instructors as $instructor ) {
				$courseIds                     = \Academy\Helper::get_course_ids_by_instructor_id( $instructor->ID );
				$instructor->ID                = $instructor->ID;
				$instructor->first_name        = get_user_meta( $instructor->ID, 'first_name', true );
				$instructor->last_name         = get_user_meta( $instructor->ID, 'last_name', true );
				$instructor->instructor_status = get_user_meta( $instructor->ID, 'academy_instructor_status', true );
				$instructor->total_courses     = is_array( $courseIds ) ? count( $courseIds ) : 0;
				$instructor->permalink         = get_edit_user_link( $instructor->ID );
				$instructor->bio = get_user_meta( $instructor->ID, 'academy_profile_bio', true );
				$instructor->desigination = get_user_meta( $instructor->ID, 'academy_profile_designation', true );
				$instructor->website = get_user_meta( $instructor->ID, 'academy_website_url', true );
				$instructor->phone = get_user_meta( $instructor->ID, 'academy_phone_number', true );
				$instructor->github = get_user_meta( $instructor->ID, 'academy_github_url', true );
				$instructor->facebook = get_user_meta( $instructor->ID, 'academy_facebook_url', true );
				$instructor->twitter = get_user_meta( $instructor->ID, 'academy_twitter_url', true );
				$instructor->linkedin = get_user_meta( $instructor->ID, 'academy_linkedin_url', true );

				$meta = \Academy\Helper::prepare_user_meta_data( $instructor_fields, $instructor->ID );
				if ( count( $meta ) ) {
					$instructor->meta = $meta;
				}

				$results[]  = $instructor;
			}//end foreach
		}//end if

		return $results;
	}

	public static function set_instructor_role( $user_id ) {
		update_user_meta( $user_id, 'is_academy_instructor', \Academy\Helper::get_time() );
		update_user_meta( $user_id, 'academy_instructor_status', 'approved' );
		update_user_meta( $user_id, 'academy_instructor_approved', \Academy\Helper::get_time() );
		$instructor = new \WP_User( $user_id );
		// Check if user has 'academy_student' role and remove it
		if ( in_array( 'academy_student', (array) $instructor->roles, true ) ) {
			$instructor->remove_role( 'academy_student' );
		}
		if ( in_array( 'subscriber', $instructor->roles, true ) ) {
			$instructor->remove_role( 'subscriber' );
		}
		$instructor->add_role( 'academy_instructor' );
	}
	public static function pending_instructor_role( $user_id ) {
		update_user_meta( $user_id, 'academy_instructor_status', 'pending' );
		delete_user_meta( $user_id, 'academy_instructor_approved' );
		$instructor = new \WP_User( $user_id );
		$instructor->add_role( 'academy_student' );
		$instructor->remove_role( 'academy_instructor' );
	}
	public static function remove_instructor_role( $user_id ) {
		delete_user_meta( $user_id, 'is_academy_instructor' );
		delete_user_meta( $user_id, 'academy_instructor_status' );
		delete_user_meta( $user_id, 'academy_instructor_approved' );
		$instructor = new \WP_User( $user_id );
		$instructor->remove_role( 'academy_instructor' );
	}

	public static function get_course_instructor( $course_id ) {
		global $wpdb;
		$instructor = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * 
                FROM {$wpdb->usermeta} 
                WHERE meta_key = %s
				AND meta_value = %d",
				'academy_instructor_course_id', $course_id
			)
		);
		return $instructor;
	}

	public static function get_all_course_by_instructor( $instructor_id ) {
		global $wpdb;
		$course_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT count(umeta_id)
				FROM {$wpdb->usermeta}
				WHERE user_id = %d
				AND meta_key = %s ",
				$instructor_id, 'academy_instructor_course_id'
			)
		);
		return $course_count;
	}

	public static function insert_instructor( $email, $first_name = '', $last_name = '', $username = '', $password = '' ) {
		$error = [];
		// check email
		if ( empty( $email ) || ! is_email( $email ) ) {
			$error[] = __( 'Email is missing or Invalid.', 'academy' );
		} elseif ( email_exists( $email ) ) {
			$error[] = __( 'The provided email is already registered with other account. Please login or reset password or use another email.', 'academy' );
		}

		// check username
		if ( empty( $username ) ) {
			$username = \Academy\Helper::generate_unique_username_from_email( $email );
		} elseif ( username_exists( $username ) ) {
			$error[] = __( 'Invalid username provided or the username already registered.', 'academy' );
		}

		if ( empty( $password ) ) {
			$password = wp_generate_password();
		}

		if ( count( $error ) ) {
			return $error;
		}

		$user_data = array(
			'user_login' => $username,
			'user_email' => $email,
			'first_name' => $first_name,
			'last_name'  => $last_name,
			'user_pass'  => $password,
			'role'       => 'academy_instructor'
		);
		do_action( 'academy/admin/before_register_instructor', $user_data );

		$user_id = wp_insert_user( $user_data );
		if ( ! is_wp_error( $user_id ) ) {
			update_user_meta( $user_id, 'is_academy_instructor', \Academy\Helper::get_time() );
			update_user_meta( $user_id, 'academy_instructor_status', 'approved' );
			if ( apply_filters( 'academy/is_allow_new_instructor_notification', true ) ) {
				wp_new_user_notification( $user_id, null, 'both' );
			}
			do_action( 'academy/admin/after_register_instructor', $user_id );
		}
		return $user_id;
	}

	public static function save_instructor_earnings( $course, $order, $order_id ) {
		$course_id    = $course->post_id;
		$user_id      = \Academy\Helper::get_user_id_from_course_id( $course_id );

		if ( 'woocommerce' === \Academy\Helper::get_settings( 'monetize_engine' ) ) {
			$order_status = \Academy\Helper::get_order_status_by_id( $order_id );
			$total_price              = $order->get_total();
		} else {
			$order_status = 'completed';
			$total_price = $order['subtotal'];
		}

		if ( self::is_exists_user_earning_by_order( $course_id, $order_id, $user_id ) ) {
			return;
		}

		$fees_deduct_data         = array();
		$is_enabled_fee_deduction = (bool) \Academy\Helper::get_settings( 'is_enabled_fee_deduction' );
		$course_price_grand_total = $total_price;
		if ( $is_enabled_fee_deduction ) {
			$fees_name   = \Academy\Helper::get_settings( 'fee_deduction_name' );
			$fees_amount = \Academy\Helper::get_settings( 'fee_deduction_amount' );
			$fees_type   = \Academy\Helper::get_settings( 'fee_deduction_type' );

			if ( $fees_amount > 0 ) {
				if ( 'percent' === $fees_type ) {
					$fees_amount = ( $total_price * $fees_amount ) / 100;
				}
				$course_price_grand_total = $total_price - $fees_amount;
			}

			$fees_deduct_data = array(
				'deduct_fees_amount' => $fees_amount,
				'deduct_fees_name'   => $fees_name,
				'deduct_fees_type'   => $fees_type,
			);
		}

		$instructor_rate = \Academy\Helper::get_settings( 'instructor_commission_percentage' );
		$admin_rate      = \Academy\Helper::get_settings( 'admin_commission_percentage' );

		$instructor_amount = 0;
		if ( $instructor_rate > 0 ) {
			$instructor_amount = ( $course_price_grand_total * $instructor_rate ) / 100;
		}

		$admin_amount = 0;
		if ( $admin_rate > 0 ) {
			$admin_amount = ( $course_price_grand_total * $admin_rate ) / 100;
		}

		$commission_type = 'percent';

		$earning_data = array(
			'user_id'                  => $user_id,
			'course_id'                => $course_id,
			'order_id'                 => $order_id,
			'order_status'             => $order_status,
			'course_price_total'       => $total_price,
			'course_price_grand_total' => $course_price_grand_total,
			'instructor_amount'        => $instructor_amount,
			'instructor_rate'          => $instructor_rate,
			'admin_amount'             => $admin_amount,
			'admin_rate'               => $admin_rate,
			'commission_type'          => $commission_type,
		);
		$data         = apply_filters( 'academy/integration/woocommerce/insert_earning_args', array_merge( $earning_data, $fees_deduct_data ) );
		self::insert_earning( $data );
	}
}
