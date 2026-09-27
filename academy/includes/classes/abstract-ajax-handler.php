<?php
namespace Academy\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class AbstractAjaxHandler {
	protected $nonce_action = 'academy_nonce';
	protected $namespace = ACADEMY_PLUGIN_SLUG;
	protected $is_admin = true;
	protected $actions = array();

	public function dispatch_actions() {
		foreach ( $this->actions as $action => $details ) {
			add_action( 'wp_ajax_' . $this->namespace . '/' . $action, array( $this, 'handle_ajax_request' ) );
			if ( isset( $details['allow_visitor_action'] ) && true === $details['allow_visitor_action'] ) {
				add_action( 'wp_ajax_nopriv_' . $this->namespace . '/' . $action, array( $this, 'handle_ajax_request' ) );
			}
		}
	}

	public function handle_ajax_request() {
		$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';
		$action = explode( $this->namespace . '/', $action )[1];
		if ( ! isset( $this->actions[ $action ] ) ) {
			wp_send_json_error( 'Invalid AJAX action.', 400 );
		}

		$details = $this->actions[ $action ];

		$nonce = isset( $_REQUEST['security'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['security'] ) ) : '';
		if ( empty( $nonce ) && isset( $_REQUEST['_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );
		}
		if ( ! wp_verify_nonce( $nonce, $this->nonce_action ) ) {
			wp_send_json_error( 'Invalid nonce.', 400 );
		}

		$allow_visitor_action = isset( $details['allow_visitor_action'] ) ? $details['allow_visitor_action'] : false;
		$capability           = isset( $details['capability'] ) ? $details['capability'] : 'manage_options';

		if ( ! $allow_visitor_action && ( ! is_user_logged_in() || ! current_user_can( $capability ) ) ) {
			wp_send_json_error( 'Insufficient permissions.', 401 );
		}

		/**
		 * Fires after the base capability + nonce checks pass, letting addons
		 * approve or deny this specific (handler, action) pair per-user without
		 * modifying this class. Return `true` to allow (the default — no
		 * behavior change unless something hooks this), a WP_Error to deny with
		 * a specific message, or any other non-true value to deny generically.
		 *
		 * @param true|WP_Error $authorized Whether the request is authorized.
		 * @param array{handler:object,action:string,namespace:string,capability:string,is_visitor:bool} $context
		 */
		$authorized = apply_filters(
			'academy/request/authorize',
			true,
			array(
				'handler'    => $this,
				'action'     => $action,
				'namespace'  => $this->namespace,
				'capability' => $capability,
				'is_visitor' => $allow_visitor_action,
			)
		);

		if ( is_wp_error( $authorized ) ) {
			wp_send_json_error( $authorized->get_error_message(), 403 );
		}

		if ( true !== $authorized ) {
			wp_send_json_error( 'Insufficient permissions.', 401 );
		}

		if ( is_callable( $details['callback'] ) ) {
			call_user_func( $details['callback'], wp_unslash( $_POST ) );
		} else {
			wp_send_json_error( 'Invalid callback method.', 500 );
		}
	}
}
