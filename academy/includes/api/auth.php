<?php
namespace Academy\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Helper;
use WP_REST_Response;
use WP_REST_Server;
use WP_REST_Request;
use Academy\Classes\Registration;

class Auth extends Registration {

	public static function init() {
		$self = new self();
		add_action( 'rest_api_init', array( $self, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			ACADEMY_PLUGIN_SLUG . '/v1',
			'/popup-login',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'render_popup_login' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'current_permalink' => array(
						'required'          => true,
						'type'              => 'string',
						'description'       => 'Current page URL to redirect after login.',
						'sanitize_callback' => 'esc_url_raw',
					),
				),
			)
		);

		register_rest_route(
			ACADEMY_PLUGIN_SLUG . '/v1',
			'/login',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'login_form_handler' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'username' => array(
						'required'          => true,
						'type'              => 'string',
						'description'       => 'Username or email for login.',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'password' => array(
						'required'          => true,
						'type'              => 'string',
						'description'       => 'Password for login.',
						'sanitize_callback' => null,
					),
					'remember' => array(
						'required'          => false,
						'type'              => 'boolean',
						'description'       => 'Whether to remember the user.',
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
					'login_redirect_url' => array(
						'required'          => false,
						'type'              => 'string',
						'description'       => 'URL to redirect after successful login.',
						'sanitize_callback' => 'esc_url_raw',
					),
				),
			)
		);

