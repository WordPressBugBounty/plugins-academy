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
	function redirect_academy_certificate() {
		global $pagenow;
		if ( $pagenow == 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] === 'academy_certificate' ) {
			$new_url = admin_url( 'admin.php?page=academy-certificates' );
			wp_safe_redirect( $new_url );
			exit;
		}
	}
}
