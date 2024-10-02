<?php
namespace Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Ajax\Course;
use Academy\Ajax\Lesson;
use Academy\Ajax\Tools;
use Academy\Ajax\Instructor;
use Academy\Ajax\Registration;
use Academy\Ajax\Student;
use Academy\Ajax\Miscellaneous;
use Academy\Ajax\Settings;

class Ajax {
	public static function init() {
		$self = new self();
		$self->dispatch_hooks();
	}
	public function dispatch_hooks() {
		( new Course() )->dispatch_actions();
		( new Lesson() )->dispatch_actions();
		( new Tools() )->dispatch_actions();
		( new Instructor() )->dispatch_actions();
		( new Registration() )->dispatch_actions();
		( new Student() )->dispatch_actions();
		( new Miscellaneous() )->dispatch_actions();
		( new Settings() )->dispatch_actions();
	}
}
