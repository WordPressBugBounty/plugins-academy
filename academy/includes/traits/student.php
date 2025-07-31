<?php
namespace Academy\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Student {
	public static function get_all_students( $offset = 0, $per_page = 10, $search_keyword = '' ) {
		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT ID, display_name, user_nicename, user_email, user_registered
			FROM {$wpdb->users}
			INNER JOIN {$wpdb->usermeta}
			ON ({$wpdb->users}.ID = {$wpdb->usermeta}.user_id)
			WHERE {$wpdb->usermeta}.meta_key = %s",
		'is_academy_student');

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

	public static function prepare_get_all_students_response( $students ) {
		if ( ! is_array( $students ) || empty( $students ) ) {
			return [];
		}

		$student_fields = self::get_form_builder_fields( 'student' );
		$results = [];

		foreach ( $students as $student ) {
			if ( is_object( $student ) ) {
				$student_id = $student->ID;
				// Batch user meta fetch
				$all_meta = get_user_meta( $student_id );

				// Assign common meta fields
				$student->phone        = $all_meta['academy_phone_number'][0] ?? '';
				$student->bio          = $all_meta['academy_profile_bio'][0] ?? '';
				$student->desigination = $all_meta['academy_profile_designation'][0] ?? '';
				$student->website      = $all_meta['academy_website_url'][0] ?? '';
				$student->github       = $all_meta['academy_github_url'][0] ?? '';
				$student->facebook     = $all_meta['academy_facebook_url'][0] ?? '';
				$student->twitter      = $all_meta['academy_twitter_url'][0] ?? '';
				$student->linkedin     = $all_meta['academy_linkedin_url'][0] ?? '';

				// Optional meta builder
				$meta = \Academy\Helper::prepare_user_meta_data( $student_fields, $student_id );
				if ( ! empty( $meta ) ) {
					$student->meta = $meta;
				}
			} else {
				$student_id = (int) $student;
				$student_details = get_userdata( $student_id );
				$student = (object) [ 'ID' => $student ];
				$student->display_name = $student_details->display_name;
				$student->registration_date = date( 'F j, Y', strtotime( $student_details->user_registered ) );
			}//end if
			// Course info
			$completed_ids = self::get_completed_courses_ids_by_user( $student_id );
			$enrolled_info = self::get_total_enrolled_courses_info_by_student_id( $student_id );

			if ( ! empty( $enrolled_info ) && is_array( $enrolled_info ) ) {
				$course_ids = array_unique( array_column( $enrolled_info, 'post_parent' ) );

				// Fetch all course titles/permalinks in bulk
				$courses = get_posts( [
					'post__in'       => $course_ids,
					'post_type'      => 'academy_courses',
					'post_status'    => [ 'publish', 'private' ],
					'posts_per_page' => -1,
				] );

				$course_data = [];
				foreach ( $courses as $course ) {
					$course_data[ $course->ID ] = [
						'title'     => html_entity_decode( get_the_title( $course->ID ) ),
						'permalink' => get_the_permalink( $course->ID ),
					];
				}

				$enrolled_courses = [];
				foreach ( $enrolled_info as $info ) {
					$cid = intval( $info['post_parent'] );
					if ( isset( $course_data[ $cid ] ) ) {
						$status = in_array( $cid, $completed_ids ) ? 'completed' : ( 'completed' === $info['post_status'] ? 'approved' : 'pending' );
						$enrolled_courses[] = [
							'ID'        => $cid,
							'enrolled_id' => intval( $info['ID'] ),
							'title'     => $course_data[ $cid ]['title'],
							'permalink' => $course_data[ $cid ]['permalink'],
							'status'    => $status,
						];
					}
				}

				$student->enrolled_courses = $enrolled_courses;
			}//end if

			$results[] = $student;
		}//end foreach

		return $results;
	}

	public static function set_student_role( $user_id ) {
		update_user_meta( $user_id, 'is_academy_student', \Academy\Helper::get_time() );
		$instructor = new \WP_User( $user_id );
		$instructor->add_role( 'academy_student' );
	}

