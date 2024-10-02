<?php
namespace AcademyMigrationTool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyMigrationTool\Ajax\Integration;

class Ajax {
	public static function init() {
		$self = new self();
		$self->dispatch_hooks();
	}

	public function dispatch_hooks() {
		( new Integration() )->dispatch_actions();
	}
}
