<?php
namespace AcademyMultiInstructor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyMultiInstructor\Post\Settings;

class Post {
	public static function init() {
		$self = new self();
		$self->dispatch_hooks();
	}
	public function dispatch_hooks() {
		( new Settings() )->dispatch_actions();
	}
}
