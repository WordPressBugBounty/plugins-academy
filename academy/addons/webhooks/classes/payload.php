<?php
namespace AcademyWebhooks\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

class Payload {

	public static function get_user_data( $user_id ) {
		if ( ! $user_id ) {
			return;
		}
		$user = get_userdata( $user_id );
		$user_data = [
			'ID' => $user_id,
			'name' => $user->display_name,
			'nicename' => $user->user_nicename,
			'email' => $user->user_email,
			'status' => $user->user_status,
			'date_created' => $user->user_registered,
			'display_name' => $user->display_name,
			'nickname' => $user->user_nicename,
			'first_name' => get_user_meta( $user_id, 'first_name', true ),
			'last_name' => get_user_meta( $user_id, 'last_name', true ),
			'description' => get_user_meta( $user_id, 'description', true ),
			'roles' => $user->roles,
			'use_ssl' => get_user_meta( $user_id, 'use_ssl', true ),
			'rich_editing' => get_user_meta( $user_id, 'rich_editing', true ),
			'syntax_highlighting' => get_user_meta(
				$user_id,
				'syntax_highlighting',
				true
			),
			'comment_shortcuts' => get_user_meta(
				$user_id,
				'comment_shortcuts',
				true
			),
			'avatar_url' => get_avatar_url( $user_id ),
		];
		return $user_data;
	}

	public static function get_lesson_data( $lesson ) {
		if ( ! $lesson ) {
			return;
		}
		$lesson_data = [
			'ID' => (int) $lesson['ID'],
			'name' => html_entity_decode( $lesson['lesson_title'] ),
			'create_date' => $lesson['lesson_date'],
			'content' => (string) $lesson['lesson_content']['rendered'],
			'excerpt' => $lesson['lesson_excerpt'],
			'date_created' => $lesson['lesson_date'],
			'date_modified' => $lesson['lesson_modified'],
			'featured_image' => (int) $lesson['meta']['featured_media'],
			'featured_image_url' => get_permalink(
				$lesson['meta']['featured_media']
			),
			'attachment' => (int) $lesson['meta']['attachment'],
			'attachment_url' => wp_get_attachment_url(
				$lesson['meta']['attachment']
			),
			'previewable' => (bool) $lesson['meta']['is_previewable'],
			'duration' => $lesson['meta']['video_duration'],
			'video_source' => $lesson['meta']['video_source'],
		];
		return $lesson_data;
	}

	public static function get_course_data( $course_id ) {
		if ( ! $course_id ) {
			return;
		}
		$course = get_post( $course_id );
		$product_id = get_post_meta(
			$course->ID,
			'academy_course_product_id',
			true
		);
		$regular_price = get_post_meta( $product_id, '_regular_price', true );
		$duration = get_post_meta( $course->ID, 'academy_course_duration', true );
		$enroll_student = count(
			get_posts([
				'post_parent' => $course->ID,
				'post_type' => 'academy_enrolled',
			])
		);
		$sale_price = get_post_meta( $product_id, '_sale_price', true );
		$course_type = get_post_meta( $course_id, 'academy_course_type', true );
		$featured_id = get_post_meta( $course->ID, '_thumbnail_id', true );
		$symbol = function_exists( 'get_woocommerce_currency_symbol' )
			? html_entity_decode(
				get_woocommerce_currency_symbol(),
				ENT_HTML5,
				'UTF-8'
			)
			: '';
		$course_data = [
			'ID' => (int) $course_id,
			'name' => wp_specialchars_decode( $course->post_title ),
			'permalink' => get_permalink( $course_id ),
			'status' => $course->post_status,
			'short_description' => $course->post_excerpt,
			'slug' => $course->post_name,
			'description' => $course->post_content,
			'preview_permalink' => get_preview_post_link( $course ),
			'reviews_allowed' => (bool) \Academy\Helper::get_settings(
				'is_enabled_course_review',
				true
			),
			'parent_id' => (int) $course->post_parent,
			'menu_order' => (int) $course->menu_order,
			'author_id' => (int) $course->post_author,
			'author_display_name' => (string) get_the_author_meta(
				'display_name',
				$course->post_author
			),
			'author_avatar_url' => get_avatar_url( $course->post_author ),
			'date_created' => $course->post_date,
			'date_modified' => $course->post_modified,
			'featured' => '',
			'price' => 'free' === $course_type ? 0 : $regular_price,
			'formatted_price' =>
				$symbol .
				number_format( 'free' === $course_type ? 0 : $regular_price, 2 ),
			'regular_price' => 'free' === $course_type ? 0 : $regular_price,
			'sale_price' => 'free' === $course_type ? 0 : $sale_price,
			'price_type' => (string) $course_type,
			'featured_image' => (int) $featured_id,
			'featured_image_url' => get_permalink( $featured_id ),
			'students_count' => (int) $enroll_student,
			'enrollment_limit' => (int) get_post_meta(
				$course_id,
				'academy_course_max_students',
				true
			),
			'duration' => (array) [
				'hours' => $duration[0],
				'minutes' => $duration[1],
				'seconds' => $duration[2],
			],
			'show_curriculum' => true,
			'edit_post_link' => get_edit_post_link( $course ),
			'difficulty' => (string) get_post_meta(
				$course_id,
				'academy_course_difficulty_level',
				true
			),
		];
		return $course_data;
	}

