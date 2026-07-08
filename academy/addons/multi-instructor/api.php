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
		// Administrators may list every course. Instructors are scoped to their own
		// courses. The previous implementation decided "admin" from the HTTP_REFERER
		// containing "/wp-admin/", which is attacker-spoofable and let an instructor
		// bypass the per-instructor scoping by forging the referer header.
		if ( current_user_can( 'manage_options' ) ) {
			return $args;
		}
		if ( current_user_can( 'manage_academy_instructor' ) ) {
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
