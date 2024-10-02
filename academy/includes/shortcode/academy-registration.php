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
		add_action(
			'wp_ajax_nopriv_academy/shortcode/instructor_registration_form_handler',
			[ $this, 'instructor_registration_form_handler' ]
		);
		add_action(
			'wp_ajax_nopriv_academy/shortcode/student_registration_form_handler',
			[ $this, 'student_registration_form_handler' ]
		);
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

	public function instructor_registration_form_handler() {
		if (
			! wp_verify_nonce(
				$_POST['_wpnonce'],
				'academy_instructor_registration_nonce'
			)
		) {
			wp_send_json_error( [ __( 'Security check', 'academy' ) ] );
		}

		$this->check_and_send_error();

		$instructor_form_fields = $this->get_form_fields( 'instructor' );

		// get all the post data
		$submitted_data = $_POST;

		list($error, $user_data) = $this->sanitize_and_validate_fields(
			$instructor_form_fields,
			$submitted_data
		);

		if ( count( $error ) ) {
			wp_send_json_error( $error );
		}

		list(
			$login_data,
			$user_meta,
		) = $this->login_data_and_user_meta_extractor( $user_data );

		do_action(
			'academy/shortcode/before_instructor_registration',
			$login_data,
			'instructor'
		);

		$user_id = wp_insert_user( $login_data );

		if ( ! is_wp_error( $user_id ) ) {
			update_user_meta(
				$user_id,
				'is_academy_instructor',
				\Academy\Helper::get_time()
			);
			update_user_meta(
				$user_id,
				'academy_instructor_status',
				apply_filters(
					'academy/admin/registration_instructor_status',
					'pending'
				)
			);

			$this->save_meta_info( $user_meta, $user_id );

			do_action(
				'academy/shortcode/after_instructor_registration',
				$user_id
			);

			$user = get_user_by( 'id', $user_id );
			if ( $user ) {
				wp_set_current_user( $user_id, $user->user_login );
				wp_set_auth_cookie( $user_id );
			}
			if (
				apply_filters(
					'academy/is_allow_new_instructor_notification',
					true
				)
			) {
				wp_new_user_notification( $user_id, null, 'both' );
			}
		}//end if

		$referer_url = Helper::sanitize_referer_url( wp_get_referer() );

		$redirect_url = apply_filters(
			'academy/shortcode/after_register_instructor_redirect',
			$referer_url
		);

		wp_send_json_success([
			'message' => __(
				'Registration completed successfully. Redirecting...',
				'academy'
			),
			'redirect_url' => esc_url( $redirect_url ),
		]);
	}

	public function student_registration_form_handler() {
		if (
			! wp_verify_nonce(
				$_POST['_wpnonce'],
				'academy_student_registration_nonce'
			)
		) {
			wp_send_json_error( [ __( 'Security check', 'academy' ) ] );
		}

		$this->check_and_send_error();

		$student_form_fields = $this->get_form_fields( 'student' );

		// get all the post data
		$submitted_data = $_POST;

		list($error, $user_data) = $this->sanitize_and_validate_fields(
			$student_form_fields,
			$submitted_data
		);

		if ( count( $error ) ) {
			wp_send_json_error( $error );
		}

		list(
			$login_data,
			$user_meta,
		) = $this->login_data_and_user_meta_extractor( $user_data );
		// Assign Student Role
		$login_data['role'] = 'academy_student';

		do_action(
			'academy/shortcode/before_student_registration',
			$login_data,
			'student'
		);

		$user_id = wp_insert_user( $login_data );
		if ( ! is_wp_error( $user_id ) ) {
			do_action(
				'academy/shortcode/after_student_registration',
				$user_id
			);

			update_user_meta(
				$user_id,
				'is_academy_student',
				\Academy\Helper::get_time()
			);

			$this->save_meta_info( $user_meta, $user_id );

			$user = get_user_by( 'id', $user_id );
			if ( $user ) {
				wp_set_current_user( $user_id, $user->user_login );
				wp_set_auth_cookie( $user_id );
			}
			if (
				apply_filters(
					'academy/is_allow_new_student_notification',
					true
				)
			) {
				wp_new_user_notification( $user_id, null, 'both' );
			}
		}//end if

		$referer_url = Helper::sanitize_referer_url( wp_get_referer() );

		$redirect_url = apply_filters(
			'academy/shortcode/after_register_student_redirect',
			$referer_url
		);

		wp_send_json_success([
			'message' => __(
				'Registration completed successfully. Redirecting...',
				'academy'
			),
			'redirect_url' => esc_url( $redirect_url ),
		]);
	}
}