	public static function get_quiz_data( $quiz ) {
		if ( ! $quiz ) {
			return;
		}
		$time = get_post_meta( $quiz->ID, 'academy_quiz_time', true );
		$unit = get_post_meta( $quiz->ID, 'academy_quiz_time_unit', true );
		$duration = $time . ' ' . $unit;
		$attempts = get_post_meta(
			$quiz->ID,
			'academy_quiz_max_attempts_allowed',
			true
		);
		$feedback_mode = get_post_meta(
			$quiz->ID,
			'academy_quiz_feedback_mode',
			true
		);
		$pas_mark = get_post_meta(
			$quiz->ID,
			'academy_quiz_passing_grade',
			true
		);
		$data = [
			'ID' => (int) $quiz->ID,
			'name' => wp_specialchars_decode( $quiz->post_title ),
			'permalink' => get_permalink( $quiz->ID ),
			'status' => (string) $quiz->post_status,
			'short_description' => $quiz->post_excerpt,
			'slug' => $quiz->post_name,
			'description' => $quiz->post_content,
			'preview_link' => get_preview_post_link( $quiz ),
			'parent_id' => (int) $quiz->post_parent,
			'menu_order' => (int) $quiz->menu_order,
			'author' => (int) $quiz->post_author,
			'author_display_name' => get_the_author_meta(
				'display_name',
				$quiz->post_author
			),
			'author_avatar_url' => get_avatar_url( $quiz->post_author ),
			'date_created' => $quiz->post_date,
			'date_modified' => $quiz->post_modified,
			'question_order' => get_post_meta(
				$quiz->ID,
				'academy_quiz_questions_order',
				true
			),
			'hide_question_number' => (bool) get_post_meta(
				$quiz->ID,
				'academy_quiz_hide_question_number',
				true
			),
			'hide_quiz_time' => (bool) get_post_meta(
				$quiz->ID,
				'academy_quiz_hide_quiz_time',
				true
			),
			'feedback_mode' => 'retry' === $feedback_mode ? 'retry' : 'default',
			'attempts_allowed' => 'default' === $feedback_mode ? 0 : $attempts,
			'duration' => $duration,
			'passing_grade' => (int) $pas_mark,
			'edit_post_link' => get_edit_post_link( $quiz ),
		];
		return $data;
	}

	public static function get_question_data( $comment ) {
		if ( ! $comment ) {
			return;
		}
		$data = [
			'ID' => (int) $comment->comment_ID,
			'user_id' => (int) $comment->user_id,
			'user_name' => $comment->comment_author,
			'user_email' => sanitize_email( $comment->comment_author_email ),
			'user_avatar_url' => get_avatar_url( $comment->user_id ),
			'date_created' => $comment->comment_date,
			'IP_address' => $comment->comment_author_IP,
			'title' => get_comment_meta(
				$comment->comment_ID,
				'academy_question_title',
				true
			),
			'content' => (string) $comment->comment_content,
			'status' => $comment->comment_approved,
			'agent' => $comment->comment_agent,
			'parent' => (int) $comment->comment_parent,
		];
		return $data;
	}

