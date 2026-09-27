<?php

namespace Academy\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes\AbstractAjaxHandler;
use Academy\Classes\Analytics;
use Academy\Classes\Sanitizer;
use Academy\Helper;
use WP_REST_Request;
use WP_REST_Response;

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
			'delete_lesson_comment' => array(
				'callback'   => array( $this, 'delete_lesson_comment' ),
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
			),
			'update_review' => array(
				'callback' => array( $this, 'update_review' ),
				'capability' => 'read'
			),
			'dismiss_admin_notice' => array(
				'callback'   => array( $this, 'dismiss_admin_notice' ),
				'capability' => 'manage_academy_instructor'
			),
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
		$payload = Sanitizer::sanitize_payload(
			[
				'post_id' => 'integer',
				'status'  => 'string',
			],
			$payload_data
		);

		$post_id = (int) ( $payload['post_id'] ?? 0 );
		$status  = $payload['status'] ?? '';

		if ( ! $post_id || empty( $status ) ) {
			wp_send_json_error( esc_html__( 'Invalid payload data.', 'academy' ) );
		}

		$post = get_post( $post_id );

		if ( ! $post ) {
			wp_send_json_error( esc_html__( 'Post not found.', 'academy' ) );
		}

		$current_user_id = (int) get_current_user_id();
		$post_author_id  = (int) $post->post_author;

		// Permission check
		if ( ! current_user_can( 'manage_options' ) && $post_author_id !== $current_user_id ) {
			wp_send_json_error( esc_html__( 'You are not allowed to update this post.', 'academy' ) );
		}

		$result = wp_update_post(
			[
				'ID'          => $post_id,
				'post_status' => $status,
			],
			true
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
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

		if ( ! \Academy\Helper::is_enrolled( $course_id, $user_id ) ) {
			wp_send_json_error( __( 'You must be enrolled in this course to mark lessons as complete.', 'academy' ) );
		}

		do_action( 'academy/frontend/before_mark_topic_complete', $topic_type, $course_id, $topic_id, $user_id );

		// Video completion gate: when a watch threshold is configured, a trackable
		// video lesson can only be marked complete once the student has watched
		// enough of it (the progress endpoint sets the watch-complete flag).
		// Opaque third-party embeds (Wistia, Vidyard, Twitch, SoundCloud,
		// Mixcloud, Facebook, Kaltura, …) get the same gate, but based on
		// dwell time (seconds open) since they report no playback position.
		$threshold       = (int) \Academy\Helper::get_settings( 'lessons_video_completion_threshold' );
		$dwell_threshold = (int) \Academy\Helper::get_settings( 'external_video_min_watch_seconds' );
		if ( ( $threshold > 0 || $dwell_threshold > 0 ) && 'lesson' === $topic_type ) {
			$video        = \Academy\Helper::get_lesson_meta( $topic_id, 'video_source' );
			$vtype        = is_array( $video ) ? ( $video['type'] ?? '' ) : '';
			$vurl         = $video['url'] ?? '';
			$is_trackable = \Academy\Helper::is_trackable_video_source( $vtype, $vurl );
			$is_dwell_trackable = ! $is_trackable && $dwell_threshold > 0
				&& \Academy\Helper::is_dwell_trackable_video_source( $vtype, $vurl );

			if ( ( $threshold > 0 && $is_trackable ) || $is_dwell_trackable ) {
				$meta_key = "academy_{$course_id}lesson_video_{$topic_id}_completed";
				if ( ! get_user_meta( $user_id, $meta_key, true ) ) {
					$message = $is_dwell_trackable
						? sprintf(
							/* translators: %d: required seconds. */
							__( 'Please keep this lesson open for at least %d seconds before marking it complete.', 'academy' ),
							$dwell_threshold
						)
						: sprintf(
							/* translators: %d: required watch percentage. */
							__( 'Please watch at least %d%% of the lesson video before marking it complete.', 'academy' ),
							$threshold
						);
					wp_send_json_error( $message );
				}
			}
		}//end if
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

	public function update_review( $payload_data ) {
		$payload = Sanitizer::sanitize_payload( [
			'comment_id' => 'integer',
			'content'    => 'string',
			'rating'     => 'integer',
		], $payload_data );
		$comment_id = $payload['comment_id'] ?? 0;
		$content    = $payload['content'] ?? '';
		$rating  = $payload['rating'] ?? 0;

		$comment = get_comment( $comment_id );

		if ( ! $comment || get_current_user_id() !== (int) $comment->user_id ) {
			wp_send_json_error( __( 'Permission denied', 'academy' ) );
		}

		wp_update_comment( array(
			'comment_ID'      => $comment_id,
			'comment_content' => $content,
		) );

		if ( isset( $payload['rating'] ) ) {
			update_comment_meta( $comment_id, 'academy_rating', (int) $rating );
		}

		wp_send_json_success();
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
		], $payload_data );

		// Preserve line breaks (sanitize_text_field would collapse them).
		$content = isset( $payload_data['content'] ) ? sanitize_textarea_field( wp_unslash( $payload_data['content'] ) ) : '';
		if ( '' === trim( $content ) ) {
			wp_send_json_error( 'Comment cannot be empty.', 400 );
		}

		$course_id = isset( $payload['post'] ) ? $payload['post'] : 0;
		$lesson_id = isset( $payload['lesson_id'] ) ? $payload['lesson_id'] : 0;
		$current_user = wp_get_current_user();
		if ( current_user_can( 'manage_options' ) || \Academy\Helper::is_instructor_of_this_course( $current_user->ID, $course_id ) || \Academy\Helper::is_enrolled( $course_id, $current_user->ID ) || \Academy\Helper::is_public_course( $course_id ) ) {
			$comment_data = array(
				'comment_post_ID'      => $lesson_id,
				'comment_parent'       => $payload['parent'] ?? 0,
				'comment_content'      => $content,
				'comment_approved'     => true,
				'comment_type'         => 'comment',
				'user_id'              => $current_user->ID,
				'comment_author'       => $current_user->user_login,
				'comment_author_email' => $current_user->user_email,
				'comment_author_url'   => $current_user->user_url,
				'comment_agent'        => 'Academy',
				'comment_meta'         => array(
					'academy_comment_course_id' => $course_id ?? 0
				)
			);

			$comment_id = wp_insert_comment( $comment_data );

			// Notification / integration hook (auto-approved for enrolled users).
			do_action( 'academy/lesson_comment/inserted', $comment_id, $course_id, $lesson_id );

			$comment = ( new \Academy\API\QuestionAnswer() )->prepare_comment_for_response( get_comment( $comment_id ) );

			wp_send_json_success( $comment );

		}//end if
		wp_die( 'You do not have the permission to do this.' );
	}

	public function delete_lesson_comment( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'comment_id' => 'integer',
			'course_id'  => 'integer',
		], $payload_data );

		$comment_id = $payload['comment_id'] ?? 0;
		$comment    = $comment_id ? get_comment( $comment_id ) : null;

		// Only Academy lesson comments can be removed through this endpoint.
		if ( ! $comment || 'Academy' !== $comment->comment_agent ) {
			wp_send_json_error( 'Comment not found.', 404 );
		}

		$course_id = $payload['course_id'] ? $payload['course_id'] : (int) get_comment_meta( $comment_id, 'academy_comment_course_id', true );
		$current_user = wp_get_current_user();

		$can_delete = current_user_can( 'manage_options' )
			|| \Academy\Helper::is_instructor_of_this_course( $current_user->ID, $course_id )
			|| ( (int) $comment->user_id === (int) $current_user->ID );

		if ( ! $can_delete ) {
			wp_die( 'You do not have the permission to do this.' );
		}

		// Remove replies first, then the comment itself.
		$children = get_comments([
			'parent' => $comment_id,
			'status' => 'all',
		]);
		foreach ( $children as $child ) {
			wp_delete_comment( (int) $child->comment_ID, true );
		}
		wp_delete_comment( $comment_id, true );

		wp_send_json_success( [ 'id' => $comment_id ] );
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
			current_user_can( 'manage_options' ) ||
			\Academy\Helper::is_instructor_of_this_course( $current_user->ID, $course_id ) ||
			\Academy\Helper::is_enrolled( $course_id, $current_user->ID ) ||
			\Academy\Helper::is_public_course( $course_id )
		) {
			// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- meta/tax lookup the feature depends on; no cheaper equivalent
			$comment_args = array(
				'status'  => 'approve',
				'post_id' => $lesson_id,
				'type'    => 'comment',
				'orderby' => 'comment_date_gmt',
				'order'   => 'ASC',
				// Sane upper bound so a huge thread doesn't load unbounded.
				'number'  => (int) apply_filters( 'academy/lesson_comment/fetch_limit', 200 ),
				// Scope to this course's Academy comments — in High-Performance
				// mode a lesson id could otherwise collide with a real post id.
				'meta_query' => array(
					array(
						'key'   => 'academy_comment_course_id',
						'value' => $course_id,
					),
				),
			);
			// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_query

			$raw_comments = get_comments( $comment_args );
			$comment_data = [];
			if ( ! empty( $raw_comments ) ) {
				foreach ( $raw_comments as $comment ) {
					$comment_data[] = ( new \Academy\API\QuestionAnswer() )->prepare_comment_for_response( $comment );
				}
			}

			wp_send_json_success( $comment_data );
		}//end if

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

	/**
	 * Persists a dismissed Admin::Notices notice for the current user (see
	 * includes/admin/notices.php — Notices::add_notice() filters against this
	 * on every subsequent request, so a dismissed notice never gets
	 * re-added). Capability + nonce are already enforced centrally by
	 * AbstractAjaxHandler::handle_ajax_request() before this runs, so the
	 * dismissal is always scoped to whichever user is actually logged in —
	 * there's no user-id parameter to spoof.
	 *
	 * @param array $payload_data
	 */
	public function dismiss_admin_notice( $payload_data ) {
		$payload = Sanitizer::sanitize_payload( [
			'notice_key' => 'string',
		], $payload_data );

		$notice_key = $payload['notice_key'] ?? '';
		if ( '' === $notice_key ) {
			wp_send_json_error( esc_html__( 'Invalid notice.', 'academy' ), 400 );
		}

		$user_id   = get_current_user_id();
		$dismissed = get_user_meta( $user_id, 'academy_dismissed_admin_notices', true );
		$dismissed = is_array( $dismissed ) ? $dismissed : array();

		if ( ! in_array( $notice_key, $dismissed, true ) ) {
			$dismissed[] = $notice_key;
			update_user_meta( $user_id, 'academy_dismissed_admin_notices', $dismissed );
		}

		wp_send_json_success( array( 'notice_key' => $notice_key ) );
	}

	public function fetch_courses( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'keyword' => 'string',
			'per_page' => 'integer',
			'include_private' => 'boolean',
		], $payload_data );

		$keyword  = $payload['keyword'] ?? '';
		$per_page = ! empty( $payload['per_page'] ) ? $payload['per_page'] : 10;
		// Default stays 'publish' only, unchanged for existing callers; the
		// students course filter opts in so it can match enrollments that live
		// on private courses.
		$post_status = ! empty( $payload['include_private'] ) ? [ 'publish', 'private' ] : 'publish';

		$courses = get_posts( [
			'post_type' => 'academy_courses',
			'post_status' => $post_status,
			's' => $keyword,
			'posts_per_page' => $per_page,
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
