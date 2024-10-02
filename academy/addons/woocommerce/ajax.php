<?php

namespace AcademyWoocommerce;

use AcademyWoocommerce\Ajax\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit(); // Exit if accessed directly.
}

class Ajax {
	public static function init() {
		$self = new self();
		$self->dispatch_hooks();
	}

	public function dispatch_hooks() {
		( new Admin() )->dispatch_actions();
	}
}
