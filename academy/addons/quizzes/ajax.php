<?php
namespace AcademyQuizzes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ajax {
	public static function init() {
		Admin\Ajax::init();
		Frontend\Ajax::init();
	}
}
