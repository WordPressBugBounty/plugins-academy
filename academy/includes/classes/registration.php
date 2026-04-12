<?php
namespace Academy\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Registration {

	protected function get_form_fields( string $type ): array {
		return json_decode(
			get_option( 'academy_form_builder_settings' ),
			true
		)[ $type ];
	}

	protected function check_and_send_error( $post_data ) {

		if ( ! get_option( 'users_can_register' ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__(
						'Sorry, Admin disabled new user registration',
						'academy'
					),
				),
				403
			);
		}

		if (
			( isset( $post_data['password'] ) || isset( $post_data['confirm-password'] ) ) &&
			$post_data['password'] !== $post_data['confirm-password']
		) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__(
						'Password and confirm password did not match',
						'academy'
					),
				),
				400
			);
		}

		if ( ! empty( $post_data['email'] ) && get_user_by( 'email', $post_data['email'] ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'Email already exists', 'academy' ),
				),
				409
			);
		}

		if ( empty( $post_data['email'] ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'Email is missing or Invalid.', 'academy' ),
				),
				400
			);
		}

		if (
			! empty( $post_data['confirm-email'] ) &&
			$post_data['confirm-email'] !== $post_data['email']
		) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'Your confirm email did not match.', 'academy' ),
				),
				400
			);
		}

		return true;
	}

	protected function sanitize_and_validate_fields(
		$instructor_form_fields,
		$submitted_data
	): array {
		$error = [];
		$user_data = [];

		foreach ( $instructor_form_fields as $field ) {
			foreach ( $field['fields'] as $column ) {
				if ( 'button' === $column['name'] ) {
					continue;
				}

				if (
					'checkbox' === $column['type'] &&
					isset( $submitted_data[ $column['name'] ] ) && is_array( $submitted_data[ $column['name'] ] )
				) {
					$submitted_data[ $column['name'] ] = implode(
						',',
						$submitted_data[ $column['name'] ]
					);
				}

				$field_value = isset( $submitted_data[ $column['name'] ] ) ? sanitize_text_field( $submitted_data[ $column['name'] ] ) : '';

				if ( $column['is_required'] && empty( $field_value ) ) {
					$error = sprintf(
						__( '%s is required.', 'academy' ),
						$column['label']
					);
				}

				$user_data[ sanitize_key( $column['name'] ) ] = $field_value;
			}//end foreach
		}//end foreach
		return [ $error, $user_data ];
	}

	protected function login_data_and_user_meta_extractor(
		array $user_meta
	): array {
		$login_data = [
			'user_login' => explode( '@', $user_meta['email'] )[0],
			'user_email' => $user_meta['email'],
			'first_name' => $user_meta['first-name'] ?? '',
			'last_name' => $user_meta['last-name'] ?? '',
		];

		if ( isset( $user_meta['password'] ) && ! empty( $user_meta['password'] ) ) {
			$login_data['user_pass'] = $user_meta['password'];
		}

		unset(
			$user_meta['file'],
			$user_meta['password'],
			$user_meta['confirm-password'],
			$user_meta['first-name'],
			$user_meta['last-name'],
			$user_meta['email'],
			$user_meta['redirect_url']
		);

		return [ $login_data, $user_meta ];
	}

	protected function save_meta_info( $user_meta, $user_id ): void {
		// Handle Phone Number
		if ( ! empty( $user_meta['phone-number'] ) ) {
			if ( get_user_meta( $user_id, 'academy_phone_number' ) ) {
				update_user_meta( $user_id, 'academy_phone_number', $user_meta['phone-number'] );
			} else {
				add_user_meta( $user_id, 'academy_phone_number', $user_meta['phone-number'] );
			}
		}
		// Handle other meta
		foreach ( $user_meta as $key => $value ) {
			if ( 'phone-number' === $key ) {
				continue;
			}
			if ( get_user_meta( $user_id, 'academy_' . $key ) ) {
				update_user_meta( $user_id, 'academy_' . $key, $value );
			} else {
				add_user_meta( $user_id, 'academy_' . $key, $value );
			}
		}
	}

}