	public static function get_quiz_attempt_object_data( $attempt_quiz ) {
		if ( ! $attempt_quiz ) {
			return;
		}
		$quiz = get_post( $attempt_quiz->quiz_id );
		$data = array_merge(
			[
				'total_questions' => $attempt_quiz->total_questions,
				'total_answered_questions' =>
					$attempt_quiz->total_answered_questions,
				'total_marks' => $attempt_quiz->total_marks,
				'earned_marks' => $attempt_quiz->earned_marks,
				'attempt_status' => $attempt_quiz->attempt_status,
				'attempt_started_at' => $attempt_quiz->attempt_started_at,
				'attempt_ended_at' => $attempt_quiz->attempt_ended_at,
			],
			[
				'_quiz' => self::get_quiz_data( $quiz ),
				'_course' => self::get_course_data( $attempt_quiz->course_id ),
				'_user' => self::get_user_data( $attempt_quiz->user_id ),
			]
		);

		return $data;
	}

	public static function get_quiz_attempt_array_data( $attempt_quiz ) {
		if ( ! $attempt_quiz ) {
			return;
		}
		$quiz = get_post( $attempt_quiz['quiz_id'] );
		$data = array_merge(
			[
				'total_questions' => $attempt_quiz['total_questions'],
				'total_answered_questions' =>
					$attempt_quiz['total_answered_questions'],
				'total_marks' => $attempt_quiz['total_marks'],
				'earned_marks' => $attempt_quiz['earned_marks'],
				'attempt_status' => $attempt_quiz['attempt_status'],
				'attempt_started_at' => $attempt_quiz['attempt_started_at'],
				'attempt_ended_at' => $attempt_quiz['attempt_ended_at'],
			],
			[
				'_quiz' => self::get_quiz_data( $quiz ),
				'_course' => self::get_course_data( $attempt_quiz['course_id'] ),
				'_user' => self::get_user_data( $attempt_quiz['user_id'] ),
			]
		);

		return $data;
	}

	public static function get_assignment_data( $assignment ) {
		if ( ! $assignment ) {
			return;
		}
		$assign_settings = get_post_meta(
			$assignment->ID,
			'academy_assignment_settings',
			true
		);
		$attachment_id = get_post_meta(
			$assignment->ID,
			'academy_assignment_attachment',
			true
		);

		$data = [
			'ID' => $assignment->ID,
			'title' => wp_specialchars_decode( $assignment->post_title ),
			'permalink' => get_permalink( $assignment->ID ),
			'status' => (string) $assignment->post_status,
			'short_description' => $assignment->post_excerpt,
			'slug' => $assignment->post_name,
			'description' => $assignment->post_content,
			'preview_link' => get_preview_post_link( $assignment ),
			'parent_id' => (int) $assignment->post_parent,
			'menu_order' => (int) $assignment->menu_order,
			'submission_time' => $assign_settings['submission_time'],
			'submission_time_unit' => $assign_settings['submission_time_unit'],
			'minimum_passing_points' =>
				$assign_settings['minimum_passing_points'],
			'total_points' => $assign_settings['total_points'],
			'attachment' => (int) $attachment_id,
			'attachment_url' => wp_get_attachment_url( $attachment_id ),
			'author' => (int) $assignment->post_author,
			'author_display_name' => get_the_author_meta(
				'display_name',
				$assignment->post_author
			),
			'author_avatar_url' => get_avatar_url( $assignment->post_author ),
			'date_created' => $assignment->post_date,
			'date_modified' => $assignment->post_modified,
			'edit_post_link' => get_edit_post_link( $assignment ),
		];

		return $data;
	}