		register_rest_route(
			ACADEMY_PLUGIN_SLUG . '/v1',
			'/password-reset',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'password_reset_handler' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'username' => array(
						'required'          => true,
						'type'              => 'string',
						'description'       => 'Username or email for password reset.',
						'sanitize_callback' => 'sanitize_text_field',
					)
				),
			)
		);

		register_rest_route(
			ACADEMY_PLUGIN_SLUG . '/v1',
			'/register',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'registration_form_handler' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'password' => array(
						'required' => true,
						'type'     => 'string',
						'description' => 'Password for registration.',
						'sanitize_callback' => null,
					),
					'confirm-password' => array(
						'required' => true,
						'type'     => 'string',
						'description' => 'Confirmation of the password.',
						'sanitize_callback' => null,
					),
					'email' => array(
						'required' => true,
						'type'     => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'confirm-email' => array(
						'required' => false,
						'type'     => 'string',
						'sanitize_callback' => 'sanitize_email',
					),
					'role' => array(
						'required' => true,
						'type'     => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	public function render_popup_login( WP_REST_Request $request ) {

		if ( is_user_logged_in() ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'markup'  => '',
				),
				200
			);
		}

		$current_permalink = $request->get_param( 'current_permalink' );

		$register_url = esc_url(
			add_query_arg(
				array(
					'redirect_to' => $current_permalink,
				),
				\Academy\Helper::get_page_permalink( 'frontend_student_reg_page' )
			)
		);

		ob_start();

		echo do_shortcode(
			'[academy_login_form 
				form_title="' . esc_html__( 'Hi, Welcome back!', 'academy' ) . '" 
				show_logged_in_message="false" 
				student_register_url="' . esc_url( $register_url ) . '"
			login_redirect_url="' . esc_url( $current_permalink ) . '"]'
		);

		$markup = ob_get_clean();

		return new WP_REST_Response(
			array(
				'success' => true,
				'markup'  => $markup,
			),
			200
		);
	}

	public function login_form_handler( WP_REST_Request $request ) {

		$username = $request->get_param( 'username' );
		$password = $request->get_param( 'password' );
		$remember = $request->get_param( 'remember' );
		$login_redirect_url = $request->get_param( 'login_redirect_url' );
		$rechaptcha_response = ! empty( $request->get_param( 'g-recaptcha-response' ) ) ? $request->get_param( 'g-recaptcha-response' ) : '';

		// Allow login via email
		if ( is_email( $username ) ) {
			$user = get_user_by( 'email', $username );
			if ( $user ) {
				$username = $user->user_login;
			}
		}

		do_action( 'academy/api/auth/before_login_signon', $username, null, $rechaptcha_response );

		$user_signon = wp_signon(
			array(
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => $remember,
			),
			is_ssl()
		);

		if ( is_wp_error( $user_signon ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $user_signon->get_error_message(),
				),
				401
			);
		}

		wp_set_current_user( $user_signon->ID );

		do_action( 'set_current_user' );

		$redirect_url = ! empty( $login_redirect_url )
			? esc_url_raw( $login_redirect_url )
			: \Academy\Helper::get_page_permalink( 'frontend_dashboard_page' );

		return new WP_REST_Response(
			array(
				'success'      => true,
				'message'      => __( 'You have logged in successfully. Redirecting...', 'academy' ),
				'redirect_url' => esc_url( $redirect_url ),
			),
			200
		);
	}

	public function password_reset_handler( WP_REST_Request $request ) {
		$username = $request->get_param( 'username' );
		$rechaptcha_response = ! empty( $request->get_param( 'g-recaptcha-response' ) ) ? $request->get_param( 'g-recaptcha-response' ) : '';
		$ip  = $_SERVER['REMOTE_ADDR'] ?? '';
		$key = 'academy_reset_limit_' . md5( $ip . $username );

		if ( get_transient( $key ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Too many requests. Try later.', 'academy' ),
				),
				429
			);
		}

		set_transient( $key, 1, MINUTE_IN_SECONDS );

		$user = get_user_by( 'login', $username );

		if ( ! $user ) {
			$user = get_user_by( 'email', $username );
		}

		// Hide user existence
		if ( ! $user ) {
			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'If the account exists, you will receive an email.', 'academy' ),
				),
				200
			);
		}

		do_action( 'academy/api/auth/before_password_reset', $user, null, $rechaptcha_response );

		retrieve_password( $user->user_login );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'If the account exists, you will receive an email.', 'academy' ),
			),
			200
		);
	}

	public function registration_form_handler( WP_REST_Request $request ) {
		$params = $request->get_params();
		$response = $this->check_and_send_error( $params );

		if ( ! empty( $response ) && $response instanceof WP_REST_Response ) {
			return $response;
		}

		$role = $request->get_param( 'role' );

		if ( ! in_array( $role, array( 'student', 'instructor' ), true ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid role.', 'academy' ),
				),
				400
			);
		}

		return $this->process_user_registration( $role, $params );
	}

	private function process_user_registration( $role, $submitted_data ) {
		$rechaptcha_response = ! empty( $submitted_data['g-recaptcha-response'] ) ? $submitted_data['g-recaptcha-response'] : '';
		$form_fields = $this->get_form_fields( $role );

		list( $error, $user_data ) = $this->sanitize_and_validate_fields(
			$form_fields,
			$submitted_data
		);

		if ( ! empty( $error ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message'  => $error,
				),
				400
			);
		}

		list( $login_data, $user_meta ) = $this->login_data_and_user_meta_extractor( $user_data );

		// Assign student role explicitly
		if ( 'student' === $role ) {
			$login_data['role'] = 'academy_student';
		}

		do_action(
			'academy/api/auth/before_' . $role . '_registration',
			$login_data,
			$role,
			$rechaptcha_response
		);

		$user_id = wp_insert_user( $login_data );

		if ( is_wp_error( $user_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $user_id->get_error_message(),
				),
				400
			);
		}

		// Common meta
		update_user_meta(
			$user_id,
			'is_academy_' . $role,
			Helper::get_time()
		);

		// Instructor extra meta
		if ( 'instructor' === $role ) {
			update_user_meta(
				$user_id,
				'academy_instructor_status',
				apply_filters(
					'academy/admin/registration_instructor_status',
					'pending'
				)
			);
		}

		$this->save_meta_info( $user_meta, $user_id );

		do_action(
			'academy/api/auth/after_' . $role . '_registration',
			$user_id
		);

		$user = get_user_by( 'id', $user_id );

		if ( $user ) {
			wp_set_current_user( $user_id, $user->user_login );
			wp_set_auth_cookie( $user_id );
		}

		if ( apply_filters( 'academy/is_allow_new_' . $role . '_notification', true ) ) {
			wp_new_user_notification( $user_id, null, 'both' );
		}

		$referer_url = Helper::sanitize_referer_url( wp_get_referer() );

		$redirect_url = apply_filters(
			'academy/api/auth/after_register_' . $role . '_redirect',
			$referer_url
		);

		return new WP_REST_Response(
			array(
				'success'      => true,
				'message'      => __( 'Registration completed successfully. Redirecting...', 'academy' ),
				'redirect_url' => esc_url( $redirect_url ),
			),
			200
		);
	}
}
