<?php
namespace Academy\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

class AcademyCourseInstructors {

	public function __construct() {
		add_shortcode('academy_course_instructors', [
			$this,
			'course_instructors',
		]);
	}

	public function course_instructors( $attributes, $content = '' ) {
		global $post;
		$author_id = $post->post_author;
		if ( \Academy\Helper::get_addon_active_status( 'multi_instructor' ) ) {
			$instructors = (array) \Academy\Helper::get_instructors_by_course_id( $post->ID );
		} else {
			$instructors = (array) \Academy\Helper::get_instructor_by_author_id( $author_id );
		}
		$instructor_reviews_status = (bool) \Academy\Helper::get_settings( 'is_enabled_instructor_review', true );
		if ( $instructors ) {
			ob_start();
			\Academy\Helper::get_template(
				'single-course/instructors.php',
				apply_filters(
					'academy/single_course_content_instructors_args',
					[
						'instructors' => $instructors,
						'instructor_reviews_status' => $instructor_reviews_status,
					]
				)
			);
			return apply_filters( 'academy/templates/shortcode/course_instructors', ob_get_clean() );
		}
	}
}
