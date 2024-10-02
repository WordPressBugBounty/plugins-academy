<?php
namespace Academy\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class AbstractPostHandler {
	protected $nonce_action = 'academy_nonce';
	protected $namespace = ACADEMY_PLUGIN_SLUG;
	protected $is_admin = true;
	protected $actions = array();

	public function dispatch_actions() {
		foreach ( $this->actions as $action => $details ) {
			add_action( 'admin_post_' . $this->namespace . '/' . $action, array( $this, 'handle_admin_post_request' ) );
		}
	}

	public function handle_admin_post_request() {
		$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';
		$action = explode( $this->namespace . '/', $action )[1];
		if ( ! isset( $this->actions[ $action ] ) ) {
			wp_die( 'Invalid POST action.' );
		}

		$details = $this->actions[ $action ];

		$nonce = isset( $_REQUEST['security'] ) ? sanitize_text_field( $_REQUEST['security'] ) : '';
		if ( empty( $nonce ) && isset( $_REQUEST['_wpnonce'] ) ) {
			$nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );
		}
		if ( ! wp_verify_nonce( $nonce, $this->nonce_action ) ) {
			wp_die( 'Invalid nonce.' );
		}
		if ( ! is_user_logged_in() || ! current_user_can( isset( $details['capability'] ) ? $details['capability'] : 'manage_options' ) ) {
			wp_die( 'Insufficient permissions.' );
		}

		if ( is_callable( $details['callback'] ) ) {
			call_user_func( $details['callback'] );
		} else {
			wp_die( 'Invalid callback method.' );
		}
	}

}
