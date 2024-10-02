<?php
namespace AcademyWebhooks\Listeners;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy;
use AcademyWebhooks\Interfaces\ListenersInterface;
use AcademyWebhooks\Classes\Payload;

class CourseCompleted implements ListenersInterface {
	public static function dispatch( $deliver_callback, $webhook ) {
		add_action(
			'academy/admin/course_complete_after',
			function( $course_id, $user_id ) use ( $deliver_callback, $webhook ) {
				call_user_func_array(
					$deliver_callback,
					array(
						$webhook,
						self::get_payload( $course_id, $user_id )
					)
				);
			}, 10, 2
		);
	}

	public static function get_payload( $course_id, $user_id ) {
		$total_completed_topics = \Academy\Helper::get_total_number_of_completed_course_topics_by_course_and_student_id( $course_id, $user_id );
		$course_curriculums = \Academy\Helper::get_course_curriculums_number_of_counts( $course_id );
		$curriculums = get_post_meta( $course_id, 'academy_course_curriculum', true );
		$user_data = Payload::get_user_data( $user_id );
		$image_url = get_avatar_url( $user_id );
		$data = array_merge( Payload::get_course_data( $course_id ), array(
			'curriculums'        => (array) self::get_curriculum_topics( $curriculums ),
			'summary'            => (array) self::get_all_topics_summary( $course_curriculums, $total_completed_topics ),
			'_user'               => $user_data,
			'user_profile_image_id' => (int) attachment_url_to_postid( $image_url ),
			'user_profile_image_url' => $image_url,
		) );

		return apply_filters( 'academy_webhooks/course_completed_payload', $data );
	}

	public static function get_all_topics_summary( $curriculum, $completed_topics ) {
		if ( ! $curriculum ) {
			return;
		}
		$topics = array(
			'lesson_completed' => $curriculum['total_lessons'],
			'lesson_total'    => $curriculum['total_lessons'],
			'quiz_completed' => $curriculum['total_quizzes'],
			'quiz_total' => $curriculum['total_quizzes'],

		);
		if ( Academy\Helper::is_active_academy_pro() ) {
			$topics['assignment_completed'] = $curriculum['total_assignments'];
			$topics['assignment_total'] = $curriculum['total_assignments'];
			$topics['tutor_booking_pending'] = $curriculum['total_tutor_bookings'];
			$topics['tutor_booking_completed'] = $curriculum['total_tutor_bookings'];
			$topics['tutor_booking_total'] = $curriculum['total_tutor_bookings'];
			$topics['zoom_pending'] = $curriculum['total_zoom_meetings'];
			$topics['zoom_completed'] = $curriculum['total_zoom_meetings'];
			$topics['zoom_total'] = $curriculum['total_zoom_meetings'];
			$topics['topics_item_completed'] = $completed_topics;
			$topics['topics_total_item'] = $curriculum['total_topics'];

		} else {
			$total_topics = $curriculum['total_lessons'] + $curriculum['total_quizzes'];
			$topics['topics_item_completed'] = $total_topics;
			$topics['topics_total_item'] = $total_topics;
		}//end if
		return $topics;
	}

	public static function get_curriculum_topics( $curriculums ) {
		if ( ! $curriculums ) {
			return;
		}
		$items = array();
		foreach ( $curriculums as $curriculum ) {
			$curriculumItem = array(
				'curriculum_title' => $curriculum['title'],
				'topics' => array(),  // Initialize the topics array for each curriculum
			);

			// Check for conditions and add topics
			if ( ! Academy\Helper::is_active_academy_pro() && is_array( $curriculum['topics'] ) && ( 'lesson' === $curriculum['topics']['type'] || 'quiz' === $curriculum['topics']['type'] ) ) {
				foreach ( $curriculum['topics'] as $topics ) {
					$curriculumItem['topics'][] = array(
						'topics_item_id'    => $topics['id'],
						'topics_item_title' => $topics['name'],
						'topics_item_type'  => $topics['type'],
					);
				}
			} else {
				foreach ( $curriculum['topics'] as $topics ) {
					$curriculumItem['topics'][] = array(
						'topics_item_id'    => $topics['id'],
						'topics_item_title' => $topics['name'],
						'topics_item_type'  => $topics['type'],
					);
				}
			}

			// Add the curriculum item to the main $items array
			$items[] = $curriculumItem;
		}//end foreach

		return $items;
	}
}