	public static function student_course_taken( $id ) {
		global $wpdb;
		$course = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT count(post_parent)
				FROM {$wpdb->posts}
				WHERE post_author = %d
				AND post_type = %s",
				$id, 'academy_enrolled'
			)
		);
		return $course;
	}

	public static function insert_student( $email, $first_name = '', $last_name = '', $username = '', $password = '' ) {
		$error = [];
		// check email
		if ( empty( $email ) || ! is_email( $email ) ) {
			$error[] = __( 'Email is missing or Invalid.', 'academy' );
		} elseif ( email_exists( $email ) ) {
			$exists_user_id = email_exists( $email );
			if ( get_user_meta( $exists_user_id, 'is_academy_student' ) ) {
				$error[] = __( 'The provided email is already registered with another account. Please login or reset password or use another email.', 'academy' );
			} else {
				$user = get_userdata( $exists_user_id );
				$user->add_role( 'academy_student' );
			}
		}

		// check username
		if ( empty( $username ) ) {
			$username = \Academy\Helper::generate_unique_username_from_email( $email );
		} elseif ( username_exists( $username ) ) {
			$exists_user_id = username_exists( $username );
			if ( get_user_meta( $exists_user_id, 'is_academy_student' ) ) {
				$error[] = __( 'Invalid username provided or the username already registered as an academy student.', 'academy' );
			} else {
				$user = get_userdata( $exists_user_id );
				$user->add_role( 'academy_student' );
			}
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
			'role'       => 'academy_student'
		);
		do_action( 'academy/admin/before_register_student', $user_data );
		if ( $exists_user_id ) {
			$user_data['ID'] = $exists_user_id;
		}

		$user_id = empty( $exists_user_id ) ? wp_insert_user( $user_data ) : wp_update_user( $user_data );
		if ( ! is_wp_error( $user_id ) ) {
			update_user_meta( $user_id, 'is_academy_student', \Academy\Helper::get_time() );
			if ( apply_filters( 'academy/is_allow_new_student_notification', true ) ) {
				wp_new_user_notification( $user_id, null, 'both' );
			}
			do_action( 'academy/admin/after_register_student', $user_id );
		}
		return $user_id;
	}

	public static function remove_student( $student_id ) {
		if ( ! $student_id ) {
			return false;
		}
		$user = get_user_by( 'ID', $student_id );
		if ( in_array( 'academy_student', $user->roles, true ) ) {
			$user->add_role( 'subscriber' );
		}
		return delete_user_meta( $student_id, 'is_academy_student' );
	}

	public static function get_total_number_of_completed_course_topics_by_course_and_student_id( $course_id, $student_id = 0 ) {

		if ( ! $student_id ) {
			$student_id = get_current_user_id();
		}

		$count      = 0;
		$completed_topics = json_decode( get_user_meta( $student_id, 'academy_course_' . $course_id . '_completed_topics', true ), true );
		if ( is_array( $completed_topics ) && count( $completed_topics ) ) {
			foreach ( $completed_topics as $topics_item ) {
				if ( is_array( $topics_item ) ) {
					$count += count( $topics_item );
				}
			}
		}
		return apply_filters(
			"academy/topic_completed_by_student_id_{$student_id}",
			(int) $count,
			$course_id,
			$student_id
		);
	}
	public static function get_completed_course_topics_by_course_and_student_id( $course_id, $student_id = 0 ) {
		if ( ! $student_id ) {
			$student_id = get_current_user_id();
		}
		return json_decode( get_user_meta( $student_id, 'academy_course_' . $course_id . '_completed_topics', true ), true );
	}
	public static function prepare_analytics_for_user( $student_id, $course_id ) {
		$enrolled = \Academy\Helper::is_enrolled( $course_id, $student_id );
		$course_curriculums = \Academy\Helper::get_course_curriculums_number_of_counts( $course_id );
		$total_completed_topics = \Academy\Helper::get_total_number_of_completed_course_topics_by_course_and_student_id( $course_id, $student_id );
		$percentage              = \Academy\Helper::calculate_percentage( $course_curriculums['total_topics'], $total_completed_topics );
		$response = [
			'title' => html_entity_decode( get_the_title() ),
			'date'  => $enrolled->post_date,
			'number_of_lessons'          => $course_curriculums['total_lessons'],
			'number_of_quizzes'          => $course_curriculums['total_quizzes'],
			'number_of_assignments'      => $course_curriculums['total_assignments'],
			'number_of_tutor_bookings'   => $course_curriculums['total_tutor_bookings'],
			'number_of_zoom_meetings'    => $course_curriculums['total_zoom_meetings'],
			'completed_topics'           => \Academy\Helper::get_completed_course_topics_by_course_and_student_id( $course_id, $student_id ),
			'progress_percentage'        => $percentage . '%'
		];
		return $response;
	}

	public static function get_search_students( $search_keyword ) {
		global $wpdb;

		$wild = '%';
		$like = $wild . $wpdb->esc_like( $search_keyword ) . $wild;

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT ID, user_login, display_name, user_nicename, user_email
			FROM {$wpdb->users}
			WHERE user_login LIKE %s OR display_name LIKE %s OR user_nicename LIKE %s OR user_email LIKE %s ",
			$like, $like, $like, $like
		) );

		return $results;
	}

	public static function get_percentage_of_completed_topics_by_student_and_course_id( $student_id, $course_id ) {
		$course_curriculums = \Academy\Helper::get_course_curriculums_number_of_counts( $course_id );
		$total_completed_topics = \Academy\Helper::get_total_number_of_completed_course_topics_by_course_and_student_id( $course_id, $student_id );

		return \Academy\Helper::calculate_percentage( $course_curriculums['total_topics'], $total_completed_topics );
	}
}
