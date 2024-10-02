<?php
namespace Academy\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Academy\Classes\Query;

class User {

	public static function init() {
		$self = new self();
		add_action( 'rest_api_init', array( $self, 'register_user_data' ) );
	}

	public static function register_user_data() {
		register_rest_field(
			'user',
			'academy_meta',
			array(
				'get_callback' => array( __CLASS__, 'get_user_data_values' ),
				'schema'       => null,
			)
		);
	}

	public static function get_user_data_values( $user ) {
		$total_course = \Academy\Helper::get_course_ids_by_instructor_id( $user['id'] );
		$instructor_fields = \Academy\Helper::get_form_builder_fields( 'instructor' );
		$student_fields = \Academy\Helper::get_form_builder_fields( 'student' );

		$user_info = get_userdata( $user['id'] );
		$values        = array(
			'first_name'              => get_user_meta( $user['id'], 'first_name', true ),
			'last_name'               => get_user_meta( $user['id'], 'last_name', true ),
			'nicename'                => $user_info->data->user_nicename,
			'email'                   => $user_info->data->user_email,
			'cover_photo'             => get_user_meta( $user['id'], 'academy_cover_photo', true ),
			'profile_photo'           => get_user_meta( $user['id'], 'academy_profile_photo', true ),
			'designation'             => get_user_meta( $user['id'], 'academy_profile_designation', true ),
			'phone_number'            => get_user_meta( $user['id'], 'academy_phone_number', true ),
			'bio'                     => get_user_meta( $user['id'], 'academy_profile_bio', true ),
			'website_url'             => get_user_meta( $user['id'], 'academy_website_url', true ),
			'github_url'              => get_user_meta( $user['id'], 'academy_github_url', true ),
			'facebook_url'            => get_user_meta( $user['id'], 'academy_facebook_url', true ),
			'twitter_url'             => get_user_meta( $user['id'], 'academy_twitter_url', true ),
			'linkedin_url'            => get_user_meta( $user['id'], 'academy_linkedin_url', true ),
			'total_enrolled_courses'  => count( \Academy\Helper::get_enrolled_courses_ids_by_user( $user['id'] ) ),
			'total_completed_courses' => count( \Academy\Helper::get_completed_courses_ids_by_user( $user['id'] ) ),
			'total_students'          => \Academy\Helper::get_total_number_of_students_by_instructor( $user['id'] ),
			'total_courses'           => is_array( $total_course ) ? count( $total_course ) : 0,
			'total_lessons'           => \Academy\Helper::get_total_number_of_lessons_by_instructor( $user['id'] ),
			'total_questions'         => \Academy\Classes\Query::get_total_number_of_questions_by_instructor_id( $user['id'] ),
			'registration_date'       => get_date_from_gmt( $user_info->user_registered, get_option( 'date_format' ) . ', ' . get_option( 'time_format' ) )
		);

		if ( current_user_can( 'manage_academy_instructor' ) ) {
			foreach ( $instructor_fields as $instructor_field ) {
				$meta_key = 'academy_' . $instructor_field['name'];

				$meta_values = [
					'label' => $instructor_field['label'],
					'value' => get_user_meta( $user['id'], $meta_key, true ),
					'placeholder' => $instructor_field['placeholder'],
					'type' => $instructor_field['type'],
				];

				// Check if the type is 'time' and modify the time format
				if ( 'time' === $meta_values['type'] ) {
					$meta_values['value'] = date_i18n( get_option( 'time_format' ), strtotime( $meta_values['value'] ) );
				}

				$values['meta_data'][ $meta_key ] = $meta_values;
			}
		} else {
			foreach ( $student_fields as $student_field ) {
				$meta_key = 'academy_' . $student_field['name'];

				$meta_values = [
					'label' => $student_field['label'],
					'value' => get_user_meta( $user['id'], $meta_key, true ),
					'placeholder' => $student_field['placeholder'],
					'type' => $student_field['type'],
				];

				// Check if the type is 'time' and modify the time format
				if ( 'time' === $meta_values['type'] ) {
					$meta_values['value'] = date_i18n( get_option( 'time_format' ), strtotime( $meta_values['value'] ) );
				}
				$values['meta_data'][ $meta_key ] = $meta_values;
			}
			// get student course details
			$enrolled_course_ids = \Academy\Helper::get_enrolled_courses_ids_by_user( $user['id'] );
			$total_lessons = 0;
			$total_quizzes = 0;
			$total_assignments = 0;
			if ( count( $enrolled_course_ids ) ) {
				foreach ( $enrolled_course_ids as $course_id ) {
					$curriculum = \Academy\Helper::get_course_curriculums_number_of_counts( $course_id );
					// count total curriculum item
					$total_lessons += $curriculum['total_lessons'];
					$total_assignments += $curriculum['total_assignments'];
					$total_quizzes += $curriculum['total_quizzes'];
				}
			}
			$values['total_lessons'] = $total_lessons;
			$values['total_quiz'] = $total_quizzes;
			$values['total_assignment'] = $total_assignments;
			$values['total_questions'] = Query::get_total_number_of_questions_by_student_id( $user['id'] );
		}//end if

		if ( \Academy\Helper::get_addon_active_status( 'multi_instructor' ) ) {
			$earning       = (array) \Academy\Helper::get_earning_by_user_id( $user['id'] );
			$values['total_earnings'] = ( isset( $earning['balance'] ) ? $earning['balance'] : 0 );
		}
		$thumbnail_url = wp_get_attachment_image_src( get_user_meta( $user['id'], 'academy_profile_photo', true ), 'full', true );
		if ( is_array( $thumbnail_url ) && isset( $thumbnail_url[0] ) ) {
			$values['photo'] = $thumbnail_url[0];
		}

		return apply_filters( 'academy/api/user/meta_values', $values );
	}
}
