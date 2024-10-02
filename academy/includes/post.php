<?php
namespace Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Post\Settings;
use Academy\Post\Course;

class Post {
	public static function init() {
		$self = new self();
		$self->dispatch_hooks();
	}
	public function dispatch_hooks() {
		( new Settings() )->dispatch_actions();
		( new Course() )->dispatch_actions();
	}
}
