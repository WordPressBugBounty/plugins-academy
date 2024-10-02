<?php
namespace AcademyMultiInstructor\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyMultiInstructor\Ajax\Frontend;

class Ajax {
	public static function init() {
		$self = new self();
		$self->dispatch_hooks();
	}

	public function dispatch_hooks() {
		( new Frontend() )->dispatch_actions();
	}

}
