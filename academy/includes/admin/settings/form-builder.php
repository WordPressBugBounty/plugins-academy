<?php
namespace Academy\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FormBuilder {
	public static function get_saved_data() {
		$settings = get_option( 'academy_form_builder_settings' );
		if ( $settings ) {
			return json_decode( $settings, true );
		}
		return [];
	}
	public static function get_default_data() {
		return apply_filters('academy/admin/settings/form_builder_default_data', array(
			'student' => [
				[
					'fields' => [
						[
							'is_required' => true,
							'label' => __( 'Email', 'academy' ),
							'name' => 'email',
							'placeholder' => __( 'Enter Email Address', 'academy' ),
							'type' => 'text'
						],
					],
				],
				[
					'fields' => [
						[
							'is_required' => true,
							'label' => __( 'Password', 'academy' ),
							'name' => 'password',
							'placeholder' => __( 'Enter Password', 'academy' ),
							'type' => 'password'
						],
						[
							'is_required' => true,
							'label' => __( 'Confirm Password', 'academy' ),
							'name' => 'confirm-password',
							'placeholder' => __( 'Enter Confirm Password', 'academy' ),
							'type' => 'password'
						]
					]
				],
				[
					'fields' => [
						[
							'is_required' => true,
							'label' => __( 'Register as Student', 'academy' ),
							'name' => 'button',
							'type' => 'button'
						],
					],
				],
			],
			'instructor' => [
				[
					'fields' => [
						[
							'is_required' => true,
							'label' => __( 'Email', 'academy' ),
							'name' => 'email',
							'placeholder' => __( 'Enter Email Address', 'academy' ),
							'type' => 'text'
						],
					],
				],
				[
					'fields' => [
						[
							'is_required' => true,
							'label' => __( 'Password', 'academy' ),
							'name' => 'password',
							'placeholder' => __( 'Enter Password', 'academy' ),
							'type' => 'password'
						],
						[
							'is_required' => true,
							'label' => __( 'Confirm Password', 'academy' ),
							'name' => 'confirm-password',
							'placeholder' => __( 'Enter Confirm Password', 'academy' ),
							'type' => 'password'
						]
					]
				],
				[
					'fields' => [
						[
							'is_required' => true,
							'label' => __( 'Register as Instructor', 'academy' ),
							'name' => 'button',
							'type' => 'button'
						],
					],
				],
			],
		));
	}

	public static function save_settings( $form_data = false ) {
		$default_data = self::get_default_data();
		$saved_data = self::get_saved_data();
		$settings_data = wp_parse_args( $saved_data, $default_data );
		if ( $form_data ) {
			$settings_data = wp_parse_args( $form_data, $settings_data );
		}
		// if settings already saved, then update it
		if ( count( $saved_data ) ) {
			return update_option( 'academy_form_builder_settings', wp_json_encode( $settings_data ) );
		}
		return add_option( 'academy_form_builder_settings', wp_json_encode( $settings_data ) );
	}
}
