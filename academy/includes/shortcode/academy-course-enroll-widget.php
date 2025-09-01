<?php
namespace Academy\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

class AcademyCourseEnrollWidget {

	public function __construct() {
		add_shortcode('academy_course_enroll_widget', [
			$this,
			'enroll_widget',
		]);
		add_shortcode('academy_course_enroll_widget_content', [
			$this,
			'enroll_widget_content',
		]);
	}

	public function enroll_widget( $attributes, $content = '' ) {
		ob_start();
		\Academy\Helper::get_template( 'shortcode/course-enroll-widget.php' );
		return apply_filters( 'academy/templates/shortcode/enroll_widget', ob_get_clean() );
	}

	public function enroll_widget_content( $attributes, $content = '' ) {
		ob_start();
		\Academy\Helper::get_template( 'shortcode/course-enroll-widget-content.php' );
		return apply_filters( 'academy/templates/shortcode/enroll_widget_content_args', ob_get_clean() );
	}
}
