<?php
namespace AcademyMultiInstructor\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyMultiInstructor\Ajax\Instructor;
use AcademyMultiInstructor\Ajax\Withdraw;
use AcademyMultiInstructor\Ajax\FakeOrder;

class Ajax {
	public static function init() {
		$self = new self();
		$self->dispatch_hooks();
	}

	public function dispatch_hooks() {
		( new Instructor() )->dispatch_actions();
		( new Withdraw() )->dispatch_actions();
		( new FakeOrder() )->dispatch_actions();
	}
}
