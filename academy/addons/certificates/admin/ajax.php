<?php

namespace AcademyCertificates\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyCertificates\Ajax\Admin;

class Ajax {
	public static function init() {
		$self = new self();
		$self->dispatch_hooks();
	}

	public function dispatch_hooks() {
		( new Admin() )->dispatch_actions();
	}
}
