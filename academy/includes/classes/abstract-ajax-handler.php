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
		$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';
		$action = explode( $this->namespace . '/', $action )[1];
		if ( ! isset( $this->actions[ $action ] ) ) {
			wp_send_json_error( 'Invalid AJAX action.' );
		}

		$details = $this->actions[ $action ];

		$nonce = isset( $_REQUEST['security'] ) ? sanitize_text_field( $_REQUEST['security'] ) : '';
		if ( empty( $nonce ) && isset( $_REQUEST['_wpnonce'] ) ) {
			$nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );
		}
		if ( ! wp_verify_nonce( $nonce, $this->nonce_action ) ) {
			wp_send_json_error( 'Invalid nonce.' );
		}

		$allow_visitor_action = isset( $details['allow_visitor_action'] ) ? $details['allow_visitor_action'] : false;
		if ( ! $allow_visitor_action && ( ! is_user_logged_in() || ! current_user_can( isset( $details['capability'] ) ? $details['capability'] : 'manage_options' ) ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		if ( is_callable( $details['callback'] ) ) {
			call_user_func( $details['callback'], wp_unslash( $_POST ) );
		} else {
			wp_send_json_error( 'Invalid callback method.' );
		}
	}

}
