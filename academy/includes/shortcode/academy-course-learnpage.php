<?php

namespace Academy\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AcademyCourseLearnpage {
	public function __construct() {
		add_shortcode('academy_course_learnpage', [
			$this,
			'course_learnpage',
		]);
	}

	public function course_learnpage() {
		if ( ! \Academy\Helper::get_settings( 'is_enabled_lessons_php_render' ) ) {
			return false;
		}
		ob_start();

		\Academy\Helper::get_template( 'curriculums/course-learnpage.php' );

		return ob_get_clean();
	}
}
