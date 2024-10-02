<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

	global $wp_query;

	$args = array(
		'name'        => get_option( 'academy_current_course_name' ),
		'post_type'   => 'academy_courses',
		'post_status' => 'publish',
		'numberposts' => 1
	);
	$course = current( get_posts( $args ) );
	$course_id = $course->ID;

		$lesson_page = get_post( \Academy\Helper::get_settings( 'lessons_page' ) );
	if ( $lesson_page ) {
		$content = apply_filters( 'the_content', $lesson_page->post_content );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $content;
	}
