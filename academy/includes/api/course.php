<?php
namespace Academy\API;

use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Course extends \WP_REST_Controller {

	public static function init() {
		$self = new self();
		add_action( 'rest_api_init', array( $self, 'register_routes' ) );
		add_filter( 'rest_prepare_academy_courses', array( $self, 'add_author_name_to_rest_response' ), 10, 3 );
		add_filter( 'rest_prepare_academy_courses_category', array( $self, 'taxonomy_decode_special_character' ), 10, 3 );
		add_filter( 'rest_prepare_academy_courses_tag', array( $self, 'taxonomy_decode_special_character' ), 10, 3 );
	}

	public function register_routes() {
		$this->namespace = ACADEMY_PLUGIN_SLUG . '/v1';
		$obj             = get_post_type_object( 'academy_courses' );
		$this->rest_base = ! empty( $obj->rest_base ) ? $obj->rest_base : $obj->name;

		$schema        = $this->get_item_schema();
		$get_item_args = array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);
		if ( isset( $schema['properties']['password'] ) ) {
			$get_item_args['password'] = array(
				'description' => esc_html__( 'The password for the post if it is password protected.', 'academy' ),
				'type'        => 'string',
			);
		}

		// 01 — Topics
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/topics',
			array(
				'args'   => array(
					'id' => array(
						'description' => esc_html__( 'Unique identifier for the object.', 'academy' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item_topics' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => $get_item_args,
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		// 02 — Announcements
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/announcements',
			array(
				'args'   => array(
					'id' => array(
						'description' => esc_html__( 'Unique identifier for the object.', 'academy' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item_announcements' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => $get_item_args,
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		// 03 — Enroll
		register_rest_route(
			$this->namespace,
			'/enroll',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'enroll_course' ),
				'permission_callback' => array( $this, 'enroll_permission' ),
				'args'                => array(
					'course_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// 04 — Complete course
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/complete',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_course_completion' ),
					'permission_callback' => array( $this, 'course_complete_before' ),
					'args'                => array(
						'id' => array(
							'description'       => esc_html__( 'Unique identifier for the object.', 'academy' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
							'validate_callback' => function( $param ) {
								return $param > 0;
							},
						),
					),
				),
			)
		);

		// 05 — Complete / incomplete topic
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/complete-topic',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_item_complete_topic' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'id' => array(
							'description'       => esc_html__( 'Course ID.', 'academy' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'topic_type' => array(
							'description'       => esc_html__( 'Topic type.', 'academy' ),
							'type'              => 'string',
							'required'          => true,
							'validate_callback' => function( $param ) {
								return in_array( $param, array( 'lesson', 'quiz', 'assignment' ), true );
							},
						),
						'topic_id' => array(
							'description'       => esc_html__( 'Topic ID.', 'academy' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// 06 — ratings
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/review_ratings',
			array(
				'args'   => array(
					'id' => array(
						'description'       => esc_html__( 'Unique identifier for the object.', 'academy' ),
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item_review_ratings' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'page' => array(
							'description'       => esc_html__( 'Current page of the collection.', 'academy' ),
							'type'              => 'integer',
							'default'           => 1,
							'sanitize_callback' => 'absint',
						),
						'per_page' => array(
							'description'       => esc_html__( 'Maximum number of reviews to be returned in result set.', 'academy' ),
							'type'              => 'integer',
							'default'           => 10,
							'sanitize_callback' => 'absint',
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item_review_rating' ),
					'permission_callback' => array( $this, 'create_review_rating_permission_check' ),
					'args'                => array(
						'rating' => array(
							'description'       => esc_html__( 'Course rating from 1 to 5.', 'academy' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
							'validate_callback' => function( $value ) {
								$value = absint( $value );
								return $value >= 1 && $value <= 5;
							},
						),
						'review' => array(
							'description'       => esc_html__( 'Course review text.', 'academy' ),
							'type'              => 'string',
							'required'          => false,
							'default'           => '',
							'sanitize_callback' => 'wp_kses_post',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
		// 07 wishlist
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/wishlist',
			array(
				'args'   => array(
					'id' => array(
						'description'       => esc_html__( 'Unique identifier for the object.', 'academy' ),
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'toggle_item_wishlist' ),
					'permission_callback' => array( $this, 'wishlist_permission_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
		// 08 — My courses
		register_rest_route(
			$this->namespace,
			'/my-courses',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_my_courses' ),
					'permission_callback' => array( $this, 'my_course_check_permission' ),
					'args'                => array(
						'status' => array(
							'description'       => esc_html__( 'Filter enrollments by status.', 'academy' ),
							'type'              => 'string',
							'default'           => 'all',
							'sanitize_callback' => 'sanitize_key',
							'validate_callback' => function( $value ) {
								return in_array( $value, array( 'all', 'enrolled', 'in_progress', 'completed' ), true );
							},
						),
						'recommended_limit' => array(
							'type'              => 'integer',
							'default'           => 6,
							'sanitize_callback' => 'absint',
						),
						'page' => array(
							'type'              => 'integer',
							'default'           => 1,
							'sanitize_callback' => 'absint',
						),
						'per_page' => array(
							'type'              => 'integer',
							'default'           => 10,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);
	}

	// =========================================================================
	// Permission Callbacks
	// =========================================================================

	public function enroll_permission( $request ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				esc_html__( 'You must be logged in.', 'academy' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}

	public function my_course_check_permission( $request ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				esc_html__( 'You must be logged in to access anythings.', 'academy' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}

	public function course_complete_before( $request ) {
		$user_id   = get_current_user_id();
		$course_id = absint( $request['id'] );

		if ( ! $user_id ) {
			return new \WP_Error(
				'rest_not_logged_in',
				esc_html__( 'You must be logged in.', 'academy' ),
				array( 'status' => 401 )
			);
		}

		if ( ! $course_id ) {
			return new \WP_Error(
				'invalid_course',
				esc_html__( 'Invalid course ID.', 'academy' ),
				array( 'status' => 400 )
			);
		}

		if ( ! \Academy\Helper::is_enrolled( $course_id, $user_id ) ) {
			return new \WP_Error(
				'not_enrolled',
				esc_html__( 'You are not enrolled in this course.', 'academy' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	public function check_permission( $request ) {
		$course_id = absint( $request->get_param( 'id' ) );
		// public course
		if ( \Academy\Helper::is_public_course( $course_id ) ) {
			return true;
		}
		
		// logged in 
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				esc_html__( 'You must be logged in to access anythings.', 'academy' ),
				array( 'status' => 401 )
			);
		}

		$user_id   = get_current_user_id();

		if ( ! $course_id ) {
			return new \WP_Error(
				'invalid_course',
				esc_html__( 'Invalid course ID.', 'academy' ),
				array( 'status' => 400 )
			);
		}

		return $this->check_course_access( $course_id, $user_id );
	}

	public function course_exists_permission_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error( 'rest_forbidden', esc_html__( 'You must be logged in to access anything.', 'academy' ), array( 'status' => 401 ) );
		}

		// Capability checks
		$can_manage   = current_user_can( 'administrator' ) || current_user_can( 'read_academy_course' );

		 // Final permission decision
		$course_id = absint( $request->get_param( 'id' ) );
		if ( ! $this->get_course_post( $course_id ) ) {
			return new \WP_Error(
				'rest_course_not_found',
				esc_html__( 'Course not found.', 'academy' ),
				array( 'status' => 404 )
			);
		}

		if ( $can_manage ) {
			return true;
		}

		return new \WP_Error(
			'rest_forbidden',
			esc_html__( 'You do not have permission to access this course.', 'academy' ),
			array( 'status' => 403 )
		);
	}

	public function wishlist_permission_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				esc_html__( 'You must be logged in.', 'academy' ),
				array( 'status' => 401 )
			);
		}

		$course_id = absint( $request->get_param( 'id' ) );
		if ( ! $this->get_course_post( $course_id ) ) {
			return new \WP_Error(
				'rest_course_not_found',
				esc_html__( 'Course not found.', 'academy' ),
				array( 'status' => 404 )
			);
		}

		return true;
	}

	public function create_review_rating_permission_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				esc_html__( 'You must be logged in to submit a review.', 'academy' ),
				array( 'status' => 401 )
			);
		}

		$course_id = absint( $request->get_param( 'id' ) );
		if ( ! $this->get_course_post( $course_id ) ) {
			return new \WP_Error(
				'rest_course_not_found',
				esc_html__( 'Course not found.', 'academy' ),
				array( 'status' => 404 )
			);
		}

		$settings_permission = $this->review_settings_permission_check( $request );
		if ( is_wp_error( $settings_permission ) ) {
			return $settings_permission;
		}

		if ( current_user_can( 'administrator' ) ) {
			return true;
		}

		$user_id = get_current_user_id();
		if ( \Academy\Helper::is_enrolled( $course_id, $user_id ) && \Academy\Helper::is_completed_course( $course_id, $user_id ) ) {
			return true;
		}

		return new \WP_Error(
			'rest_forbidden',
			esc_html__( 'You must enroll in and complete this course before submitting a review.', 'academy' ),
			array( 'status' => 403 )
		);
	}

	private function review_settings_permission_check( $request ) {
		$course_id = absint( $request->get_param( 'id' ) );

		if ( ! (bool) \Academy\Helper::get_settings( 'is_enabled_course_review', true ) || (bool) get_post_meta( $course_id, 'academy_is_disabled_course_review', true ) ) {
			return new \WP_Error(
				'rest_course_review_disabled',
				esc_html__( 'Course reviews are disabled.', 'academy' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	// =========================================================================
	// Route Callbacks
	// =========================================================================

	public function get_item_topics( $request ) {
		$course_id   = $request->get_param( 'id' );
		$curriculums = \Academy\Helper::get_course_curriculum( $course_id );
		return apply_filters( 'academy/api/course/get_item_curriculums', $curriculums, $course_id );
	}

	public function get_item_announcements( $request ) {
		global $wpdb;
		$course_id = absint( $request['id'] );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$announcement_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id
				FROM {$wpdb->postmeta}
				WHERE meta_key = %s
				AND meta_value LIKE %s",
				'academy_announcements_course_ids',
				'%"value";i:' . $course_id . ';%'
			)
		);

		if ( empty( $announcement_ids ) ) {
			return array();
		}

		return get_posts(
			array(
				'post_type'      => 'academy_announcement',
				'post_status'    => 'publish',
				'post__in'       => $announcement_ids,
				'posts_per_page' => -1,
			)
		);
	}

	public function get_item_review_ratings( $request ) {
		$course_id = absint( $request->get_param( 'id' ) );
		$page      = max( 1, absint( $request->get_param( 'page' ) ) );
		$per_page  = absint( $request->get_param( 'per_page' ) );

		if ( $per_page < 1 ) {
			$per_page = 10;
		}
		if ( $per_page > 100 ) {
			$per_page = 100;
		}

		$comment_query = new \WP_Comment_Query();
		$comments      = $comment_query->query(
			array(
				'post_id' => $course_id,
				'status'  => 'approve',
				'type'    => 'academy_courses',
				'number'  => $per_page,
				'paged'   => $page,
				'orderby' => 'comment_ID',
				'order'   => 'DESC',
			)
		);

		$reviews = array();
		foreach ( $comments as $comment ) {
			$reviews[] = array(
				'name'         => $this->get_review_author_name( $comment ),
				'review_title' => $this->get_review_title( $comment->comment_ID ),
				'review'       => wp_kses_post( $comment->comment_content ),
				'created_at'   => get_comment_date( \Academy\Helper::get_date_format(), $comment ),
				'avatar'       => get_avatar_url( $comment, array( 'size' => 96 ) ),
			);
		}

		$review_count = (int) get_comments(
			array(
				'post_id' => $course_id,
				'status'  => 'approve',
				'type'    => 'academy_courses',
				'count'   => true,
			)
		);

		$rating = $this->get_course_approved_rating( $course_id );

		return rest_ensure_response(
			array(
				'reviews'        => $reviews,
				'review_count'   => $review_count,
				'average_rating' => $rating['average_rating'],
				'rating_from'    => 5,
				'page'           => $page,
				'per_page'       => $per_page,
				'total_pages'    => (int) ceil( $review_count / $per_page ),
			)
		);
	}

	public function create_item_review_rating( $request ) {
		$course_id    = absint( $request->get_param( 'id' ) );
		$user_id      = get_current_user_id();
		$current_user = get_userdata( $user_id );
		$rating       = absint( $request->get_param( 'rating' ) );
		$review       = (string) $request->get_param( 'review' );

		if ( $rating < 1 || $rating > 5 ) {
			return new \WP_Error(
				'rest_invalid_rating',
				esc_html__( 'Rating must be between 1 and 5.', 'academy' ),
				array( 'status' => 400 )
			);
		}

		if ( ! $current_user ) {
			return new \WP_Error(
				'rest_forbidden',
				esc_html__( 'You must be logged in to submit a review.', 'academy' ),
				array( 'status' => 401 )
			);
		}

		$comment_data = array(
			'comment_post_ID'      => $course_id,
			'comment_content'      => wp_kses_post( $review ),
			'user_id'              => $current_user->ID,
			'comment_author'       => $current_user->display_name ? $current_user->display_name : $current_user->user_login,
			'comment_author_email' => $current_user->user_email,
			'comment_author_url'   => $current_user->user_url,
			'comment_type'         => 'academy_courses',
			'comment_approved'     => '1',
		);

		$existing_reviews = get_comments(
			array(
				'comment_type' => 'academy_courses',
				'post_id'      => $course_id,
				'user_id'      => $current_user->ID,
				'status'       => 'all',
				'number'       => 1,
			)
		);

		$review_id = 0;
		$updated   = false;

		if ( ! empty( $existing_reviews ) ) {
			$existing_review = current( $existing_reviews );
			$review_id       = (int) $existing_review->comment_ID;
			$comment_data['comment_ID'] = $review_id;
			$update_result = wp_update_comment( $comment_data );

			if ( false === $update_result ) {
				return new \WP_Error(
					'rest_review_failed',
					esc_html__( 'Sorry, failed to submit review.', 'academy' ),
					array( 'status' => 500 )
				);
			}

			$updated = true;
			update_comment_meta( $review_id, 'academy_rating', $rating );
		} else {
			$comment_data['comment_meta'] = array(
				'academy_rating' => $rating,
			);
			$review_id = (int) wp_insert_comment( $comment_data );
		}

		if ( ! $review_id ) {
			return new \WP_Error(
				'rest_review_failed',
				esc_html__( 'Sorry, failed to submit review.', 'academy' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response(
			array(
				'success'   => true,
				'review_id' => $review_id,
				'updated'   => $updated,
			)
		);
	}

	public function toggle_item_wishlist( $request ) {
		global $wpdb;

		$course_id = absint( $request->get_param( 'id' ) );
		$user_id   = get_current_user_id();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$is_already_in_list = $wpdb->get_row( $wpdb->prepare( "SELECT * from {$wpdb->usermeta} WHERE user_id = %d AND meta_key = 'academy_course_wishlist' AND meta_value = %d;", $user_id, $course_id ) );

		if ( $is_already_in_list ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete(
				$wpdb->usermeta,
				array(
					'user_id'    => $user_id,
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_key'   => 'academy_course_wishlist',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'meta_value' => $course_id,
				)
			);

			return rest_ensure_response(
				array(
					'wishlist' => false,
					'message'  => esc_html__( 'Course removed from wishlist.', 'academy' ),
				)
			);
		}

		add_user_meta( $user_id, 'academy_course_wishlist', $course_id );

		return rest_ensure_response(
			array(
				'wishlist' => true,
				'message'  => esc_html__( 'Course added to wishlist.', 'academy' ),
			)
		);
	}

	public function get_item_enroll_course( $request ) {
		$course_id   = (int) $request->get_param( 'id' );
		$user_id     = (int) get_current_user_id();
		$is_enrolled = \Academy\Helper::is_enrolled( $course_id, $user_id );

		if ( $is_enrolled ) {
			return new \WP_Error( 'rest_already_enrolled', esc_html__( 'You are already enrolled in this course.', 'academy' ), array( 'status' => 400 ) );
		}

		$course_type = \Academy\Helper::get_course_type( $course_id );
		$course_type = apply_filters( 'academy/before_enroll_course_type', $course_type, $course_id );

		if ( 'free' !== $course_type && 'public' !== $course_type ) {
			return new \WP_Error( 'rest_enrollment_unavailable', esc_html__( 'This course is not available for direct enrollment.', 'academy' ), array( 'status' => 400 ) );
		}

		$result = \Academy\Helper::do_enroll( $course_id, $user_id );

		if ( is_wp_error( $result ) || ! $result ) {
			$error_message = is_wp_error( $result ) ? $result->get_error_message() : esc_html__( 'Failed to enroll in this course.', 'academy' );
			return new \WP_Error( 'rest_enrollment_failed', $error_message, array( 'status' => 500 ) );
		}

		return array(
			'success' => true,
			'message' => esc_html__( 'You have been successfully enrolled in the course.', 'academy' ),
		);
	}

	public function enroll_course( WP_REST_Request $request ) {
		$user_id   = get_current_user_id();
		$course_id = (int) $request->get_param( 'course_id' );

		$course_type = \Academy\Helper::get_course_type( $course_id );
		$course_type = apply_filters( 'academy/before_enroll_course_type', $course_type, $course_id );

		if ( 'free' !== $course_type && 'public' !== $course_type ) {
			return new \WP_Error(
				'enroll_failed',
				esc_html__( 'Failed to enroll course.', 'academy' ),
				array( 'status' => 400 )
			);
		}

		if ( ! \Academy\Helper::do_enroll( $course_id, $user_id ) ) {
			return new \WP_Error(
				'enroll_failed',
				esc_html__( 'Failed to enroll course.', 'academy' ),
				array( 'status' => 400 )
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => esc_html__( 'Successfully Enrolled.', 'academy' ),
			)
		);
	}

	public function get_course_completion( $request ) {
		$course_id = (int) $request->get_param( 'id' );
		$user_id   = (int) get_current_user_id();

		$has_incomplete_topic = false;
		$curriculum_lists     = \Academy\Helper::get_course_curriculum( $course_id );

		foreach ( $curriculum_lists as $curriculum_list ) {
			if ( ! is_array( $curriculum_list['topics'] ) ) {
				continue;
			}
			foreach ( $curriculum_list['topics'] as $topic ) {
				if ( empty( $topic['is_completed'] ) && 'sub-curriculum' !== $topic['type'] ) {
					$has_incomplete_topic = true;
					break 2;
				}
				if ( isset( $topic['topics'] ) && is_array( $topic['topics'] ) ) {
					foreach ( $topic['topics'] as $child_topic ) {
						if ( empty( $child_topic['is_completed'] ) ) {
							$has_incomplete_topic = true;
							break 3;
						}
					}
				}
			}
		}

		if ( $has_incomplete_topic ) {
			return new \WP_Error(
				'rest_incomplete_topics',
				esc_html__( 'To complete this course, please make sure that you have finished all the topics.', 'academy' ),
				array( 'status' => 400 )
			);
		}

		do_action( 'academy/admin/course_complete_before', $course_id, $user_id );

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$already_completed = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(comment_ID) FROM {$wpdb->comments}
				WHERE comment_agent = 'academy' AND comment_type = 'course_completed'
				AND comment_post_ID = %d AND user_id = %d",
				$course_id,
				$user_id
			)
		);

		if ( $already_completed > 0 ) {
			return new \WP_Error(
				'rest_already_completed',
				esc_html__( 'You have already completed this course.', 'academy' ),
				array( 'status' => 400 )
			);
		}

		$date = gmdate( 'Y-m-d H:i:s', \Academy\Helper::get_time() );

		// Generate a unique verification hash.
		do {
			$hash = substr( md5( wp_generate_password( 32 ) . $date . $course_id . $user_id ), 0, 16 );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$hash_exists = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(comment_ID) FROM {$wpdb->comments}
					WHERE comment_agent = 'academy' AND comment_type = 'course_completed' AND comment_content = %s",
					$hash
				)
			);
		} while ( $hash_exists > 0 );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$is_complete = $wpdb->insert(
			$wpdb->comments,
			array(
				'comment_post_ID'  => $course_id,
				'comment_author'   => $user_id,
				'comment_date'     => $date,
				'comment_date_gmt' => get_gmt_from_date( $date ),
				'comment_content'  => $hash,
				'comment_approved' => 'approved',
				'comment_agent'    => 'academy',
				'comment_type'     => 'course_completed',
				'user_id'          => $user_id,
			)
		);

		do_action( 'academy/admin/course_complete_after', $course_id, $user_id );

		if ( ! $is_complete ) {
			return new \WP_Error(
				'rest_completion_failed',
				esc_html__( 'Failed, try again.', 'academy' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => esc_html__( 'Successfully Completed.', 'academy' ),
			)
		);
	}

	public function get_item_complete_topic( $request ) {
		$user_id    = get_current_user_id();
		$course_id  = (int) $request['id'];
		$topic_type = $request->get_param( 'topic_type' );
		$topic_id   = (int) $request->get_param( 'topic_id' );

		do_action( 'academy/frontend/before_mark_topic_complete', $topic_type, $course_id, $topic_id, $user_id );

		$is_skip_disabled = \Academy\Helper::get_settings( 'is_disabled_lessons_video_skip' )
			&& \Academy\Helper::get_settings( 'is_enabled_academy_player' );

		if ( $is_skip_disabled && 'lesson' === $topic_type ) {
			$video_meta = \Academy\Helper::get_lesson_meta( $topic_id, 'video_source' );

			if ( ! empty( $video_meta['type'] ) && 'youtube' === $video_meta['type'] ) {
				$meta_key = "academy_{$course_id}lesson_video_{$topic_id}_completed";

				if ( ! get_user_meta( $user_id, $meta_key, true ) ) {
					return new \WP_Error(
						'video_incomplete',
						esc_html__( 'Please complete the lesson video first.', 'academy' ),
						array( 'status' => 400 )
					);
				}
			}
		}

		$option_name        = "academy_course_{$course_id}_completed_topics";
		$saved_topics_lists = get_user_meta( $user_id, $option_name, true );
		$saved_topics_lists = $saved_topics_lists ? json_decode( $saved_topics_lists, true ) : array();

		$is_complete = ! isset( $saved_topics_lists[ $topic_type ][ $topic_id ] );

		if ( $is_complete ) {
			$saved_topics_lists[ $topic_type ][ $topic_id ] = \Academy\Helper::get_time();
		} else {
			unset( $saved_topics_lists[ $topic_type ][ $topic_id ] );
		}

		update_user_meta( $user_id, $option_name, wp_json_encode( $saved_topics_lists ) );

		if ( $is_complete ) {
			do_action( 'academy/frontend/after_mark_topic_complete', $topic_type, $course_id, $topic_id, $user_id );
		} else {
			do_action( 'academy/frontend/mark_topic_incomplete', $topic_type, $course_id, $topic_id, $user_id );
		}

		return rest_ensure_response(
			array(
				'success'      => true,
				'is_completed' => $is_complete,
				'topics'       => $saved_topics_lists,
				'message'      => $is_complete
					? esc_html__( 'Topic marked as completed.', 'academy' )
					: esc_html__( 'Topic marked as incomplete.', 'academy' ),
			)
		);
	}

	public function get_my_courses( $request ) {
		$user_id = get_current_user_id();

		$status            = $request->get_param( 'status' );
		$recommended_limit = min( max( (int) $request->get_param( 'recommended_limit' ), 1 ), 24 );
		$page              = max( (int) $request->get_param( 'page' ), 1 );
		$per_page          = min( max( (int) $request->get_param( 'per_page' ), 1 ), 50 );

		$enrolled_ids  = array_map( 'intval', (array) \Academy\Helper::get_enrolled_courses_ids_by_user( $user_id ) );
		$completed_ids = array_map( 'intval', (array) \Academy\Helper::get_completed_courses_ids_by_user( $user_id ) );

		$completed_lookup = array_flip( $completed_ids );
		$enrollment_dates = $this->get_user_enrollment_dates( $user_id );

		$in_progress = array();
		$completed   = array();

		foreach ( $enrolled_ids as $course_id ) {
			$course = get_post( $course_id );

			if ( ! $course || 'academy_courses' !== $course->post_type || 'publish' !== $course->post_status ) {
				continue;
			}

			$total_topics     = (int) \Academy\Helper::get_total_number_of_course_topics( $course_id );
			$completed_topics = (int) \Academy\Helper::get_total_number_of_completed_course_topics_by_course_and_student_id( $course_id, $user_id );

			$progress_percent = $total_topics > 0
				? (int) ( ( $completed_topics / $total_topics ) * 100 )
				: 0;

			$is_completed = isset( $completed_lookup[ $course_id ] );

			$item = $this->prepare_my_course_item(
				$course_id,
				array(
					'status'                  => $is_completed ? 'completed' : 'in_progress',
					'is_completed'            => $is_completed,
					'progress_percent'        => $progress_percent,
					'total_topics'            => $total_topics,
					'completed_topics'        => $completed_topics,
					'last_completed_topic_at' => $this->get_last_completed_topic_timestamp( $course_id, $user_id ),
					'enrolled_at'             => $enrollment_dates[ $course_id ] ?? 0,
				)
			);

			if ( $is_completed ) {
				$completed[] = $item;
			} else {
				$in_progress[] = $item;
			}
		}

		$sort = function( $a, $b ) {
			return ( $b['last_completed_topic_at'] ?? 0 ) <=> ( $a['last_completed_topic_at'] ?? 0 )
				?: ( $b['enrolled_at'] ?? 0 ) <=> ( $a['enrolled_at'] ?? 0 );
		};

		usort( $in_progress, $sort );
		usort( $completed, $sort );

		$all = array_merge( $in_progress, $completed );

		$hero = null;
		if ( 'completed' === $status ) {
			$hero = $completed[0] ?? null;
		} else {
			$hero = $in_progress[0] ?? null;
		}

		if ( ! $hero ) {
			$hero = array(
				'type'    => 'fallback',
				'title'   => esc_html__( 'Start learning', 'academy' ),
				'message' => esc_html__( 'Pick a course to continue your learning journey.', 'academy' ),
			);
		}

		switch ( $status ) {
			case 'in_progress':
				$filtered = $in_progress;
				break;

			case 'completed':
				$filtered = $completed;
				break;

			default:
				$filtered = $all;
				break;
		}

		$total    = count( $filtered );
		$offset   = ( $page - 1 ) * $per_page;
		$items    = array_slice( $filtered, $offset, $per_page );
		$has_more = ( $offset + count( $items ) ) < $total;

		$recommendations = array();
		if ( 1 === $page ) {
			$exclude_ids = array_unique(
				array_merge( $enrolled_ids, $this->get_user_enrollment_course_ids_any_status( $user_id ) )
			);

			$recommendations = $this->get_recommended_courses( $enrolled_ids, $exclude_ids, $recommended_limit );
		}

		return rest_ensure_response(
			array(
				'success'         => true,
				'selected_status' => $status,
				'hero'            => $hero,
				'tabs_count'      => array(
					'all'         => count( $all ),
					'enrolled'    => count( $all ),
					'in_progress' => count( $in_progress ),
					'completed'   => count( $completed ),
				),
				'items'           => array_values( $items ),
				'pagination'      => array(
					'page'     => $page,
					'per_page' => $per_page,
					'total'    => $total,
					'has_more' => $has_more,
				),
				'recommended'     => $recommendations,
			)
		);
	}

	// =========================================================================
	// REST Response Filters
	// =========================================================================

	public function add_author_name_to_rest_response( $item, $post, $request ) {
		$author_data             = get_userdata( $item->data['author'] );
		$item->data['author_name'] = $author_data ? $author_data->display_name : '';
		return $item;
	}

	public function taxonomy_decode_special_character( $item, $post, $request ) {
		$item->data['name']        = html_entity_decode( $item->data['name'] );
		$item->data['slug']        = urldecode( $item->data['slug'] );
		$item->data['description'] = html_entity_decode( $item->data['description'] );
		return $item;
	}

	// Unused — kept for backwards-compatibility with any external hook consumers.
	public function get_announcements_permissions_check( $request ) {
		$course_id = $request->get_param( 'id' );
		$user_id   = (int) get_current_user_id();

		if ( current_user_can( 'administrator' )
			|| \Academy\Helper::is_instructor_of_this_course( $user_id, $course_id )
			|| \Academy\Helper::is_enrolled( $course_id, $user_id )
			|| \Academy\Helper::is_public_course( $course_id )
		) {
			return true;
		}

		return false;
	}

	// =========================================================================
	// Private Helpers
	// =========================================================================

	private function check_course_access( $course_id, $user_id ) {
		if ( current_user_can( 'administrator' )
			|| \Academy\Helper::is_instructor_of_this_course( $user_id, $course_id )
			|| \Academy\Helper::is_enrolled( $course_id, $user_id )
		) {
			return true;
		}

		return new \WP_Error(
			'rest_forbidden',
			esc_html__( 'You do not have permission to access this course.', 'academy' ),
			array( 'status' => 403 )
		);
	}

	private function get_user_enrollment_dates( $user_id ) {
		$enrollments = get_posts(
			array(
				'post_type'      => 'academy_enrolled',
				'post_status'    => 'completed',
				'author'         => (int) $user_id,
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		$dates = array();
		foreach ( $enrollments as $enrollment_id ) {
			$course_id = (int) get_post_field( 'post_parent', $enrollment_id );
			if ( ! $course_id || isset( $dates[ $course_id ] ) ) {
				continue;
			}
			$dates[ $course_id ] = (int) get_post_time( 'U', true, $enrollment_id );
		}

		return $dates;
	}

	private function get_last_completed_topic_timestamp( $course_id, $user_id ) {
		$completed_topics = json_decode(
			get_user_meta( (int) $user_id, 'academy_course_' . (int) $course_id . '_completed_topics', true ),
			true
		);

		if ( ! is_array( $completed_topics ) ) {
			return 0;
		}

		$last_timestamp = 0;
		foreach ( $completed_topics as $topic_group ) {
			if ( ! is_array( $topic_group ) ) {
				continue;
			}
			foreach ( $topic_group as $timestamp ) {
				$candidate = (int) $timestamp;
				if ( $candidate > $last_timestamp ) {
					$last_timestamp = $candidate;
				}
			}
		}

		return $last_timestamp;
	}

	private function get_recommended_courses( $seed_course_ids, $exclude_course_ids, $limit ) {
		$seed_course_ids    = array_values( array_filter( array_map( 'intval', (array) $seed_course_ids ) ) );
		$exclude_course_ids = array_values( array_filter( array_map( 'intval', (array) $exclude_course_ids ) ) );

		$seed_category_ids = array();
		$seed_tag_ids      = array();

		foreach ( $seed_course_ids as $course_id ) {
			$category_ids = wp_get_post_terms( $course_id, 'academy_courses_category', array( 'fields' => 'ids' ) );
			$tag_ids      = wp_get_post_terms( $course_id, 'academy_courses_tag', array( 'fields' => 'ids' ) );

			if ( is_array( $category_ids ) ) {
				$seed_category_ids = array_merge( $seed_category_ids, array_map( 'intval', $category_ids ) );
			}
			if ( is_array( $tag_ids ) ) {
				$seed_tag_ids = array_merge( $seed_tag_ids, array_map( 'intval', $tag_ids ) );
			}
		}

		$seed_category_ids = array_values( array_unique( $seed_category_ids ) );
		$seed_tag_ids      = array_values( array_unique( $seed_tag_ids ) );

		$tax_query = array();
		if ( ! empty( $seed_category_ids ) ) {
			$tax_query[] = array(
				'taxonomy' => 'academy_courses_category',
				'field'    => 'term_id',
				'terms'    => $seed_category_ids,
			);
		}
		if ( ! empty( $seed_tag_ids ) ) {
			$tax_query[] = array(
				'taxonomy' => 'academy_courses_tag',
				'field'    => 'term_id',
				'terms'    => $seed_tag_ids,
			);
		}
		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'OR';
		}

		$query_args = array(
			'post_type'      => 'academy_courses',
			'post_status'    => 'publish',
			'posts_per_page' => (int) $limit,
			'post__not_in'   => $exclude_course_ids,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		);

		if ( ! empty( $tax_query ) ) {
			$query_args['tax_query'] = $tax_query;
		}

		$recommendations = array();
		foreach ( get_posts( $query_args ) as $post ) {
			$item = $this->prepare_my_course_item( $post->ID );
			if ( ! empty( $item ) ) {
				$recommendations[] = $item;
			}
		}

		return $recommendations;
	}

	private function get_course_post( $course_id ) {
		$course = get_post( $course_id );
		if ( ! $course || 'academy_courses' !== $course->post_type ) {
			return false;
		}

		return $course;
	}

	private function get_review_author_name( $comment ) {
		$user = $comment->user_id ? get_userdata( $comment->user_id ) : false;
		if ( $user && ! empty( $user->display_name ) ) {
			return $user->display_name;
		}

		return $comment->comment_author;
	}

	private function get_review_title( $comment_id ) {
		$meta_keys = array(
			'academy_review_title',
			'review_title',
		);

		foreach ( $meta_keys as $meta_key ) {
			$review_title = get_comment_meta( $comment_id, $meta_key, true );
			if ( ! empty( $review_title ) ) {
				return $review_title;
			}
		}

		return '';
	}

	private function get_course_approved_rating( $course_id ) {
		global $wpdb;

		$rating = array(
			'rating_count'   => 0,
			'rating_sum'     => 0,
			'average_rating' => 0.0,
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(commentmeta.meta_value) AS rating_count,
					SUM(commentmeta.meta_value) AS rating_sum
				FROM {$wpdb->comments} comments
					INNER JOIN {$wpdb->commentmeta} commentmeta
						ON comments.comment_ID = commentmeta.comment_id
				WHERE comments.comment_post_ID = %d
					AND comments.comment_type = %s
					AND comments.comment_approved = %s
					AND commentmeta.meta_key = %s",
				$course_id,
				'academy_courses',
				'1',
				'academy_rating'
			)
		);

		if ( ! empty( $result->rating_count ) ) {
			$rating['rating_count']   = (int) $result->rating_count;
			$rating['rating_sum']     = (int) $result->rating_sum;
			$rating['average_rating'] = round( $rating['rating_sum'] / $rating['rating_count'], 1 );
		}

		return $rating;
	}

	private function get_user_enrollment_course_ids_any_status( $user_id ) {
		$enrollments = get_posts(
			array(
				'post_type'      => 'academy_enrolled',
				'post_status'    => 'any',
				'author'         => (int) $user_id,
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		$course_ids = array();
		foreach ( $enrollments as $enrollment_id ) {
			$course_id = (int) get_post_field( 'post_parent', $enrollment_id );
			if ( $course_id > 0 ) {
				$course_ids[] = $course_id;
			}
		}

		return array_values( array_unique( array_map( 'intval', $course_ids ) ) );
	}

	private function prepare_my_course_item( $course_id, $extra = array() ) {
		$post = get_post( $course_id );
		if ( ! $post ) {
			return array();
		}

		$thumbnail_id  = get_post_thumbnail_id( $course_id );
		$thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'large' ) : '';
		$author_id     = (int) $post->post_author;
		$author_data   = get_userdata( $author_id );
		$category_ids  = wp_get_post_terms( $course_id, 'academy_courses_category', array( 'fields' => 'ids' ) );
		$tag_ids       = wp_get_post_terms( $course_id, 'academy_courses_tag', array( 'fields' => 'ids' ) );

		$item = array(
			'id'           => (int) $course_id,
			'title'        => html_entity_decode( get_the_title( $course_id ) ),
			'slug'         => $post->post_name,
			'link'         => get_permalink( $course_id ),
			'thumbnail'    => $thumbnail_url ?: '',
			'instructor'   => array(
				'id'   => $author_id,
				'name' => $author_data ? $author_data->display_name : '',
			),
			'category_ids' => array_map( 'intval', is_array( $category_ids ) ? $category_ids : array() ),
			'tag_ids'      => array_map( 'intval', is_array( $tag_ids ) ? $tag_ids : array() ),
		);

		return array_merge( $item, $extra );
	}

	public function get_topics_permissions_check( $request ) {

		$course_id = (int) $request['id'];

		// 1. Allow admins immediately (full access)
		if ( current_user_can( 'administrator' ) ) {
			return true;
		}

		// 2. Allow instructor of this course
		if ( is_user_logged_in() && \Academy\Helper::is_instructor_of_this_course( get_current_user_id(), $course_id ) ) {
			return true;
		}

		// 3. Get course status
		$status = get_post_status( $course_id );

		// Block invalid course
		if ( ! $status ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Invalid course.', 'academy' ),
				array( 'status' => 403 )
			);
		}

		// 4. Public course (only if published)
		if ( 'publish' === $status ) {
			if( \Academy\Helper::is_public_course( $course_id ) || \Academy\Helper::get_addon_active_status( 'course-preview' ) ) {
				return true;
			}
		}
		
		// 5. Must be logged in beyond this point
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Authentication required.', 'academy' ),
				array( 'status' => 401 )
			);
		}

		$user_id = get_current_user_id();

		// 6. Enrolled users
		if ( \Academy\Helper::is_enrolled( $course_id, $user_id ) ) {
			return true;
		}

		// 7. Private course capability
		if ( 'private' === $status && current_user_can( 'read_private_academy_courses' ) ) {
			return true;
		}

		// 8. Final deny
		return new \WP_Error(
			'rest_forbidden',
			__( 'You are not allowed to access this course.', 'academy' ),
			array( 'status' => 403 )
		);
	}

}
