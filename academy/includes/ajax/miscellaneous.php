<?php

namespace Academy\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes\AbstractAjaxHandler;
use Academy\Classes\Analytics;
use Academy\Classes\Sanitizer;
use Academy\Helper;

class Miscellaneous extends AbstractAjaxHandler {
	public function __construct() {
		$this->actions = array(
			'get_admin_menu_items'      => array(
				'callback' => array( $this, 'get_admin_menu_items' ),
			),
			'get_analytics'             => array(
				'callback' => array( $this, 'get_analytics' ),
			),
			'change_post_status'        => array(
				'callback'   => array( $this, 'change_post_status' ),
				'capability' => 'manage_academy_instructor'
			),
			'fetch_posts'               => array(
				'callback'   => array( $this, 'fetch_posts' ),
				'capability' => 'manage_academy_instructor'
			),
			'render_popup_login'        => array(
				'callback'             => array( $this, 'render_popup_login' ),
				'allow_visitor_action' => true
			),
			'mark_topic_complete'       => array(
				'callback'   => array( $this, 'mark_topic_complete' ),
				'capability' => 'read'
			),
			'saved_user_info'           => array(
				'callback'   => array( $this, 'saved_user_info' ),
				'capability' => 'read'
			),
			'reset_password'            => array(
				'callback'   => array( $this, 'reset_password' ),
				'capability' => 'read'
			),
			'get_user_given_reviews'    => array(
				'callback'   => array( $this, 'get_user_given_reviews' ),
				'capability' => 'read'
			),
			'get_user_received_reviews' => array(
				'callback'   => array( $this, 'get_user_received_reviews' ),
				'capability' => 'read'
			),
			'get_user_purchase_history' => array(
				'callback'   => array( $this, 'get_user_purchase_history' ),
				'capability' => 'read'
			),
			'insert_lesson_comment' => array(
				'callback'   => array( $this, 'insert_lesson_comment' ),
				'capability' => 'read'
			),
			'get_lesson_comment' => array(
				'callback'   => array( $this, 'get_lesson_comment' ),
				'capability' => 'read'
			),
			'hide_zencommunity_ads' => array(
				'callback' => array( $this, 'hide_zencommunity_ads' ),
				'capability' => 'manage_options'
			),
			'fetch_roles' => array(
				'callback' => array( $this, 'get_roles' ),
				'capability' => 'manage_options'
			),
			'fetch_courses' => array(
				'callback' => array( $this, 'fetch_courses' ),
				'capability' => 'manage_options'
			)
		);
	}

	public function get_admin_menu_items() {
		$menu_items = wp_json_encode( Helper::get_admin_menu_list() );
		wp_send_json_success( $menu_items );
	}

	public function get_analytics() {
		$analytics = new Analytics();
		wp_send_json_success( $analytics->get_analytics() );
	}

	public function change_post_status( $payload_data ) {
		$payload = Sanitizer::sanitize_payload( [
			'post_id' => 'integer',
			'status'  => 'string',
		], $payload_data );

		$post_id   = $payload['post_id'];
		$status    = $payload['status'];
		$is_update = wp_update_post( array(
			'ID'          => $post_id,
			'post_status' => $status,
		), true, true );
		wp_send_json_success( $is_update );
	}

	public function fetch_posts( $payload_data ) {
		$payload = Sanitizer::sanitize_payload( [
			'postId'   => 'integer',
			'postType' => 'string',
			'keyword'  => 'string',
		], $payload_data );

		$post_type = ( isset( $payload['postType'] ) ? $payload['postType'] : 'page' );
		$postId    = ( isset( $payload['postId'] ) ? $payload['postId'] : 0 );
		$keyword   = ( isset( $payload['keyword'] ) ? $payload['keyword'] : '' );

		if ( $postId ) {
			$args = array(
				'post_type' => $post_type,
				'p'         => $postId,
			);
		} else {
			$args = array(
				'post_type'      => $post_type,
				'posts_per_page' => 10,
				'post_status'    => [ 'publish', 'private' ]
			);
			if ( ! empty( $keyword ) ) {
				$args['s'] = $keyword;
			}
			if ( ! current_user_can( 'manage_options' ) ) {
				$args['author'] = get_current_user_id();
			}
		}
		$results = array();
		$posts   = get_posts( $args );
		if ( is_array( $posts ) ) {
			foreach ( $posts as $post ) {
				$results[] = array(
					'label' => $post->post_title,
					'value' => $post->ID,
				);
			}
		}
		wp_send_json_success( $results );
	}

