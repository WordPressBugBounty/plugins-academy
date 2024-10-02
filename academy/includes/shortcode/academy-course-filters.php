<?php
namespace  Academy\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AcademyCourseFilters {
	public function __construct() {
		add_shortcode( 'academy_course_filters', array( $this, 'academy_course_filters' ) );
	}
	public function academy_course_filters( $atts, $content = '' ) {
		ob_start();
		echo '<div class="academy-course-filters">';
			do_action( 'academy/templates/archive/course_sidebar_content' );
		echo '</div>';
		return apply_filters( 'academy/templates/shortcode/course_filters', ob_get_clean() );
	}
}
