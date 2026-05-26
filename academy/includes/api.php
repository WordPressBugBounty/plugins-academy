<?php
namespace Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class API {

	public static function init() {
		$self = new self();
		API\Course::init();
		API\Settings::init();
		API\Lessons::init();
		API\QuestionAnswer::init();
		API\CourseFilterHandler::init();
		API\Auth::init();
		API\Notes::init();
		add_action( 'rest_after_insert_academy_courses', array( $self, 'course_instructor_meta_data_save' ), 10, 2 );
		add_action( 'rest_prepare_academy_courses', [ $self, 'add_custom_meta_in_courses' ], 11, 2 );
	}
	public function course_instructor_meta_data_save( $post, $request ) {
		$params = $request->get_params();
		// make author as instructor
		$author = (int) isset( $params['author'] ) ? $params['author'] : 0;
		if ( ! \Academy\Helper::has_user_meta_exists( $author, 'academy_instructor_course_id', $post->ID ) ) {
			add_user_meta( $author, 'academy_instructor_course_id', $post->ID );
		}
		// mark as sub instructor
		if ( isset( $params['instructors'] ) && is_array( $params['instructors'] ) ) {
			foreach ( $params['instructors'] as $author_id ) {

				if ( $author === (int) $author_id ) {
					continue;
				}

				if ( ! \Academy\Helper::has_user_meta_exists( (int) $author_id, 'academy_instructor_course_id', $post->ID ) ) {
					add_user_meta( (int) $author_id, 'academy_instructor_course_id', $post->ID );
				}
			}
		}
	}

	public function add_custom_meta_in_courses( $response, $post ) {
		$is_enrolled = false;
		if ( \Academy\Helper::is_enrolled( $post->ID, get_current_user_id() ) ) {
			$is_enrolled = true;
		}
		$response->data['meta']['is_academy_course_enrolled'] = $is_enrolled;
		return $response;
	}
}