	public function render_popup_login() {
		if ( is_user_logged_in() ) {
			wp_die();
		}

		$payload = Sanitizer::sanitize_payload( [
			'current_permalink' => 'url',
		], $_REQUEST ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_permalink = Helper::sanitize_referer_url( $payload['current_permalink'] );
		$register_url      = esc_url( add_query_arg( array(
			'redirect_to' => $current_permalink
		), Helper::get_page_permalink( 'frontend_student_reg_page' ) ) );
		ob_start();
		echo do_shortcode( '[academy_login_form 
			form_title="' . esc_html__( 'Hi, Welcome back!', 'academy' ) . '" 
			show_logged_in_message="false" 
			student_register_url="' . $register_url . '"
			login_redirect_url="' . $current_permalink . '"]'
		);
		$markup = ob_get_clean();
		wp_send_json_success( $markup );
	}

	public function mark_topic_complete( $payload_data ) {
		$payload = Sanitizer::sanitize_payload( [
			'course_id'  => 'integer',
			'topic_type' => 'string',
			'topic_id'   => 'integer',
		], $payload_data );

		$course_id  = $payload['course_id'];
		$topic_type = $payload['topic_type'];
		$topic_id   = $payload['topic_id'];
		$user_id    = (int) get_current_user_id();
		if ( empty( $topic_type ) || ! $course_id || ! $topic_id ) {
			wp_send_json_error( __( 'Request is not valid.', 'academy' ) );
		}

		do_action( 'academy/frontend/before_mark_topic_complete', $topic_type, $course_id, $topic_id, $user_id );
		$is_skip_disabled = \Academy\Helper::get_settings( 'is_disabled_lessons_video_skip' ) && \Academy\Helper::get_settings( 'is_enabled_academy_player' );

		if ( $is_skip_disabled && 'lesson' === $topic_type && 'youtube' === \Academy\Helper::get_lesson_meta( $topic_id, 'video_source' )['type'] ) {
			$meta_key     = "academy_{$course_id}lesson_video_{$topic_id}_completed";
			$is_completed = get_user_meta( $user_id, $meta_key, true );

			if ( ! $is_completed ) {
				wp_send_json_error(
					__( 'Please complete the lesson video before marking the topic as completed.', 'academy' )
				);
			}
		}
		$option_name        = 'academy_course_' . $course_id . '_completed_topics';
		$is_complete = true;
		$saved_topics_lists = (array) json_decode( get_user_meta( $user_id, $option_name, true ), true );

		if ( isset( $saved_topics_lists[ $topic_type ][ $topic_id ] ) ) {
			$is_complete = false;
			unset( $saved_topics_lists[ $topic_type ][ $topic_id ] );
		} else {
			$saved_topics_lists[ $topic_type ][ $topic_id ] = Helper::get_time();
		}
		$saved_topics_lists = wp_json_encode( $saved_topics_lists );
		update_user_meta( $user_id, $option_name, $saved_topics_lists );

		if ( $is_complete ) {
			do_action( 'academy/frontend/after_mark_topic_complete', $topic_type, $course_id, $topic_id, $user_id );
		} else {
			do_action( 'academy/frontend/mark_topic_incomplete', $topic_type, $course_id, $topic_id, $user_id );
		}

		wp_send_json_success( $saved_topics_lists );
	}

