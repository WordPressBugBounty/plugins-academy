<?php
namespace AcademyMultiInstructor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ajax {
	public static function init() {
		$self = new self();
		$self->init_admin_request();
		$self->init_frontend_request();
	}

	public function init_admin_request() {
		Admin\Ajax::init();
	}
	public function init_frontend_request() {
		Frontend\Ajax::init();
	}
}
