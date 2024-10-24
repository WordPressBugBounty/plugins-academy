<?php
namespace AcademyCertificates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin {

	public static function init() {
		$self = new self();
		$self->dispatch_hooks();
	}
	public function dispatch_hooks() {
		add_filter( 'admin_init', array( $this, 'redirect_academy_certificate' ) );
	}
	public function redirect_academy_certificate() {
		global $pagenow;
		$post_type = isset( $_GET['post_type'] ) ? sanitize_key( $_GET['post_type'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'edit.php' === $pagenow && $post_type && 'academy_certificate' === $post_type ) {
			$new_url = admin_url( 'admin.php?page=academy-certificates' );
			wp_safe_redirect( $new_url );
			exit;
		}
	}
}