	public function saved_user_info( $payload_data ) {
		$payload = Sanitizer::sanitize_payload( [
			'first_name'                  => 'string',
			'last_name'                   => 'string',
			'academy_profile_photo'       => 'string',
			'academy_cover_photo'         => 'string',
			'academy_phone_number'        => 'string',
			'academy_profile_designation' => 'string',
			'academy_profile_bio'         => 'string',
			'academy_website_url'         => 'string',
			'academy_github_url'          => 'string',
			'academy_facebook_url'        => 'string',
			'academy_twitter_url'         => 'string',
			'academy_linkedin_url'        => 'string',
		], $payload_data );

		$user_info = $payload;

		$user_id = get_current_user_id();
		foreach ( $user_info as $key => $value ) {
			update_user_meta( $user_id, $key, $value );
		}
		wp_send_json_success( $user_info );
	}

	public function reset_password( $payload_data ) {
		$payload = Sanitizer::sanitize_payload( [
			'current_password'     => 'string',
			'new_password'         => 'string',
			'confirm_new_password' => 'string',
		], $payload_data );

		$current_password     = ( $payload['current_password'] ? $payload['current_password'] : '' );
		$new_password         = ( $payload['new_password'] ? $payload['new_password'] : '' );
		$confirm_new_password = ( $payload['confirm_new_password'] ? $payload['confirm_new_password'] : '' );

		$message      = '';
		$current_user = wp_get_current_user();
		if ( $current_user && wp_check_password( $current_password, $current_user->data->user_pass, $current_user->ID ) ) {
			if ( ! empty( $new_password ) && $new_password === $confirm_new_password ) {
				$user = wp_get_current_user();
				// Change password.
				wp_set_password( $new_password, $user->ID );
				// Log-in again.
				wp_set_auth_cookie( $user->ID );
				wp_set_current_user( $user->ID );
				do_action( 'wp_login', $user->user_login, $user );
				wp_send_json_success( esc_html__( 'Successfully, updated your password.', 'academy' ) );
				wp_die();
			} else {
				$message .= esc_html__( 'New Password and Confirm New password do not match equally.', 'academy' );
			}
		} else {
			$message .= esc_html__( 'Current password is incorrect.', 'academy' );
		}

		wp_send_json_error( $message );
	}

	public function get_user_given_reviews() {
		check_ajax_referer( 'academy_nonce', 'security' );
		$user_id = get_current_user_id();
		$reviews = Helper::get_reviews_by_user( $user_id );
		$results = [];
		if ( is_array( $reviews ) ) {
			foreach ( $reviews as $review ) {
				$review->post_title     = get_the_title( $review->comment_post_ID );
				$review->post_permalink = esc_url( get_the_permalink( $review->comment_post_ID ) );
				$results[]              = $review;
			}
		}
		wp_send_json_success( $results );
	}

	public function get_user_received_reviews() {
		$user_id = get_current_user_id();
		$reviews = Helper::get_reviews_by_instructor( $user_id );
		$results = [];
		if ( is_array( $reviews ) ) {
			foreach ( $reviews as $review ) {
				$review->post_title     = get_the_title( $review->comment_post_ID );
				$review->post_permalink = esc_url( get_the_permalink( $review->comment_post_ID ) );
				$results[]              = $review;
			}
		}
		wp_send_json_success( $results );
	}

	public function get_user_purchase_history() {
		if ( ! Helper::is_active_woocommerce() ) {
			wp_die();
		}
		$user_id = get_current_user_id();
		$orders  = Helper::get_orders_by_user_id( $user_id );
		$results = [];
		if ( is_array( $orders ) ) {
			foreach ( $orders as $order ) {
				$courses_order = Helper::get_course_enrolled_ids_by_order_id( $order->ID );
				$courses       = [];
				if ( is_array( $courses_order ) ) {
					foreach ( $courses_order as $course ) {
						$courses[] = [
							'ID'        => $course['course_id'],
							'title'     => get_the_title( $course['course_id'] ),
							'permalink' => esc_url( get_the_permalink( $course['course_id'] ) ),
						];
					}
				}
				$wc_order  = wc_get_order( $order->ID );
				$price     = $wc_order->get_total();
				$status    = Helper::order_status_context( $order->post_status );
				$results[] = [
					'ID'      => $order->ID,
					'courses' => $courses,
					'price'   => wc_price( $price, array( 'currency' => $wc_order->get_currency() ) ),
					'status'  => $status,
					'date'    => date_i18n( get_option( 'date_format' ), strtotime( $order->post_date ) ),
				];
			}//end foreach
		}//end if
		wp_send_json_success( array_reverse( $results ) );
	}

