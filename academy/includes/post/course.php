<?php
namespace  Academy\Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Helper;
use Academy\Classes\Sanitizer;
use Academy\Classes\AbstractPostHandler;

class Course extends AbstractPostHandler {
	public function __construct() {
		$this->actions = array(
			'save_topic_mark_as_complete' => array(
				'callback' => array( $this, 'save_topic_mark_as_complete' ),
				'capability'    => 'read'
			),
			'insert_question' => array(
				'callback' => array( $this, 'insert_question' ),
				'capability'    => 'read'
			),
			'student_register_as_instructor' => array(
				'callback' => array( $this, 'student_register_as_instructor' ),
				'capability'    => 'read'
			),
		);
	}

	public function save_topic_mark_as_complete() {
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
			'topic_id' => 'integer',
			'topic_type' => 'string',
		], $_POST); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$course_id = $payload['course_id'];
		$topic_type = $payload['topic_type'];
		$topic_id = $payload['topic_id'];
		$user_id   = (int) get_current_user_id();

		do_action( 'academy/frontend/before_mark_topic_complete', $topic_type, $course_id, $topic_id, $user_id );

		$option_name = 'academy_course_' . $course_id . '_completed_topics';
		$saved_topics_lists = (array) json_decode( get_user_meta( $user_id, $option_name, true ), true );

		if ( isset( $saved_topics_lists[ $topic_type ][ $topic_id ] ) ) {
			unset( $saved_topics_lists[ $topic_type ][ $topic_id ] );
		} else {
			$saved_topics_lists[ $topic_type ][ $topic_id ] = \Academy\Helper::get_time();
		}
		$saved_topics_lists = wp_json_encode( $saved_topics_lists );
		update_user_meta( $user_id, $option_name, $saved_topics_lists );
		do_action( 'academy/frontend/after_mark_topic_complete', $topic_type, $course_id, $topic_id, $user_id );

		$referer_url = Helper::sanitize_referer_url( wp_get_referer() );
		wp_safe_redirect( $referer_url );
	}

	public function insert_question() {
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
			'parent' => 'integer',
			'content' => 'string',
			'status' => 'string',
			'title' => 'string',
		], $_POST); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$course_id = $payload['course_id'];
		$referer_url = Helper::sanitize_referer_url( wp_get_referer() );
		$current_user = wp_get_current_user();

		if ( current_user_can( 'administrator' ) || \Academy\Helper::is_instructor_of_this_course( $current_user->ID, $course_id ) || \Academy\Helper::is_enrolled( $course_id, $current_user->ID ) || \Academy\Helper::is_public_course( $course_id ) ) {
			$comment_data = array(
				'comment_post_ID'      => $course_id,
				'comment_parent'       => $payload['parent'] ?? '0',
				'comment_content'      => $payload['content'],
				'comment_approved'     => $payload['status'],
				'comment_type'         => 'academy_qa',
				'user_id'              => $current_user->ID,
				'comment_author'       => $current_user->user_login,
				'comment_author_email' => $current_user->user_email,
				'comment_author_url'   => $current_user->user_url,
				'comment_agent'        => 'AcademyLMS',
				'comment_meta'         => array(
					'academy_question_title' => $payload['title'] ?? '0'
				)
			);

			if ( wp_insert_comment( $comment_data ) ) {
				wp_safe_redirect( $referer_url );
				exit;
			}
		}//end if
		wp_die( 'You do not have the permission to do this.' );
	}

	public function student_register_as_instructor() {
		$payload = Sanitizer::sanitize_payload([
			'action' => 'string',
		], $_POST); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$referer_url = Helper::sanitize_referer_url( wp_get_referer() );

		if ( ! current_user_can( 'manage_academy_instructor' ) ) {
			$user_id = get_current_user_id();
			$user = new \WP_User( $user_id );
			update_user_meta( $user_id, 'is_academy_instructor', \Academy\Helper::get_time() );
			update_user_meta( $user_id, 'academy_instructor_status', 'pending' );
			if ( ! array_intersect( [ 'academy_instructor', 'subscriber' ], $user->roles ) ) {
				$user->add_role( 'subscriber' );
			}
			wp_safe_redirect( $referer_url );
			exit;
		}

		wp_die( 'You do not have the permission to do this.' );
	}
}
