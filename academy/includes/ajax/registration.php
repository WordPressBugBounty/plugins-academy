<?php
namespace  Academy\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes\Sanitizer;
use Academy\Classes\AbstractAjaxHandler;

class Registration extends AbstractAjaxHandler {
	public function __construct() {
		$this->actions = array(
			'register_student' => array(
				'callback' => array( $this, 'register_student' )
			),
			'register_instructor' => array(
				'callback' => array( $this, 'register_instructor' )
			),
			'save_instructor_form_settings' => array(
				'callback' => array( $this, 'save_instructor_form_settings' )
			),
			'save_student_form_settings' => array(
				'callback' => array( $this, 'save_student_form_settings' )
			),
		);
	}

	public function register_student( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'first_name' => 'string',
			'last_name' => 'string',
			'username' => 'string',
			'email' => 'string',
			'password' => 'string',
		], $payload_data );

		$first_name = $payload['first_name'];
		$last_name  = $payload['last_name'];
		$username   = $payload['username'];
		$email      = $payload['email'];
		$password   = $payload['password'];

		$student_id = \Academy\Helper::insert_student( $email, $first_name, $last_name, $username, $password );

		if ( is_numeric( $student_id ) ) {
			do_action( 'academy/admin/after_student_registration', $student_id );
			$user_data = get_user_by( 'ID', $student_id );
			$args = (object) [
				'ID'            => $user_data->ID,
				'display_name'  => $user_data->data->display_name,
				'user_nicename' => $user_data->data->user_nicename,
				'user_email'    => $user_data->data->user_email,
			];
			// get instructor response data
			$instructor_data = \Academy\Helper::prepare_get_all_students_response( [ $args ] );
			foreach ( $instructor_data as $data ) {
				$user_data->data = $data;
			}
			wp_send_json_success( $user_data );
		}

		wp_send_json_error( $student_id );
	}

	public function register_instructor( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'first_name' => 'string',
			'last_name' => 'string',
			'username' => 'string',
			'email' => 'string',
			'password' => 'string',
		], $payload_data );

		$first_name = $payload['first_name'];
		$last_name  = $payload['last_name'];
		$username   = $payload['username'];
		$email      = $payload['email'];
		$password   = $payload['password'];

		$instructor = \Academy\Helper::insert_instructor( $email, $first_name, $last_name, $username, $password );

		if ( is_numeric( $instructor ) ) {
			do_action( 'academy/admin/after_instructor_registration', $instructor );
			$user_data = get_user_by( 'ID', $instructor );
			$args = (object) [
				'ID'            => $user_data->ID,
				'display_name'  => $user_data->data->display_name,
				'user_nicename' => $user_data->data->user_nicename,
				'user_email'    => $user_data->data->user_email,
			];
			// get instructor response data
			$instructor_data = \Academy\Helper::prepare_all_instructors_response( [ $args ] );
			foreach ( $instructor_data as $data ) {
				$user_data->data = $data;
			}
			wp_send_json_success( $user_data );
		}
		wp_send_json_error( $instructor );
	}


	public function save_instructor_form_settings( $payload_data ) {
		// Retrieve the JSON data sent via AJAX
		$json_data = isset( $payload_data['form_fields'] ) ? $payload_data['form_fields'] : '';
		$form_settings = get_option( 'academy_form_builder_settings' );
		$form_settings = json_decode( $form_settings, true );

		// Check if JSON data was received
		if ( ! empty( $json_data ) ) {
			// Decode the JSON string into a PHP array
			$json_data = json_decode( $json_data, true );
			if ( is_array( $json_data ) ) {
				$settings = [];
				foreach ( $json_data as $json_data_item ) {
					if ( is_array( $json_data_item ) ) {
						$fields = [];
						foreach ( $json_data_item as $key => $field_item ) {
							$fields[] = Sanitizer::sanitize_payload(array(
								'is_required' => 'boolean',
								'label' => 'string',
								'name' => 'string',
								'placeholder' => 'string',
								'type' => 'string',
							), $field_item);
							if ( $field_item['options'] ) {
								$options = [];
								foreach ( $field_item['options'] as $option ) {
									$sanitize_option = Sanitizer::sanitize_payload(array(
										'label' => 'string',
										'value' => 'string',
									), $option);
									$options[] = $sanitize_option;
								}
								$fields[ $key ]['options'] = $options;
							}
						}
						$settings[]['fields'] = $fields;
					}//end if
				}//end foreach
			}//end if
			$form_settings['instructor'] = $settings;
			update_option( 'academy_form_builder_settings', wp_json_encode( $form_settings ) );
		}//end if
		wp_send_json_success( isset( $form_settings['instructor'] ) ? $form_settings['instructor'] : [] );
	}

	public function save_student_form_settings( $payload_data ) {
		// Retrieve the JSON data sent via AJAX
		$json_data = isset( $payload_data['form_fields'] ) ? $payload_data['form_fields'] : '';
		$form_settings = get_option( 'academy_form_builder_settings' );
		$form_settings = json_decode( $form_settings, true );

		// Check if JSON data was received
		if ( ! empty( $json_data ) ) {
			// Decode the JSON string into a PHP array
			$json_data = json_decode( $json_data, true );
			if ( is_array( $json_data ) ) {
				$settings = [];
				foreach ( $json_data as $json_data_item ) {
					if ( is_array( $json_data_item ) ) {
						$fields = [];
						foreach ( $json_data_item as $key => $field_item ) {
							$fields[] = Sanitizer::sanitize_payload(array(
								'is_required' => 'boolean',
								'label' => 'string',
								'name' => 'string',
								'placeholder' => 'string',
								'type' => 'string',
							), $field_item);
							if ( $field_item['options'] ) {
								$options = [];
								foreach ( $field_item['options'] as $option ) {
									$sanitize_option = Sanitizer::sanitize_payload(array(
										'label' => 'string',
										'value' => 'string',
									), $option);
									$options[] = $sanitize_option;
								}
								$fields[ $key ]['options'] = $options;
							}
						}
						$settings[]['fields'] = $fields;
					}//end if
				}//end foreach
			}//end if
			$form_settings['student'] = $settings;
			update_option( 'academy_form_builder_settings', wp_json_encode( $form_settings ) );
		}//end if
		wp_send_json_success( isset( $form_settings['student'] ) ? $form_settings['student'] : [] );

	}
}