	public function insert_lesson_comment( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'post' => 'integer',
			'lesson_id' => 'integer',
			'parent' => 'integer',
			'content' => 'string',
		], $payload_data );

		$course_id = isset( $payload['post'] ) ? $payload['post'] : 0;
		$lesson_id = isset( $payload['lesson_id'] ) ? $payload['lesson_id'] : 0;
		$current_user = wp_get_current_user();
		if ( current_user_can( 'administrator' ) || \Academy\Helper::is_instructor_of_this_course( $current_user->ID, $course_id ) || \Academy\Helper::is_enrolled( $course_id, $current_user->ID ) || \Academy\Helper::is_public_course( $course_id ) ) {
			$comment_data = array(
				'comment_post_ID'      => $lesson_id,
				'comment_parent'       => $payload['parent'] ?? '0',
				'comment_content'      => $payload['content'],
				'comment_approved'     => true,
				'comment_type'         => 'comment',
				'user_id'              => $current_user->ID,
				'comment_author'       => $current_user->user_login,
				'comment_author_email' => $current_user->user_email,
				'comment_author_url'   => $current_user->user_url,
				'comment_agent'        => 'Academy',
				'comment_meta'         => array(
					'academy_comment_course_id' => $course_id ?? '0'
				)
			);

			$comment_id = wp_insert_comment( $comment_data );
			$comment = ( new \Academy\API\QuestionAnswer() )->prepare_comment_for_response( get_comment( $comment_id ) );

			do_action( 'academy/frontend/insert_course_lesson_comments', $comment );
			wp_send_json_success( $comment );

		}//end if
		wp_die( 'You do not have the permission to do this.' );
	}

	public function get_lesson_comment( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
			'lesson_id' => 'integer',
		], $payload_data );

		$course_id = $payload['course_id'] ?? 0;
		$lesson_id = $payload['lesson_id'] ?? 0;

		if ( ! $lesson_id ) {
			wp_send_json_success( [] );
		}

		$current_user = wp_get_current_user();

		if (
			current_user_can( 'administrator' ) ||
			\Academy\Helper::is_instructor_of_this_course( $current_user->ID, $course_id ) ||
			\Academy\Helper::is_enrolled( $course_id, $current_user->ID ) ||
			\Academy\Helper::is_public_course( $course_id )
		) {
			$comment_args = array(
				'status'  => true,
				'post_id' => $lesson_id,
				'type'    => 'comment',
			);

			$raw_comments = get_comments( $comment_args );
			foreach ( $raw_comments as $comment ) {
				$comment_data[] = ( new \Academy\API\QuestionAnswer() )->prepare_comment_for_response( $comment );
			}

			wp_send_json_success( $comment_data );
		}

		wp_die( 'You do not have the permission to do this.' );
	}

	public function hide_zencommunity_ads( $payload_data ) {
		update_option( 'academy_is_hide_zencommunity_menu', true, false );
	}

	public function get_roles() {
		global $wp_roles;

		$roles = $wp_roles->roles;
		$results[] = [
			'label' => 'All Roles',
			'value' => 'all'
		];
		if ( is_array( $roles ) && ! empty( $roles ) ) {
			foreach ( $roles as $role_key => $role ) {
				$results[] = array(
					'label' => $role['name'],
					'value' => $role_key
				);
			}
		}
		wp_send_json_success( $results );
	}

	public function fetch_courses( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'keyword' => 'string',
		], $payload_data );

		$keyword = $payload['keyword'] ?? '';

		$courses = get_posts( [
			'post_type' => 'academy_courses',
			'post_status' => 'publish',
			's' => $keyword,
			'posts_per_page' => 10,
		] );
		$results = [];
		if ( is_array( $courses ) ) {
			foreach ( $courses as $course ) {
				$results[] = array(
					'label' => $course->post_title,
					'ID' => (string) $course->ID,
				);
			}
		}
		wp_send_json_success( $results );
	}
}
