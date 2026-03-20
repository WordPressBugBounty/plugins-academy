<?php

namespace Academy\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

use Academy\Helper;
use Academy\Classes\Registration;

class AcademyRegistration extends Registration {

	private $common_fields = [
		'text',
		'email',
		'password',
		'number',
		'date',
		'url',
		'tel',
		'color',
		'time',
		'range',
	];
	private $allow_fields = [
		'first-name',
		'last-name',
		'email',
		'confirm-email',
		'password',
		'confirm-password',
		'button',
	];
	public function __construct() {
		add_shortcode('academy_instructor_registration_form', [
			$this,
			'instructor_registration_form',
		]);
		add_shortcode('academy_student_registration_form', [
			$this,
			'student_registration_form',
		]);
	}

	public function instructor_registration_form() {
		ob_start();
		if (
			apply_filters(
				'academy/shortcode/instructor_registration_form_is_user_logged_in',
				is_user_logged_in()
			)
		) {
			$dashboard_page_id = (int) \Academy\Helper::get_settings(
				'frontend_dashboard_page'
			);
			$user_id = get_current_user_id();
			$instructor_status = '';
			if ( get_user_meta( $user_id, 'is_academy_instructor', true ) ) {
				$instructor_status = get_user_meta(
					$user_id,
					'academy_instructor_status',
					true
				);
			}
			\Academy\Helper::get_template(
				'shortcode/logged-in-instructor.php',
				[
					'dashboard_url' => get_permalink( $dashboard_page_id ),
					'instructor_status' => $instructor_status,
				]
			);
		} else {
			$instructor_form_fields = $this->get_form_fields( 'instructor' );
			$is_pro_active = \Academy\Helper::is_active_academy_pro();
			\Academy\Helper::get_template('shortcode/instructor.php', [
				'form_fields' => $instructor_form_fields,
				'common_fields' => $this->common_fields,
				'allow_fields' => $is_pro_active ? [] : $this->allow_fields,
			]);
		}//end if

		return apply_filters( 'academy/shortcode/instructor', ob_get_clean() );
	}

	public function student_registration_form() {
		ob_start();
		if (
			apply_filters(
				'academy/shortcode/student_registration_form_is_user_logged_in',
				is_user_logged_in()
			)
		) {
			$dashboard_page_id = (int) \Academy\Helper::get_settings(
				'frontend_dashboard_page'
			);
			\Academy\Helper::get_template('shortcode/logged-in-student.php', [
				'dashboard_url' => get_permalink( $dashboard_page_id ),
			]);
		} else {
			$student_form_fields = $this->get_form_fields( 'student' );
			$is_pro_active = \Academy\Helper::is_active_academy_pro();
			\Academy\Helper::get_template('shortcode/student.php', [
				'form_fields' => $student_form_fields,
				'common_fields' => $this->common_fields,
				'allow_fields' => $is_pro_active ? [] : $this->allow_fields,
			]);
		}

		return apply_filters( 'academy/shortcode/student', ob_get_clean() );
	}
}
