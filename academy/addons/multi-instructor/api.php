<?php
namespace AcademyMultiInstructor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class API {
	public static function init() {
		$self = new self();
		add_filter( 'rest_academy_courses_query', array( $self, 'allow_multi_instructor_courses' ), 10 );
	}
	public function allow_multi_instructor_courses( $args ) {
		if ( ! current_user_can( 'manage_options' ) && current_user_can( 'manage_academy_instructor' ) ) {
			$user_id = get_current_user_id();
			$course_ids = \Academy\Helper::get_course_ids_by_instructor_id( $user_id );
			if ( $course_ids ) {
				$args['author__in'] = '';
				$args['post__in'] = $course_ids;
			}
		}
		return $args;
	}
}