	public static function get_tutor_booking_data( $booking ) {
		if ( ! $booking ) {
			return;
		}
		$repeated_schedule = [];
		$single_schedule = [];
		$regular_price = 0;
		$sale_price = 0;

		$schedule = get_post_meta(
			$booking->ID,
			'_academy_booking_schedule_time',
			true
		);
		$single_schedule = [
			'date' => $schedule['date'],
			'start_time' => $schedule['start_time'],
			'end_time' => $schedule['end_time'],
		];

		$repeated_days = get_post_meta(
			$booking->ID,
			'_academy_booking_schedule_repeated_times',
			true
		);
		foreach ( $repeated_days as $day ) {
			$schedules['day'] = $day['day'];
			foreach ( $day['scheduleTimes'] as $time ) {
				$schedules['start_time'] = $time['start_time'];
				$schedules['end_time'] = $time['end_time'];
			}
			$repeated_schedule[] = $schedules;
		}

		$type = get_post_meta( $booking->ID, '_academy_booking_type', true );
		if ( 'paid' === $type ) {
			$product_id = get_post_meta(
				$booking->ID,
				'_academy_booking_product_id',
				true
			);
			$regular_price = get_post_meta( $product_id, '_regular_price', true );
			$sale_price = get_post_meta( $product_id, '_sale_price', true );
		}

		$symbol = function_exists( 'get_woocommerce_currency_symbol' )
			? html_entity_decode(
				get_woocommerce_currency_symbol(),
				ENT_HTML5,
				'UTF-8'
			)
			: '';
		$schedule_type = get_post_meta(
			$booking->ID,
			'_academy_booking_schedule_type',
			true
		);
		$featured_id = get_post_meta( $booking->ID, '_thumbnail_id', true );

		$data = [
			'ID' => $booking->ID,
			'title' => wp_specialchars_decode( $booking->post_title ),
			'permalink' => get_permalink( $booking->ID ),
			'status' => (string) $booking->post_status,
			'short_description' => $booking->post_excerpt,
			'slug' => $booking->post_name,
			'description' => $booking->post_content,
			'preview_link' => get_preview_post_link( $booking ),
			'parent_id' => (int) $booking->post_parent,
			'menu_order' => (int) $booking->menu_order,
			'price_type' => $type,
			'regular_price' => $regular_price,
			'formatted_price' => $symbol . number_format( $regular_price, 2 ),
			'sale_price' => $sale_price,
			'class_type' => get_post_meta(
				$booking->ID,
				'_academy_booking_class_type',
				true
			),
			'schedule_type' => $schedule_type,
			'time_zone' => get_post_meta(
				$booking->ID,
				'_academy_booking_schedule_time_zone',
				true
			),
			'duration' =>
				get_post_meta( $booking->ID, '_academy_booking_duration', true ) .
				' minutes',
			'schedule' =>
				'single' === $schedule_type
					? $single_schedule
					: $repeated_schedule,
			'private_booked_info' => get_post_meta(
				$booking->ID,
				'_academy_booking_private_booked_info',
				true
			),
			'featured_image' => (int) $featured_id,
			'featured_image_url' => get_permalink( $featured_id ),
			'author' => (int) $booking->post_author,
			'author_display_name' => get_the_author_meta(
				'display_name',
				$booking->post_author
			),
			'author_avatar_url' => get_avatar_url( $booking->post_author ),
			'date_created' => $booking->post_date,
			'date_modified' => $booking->post_modified,
			'edit_post_link' => get_edit_post_link( $booking ),
		];

		return $data;
	}

	public static function get_zoom_data( $zoom_id ) {
		$zoom = get_post( $zoom_id );
		$zoom_request = json_decode(
			get_post_meta( $zoom_id, 'academy_zoom_request', true )
		);
		$zoom_response = json_decode(
			get_post_meta( $zoom_id, 'academy_zoom_response', true )
		);
		$data = [
			'ID' => (int) $zoom_id,
			'title' => wp_specialchars_decode( $zoom->post_title ),
			'permalink' => get_permalink( $zoom_id ),
			'status' => $zoom->post_status,
			'short_description' => $zoom->post_excerpt,
			'slug' => $zoom->post_name,
			'description' => $zoom->post_content,
			'preview_permalink' => get_preview_post_link( $zoom ),
			'parent_id' => (int) $zoom->post_parent,
			'menu_order' => (int) $zoom->menu_order,
			'author_id' => (int) $zoom->post_author,
			'author_display_name' => (string) get_the_author_meta(
				'display_name',
				$zoom->post_author
			),
			'author_avatar_url' => get_avatar_url( $zoom->post_author ),
			'date_created' => $zoom_response->created_at,
			'start_time' => $zoom_request->start_time,
			'timezone' => $zoom_request->timezone,
			'duration' => $zoom_request->duration . ' minutes',
			'recording' => $zoom_request->settings->recording_settings,
			'status' => $zoom_response->status,
			'host_email' => $zoom_response->host_email,
			'start_url' => $zoom_response->start_url,
			'join_url' => $zoom_response->join_url,
		];

		return $data;
	}
}
