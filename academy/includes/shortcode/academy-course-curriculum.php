<?php
namespace Academy\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

class AcademyCourseCurriculum {

	public function __construct() {
		add_shortcode('academy_course_curriculum_topbar', [
			$this,
			'course_topbar',
		]);
		add_shortcode('academy_course_curriculums', [
			$this,
			'course_curriculums',
		]);
		add_shortcode('academy_course_curriculum_content', [
			$this,
			'curriculum_content',
		]);
		add_shortcode( 'academy_course_announcements', [
			$this,
			'course_announcements',
		]);
		add_shortcode( 'academy_course_questions_answers', [
			$this,
			'course_questions_answers',
		]);

	}

	public function course_topbar( $attributes, $content = '' ) {
		ob_start();

		\Academy\Helper::get_template('curriculums/topbar.php', [
			'course_id' => \Academy\Helper::get_the_current_course_id(),
		]);

		return apply_filters( 'academy/templates/shortcode/course_topbar', ob_get_clean() );
	}

	public function course_curriculums( $attributes, $content = '' ) {
		$course_id = \Academy\Helper::get_the_current_course_id();
		$curriculums = \Academy\Helper::get_course_curriculum( $course_id, true );
		ob_start();
		\Academy\Helper::get_template('curriculums/course-curriculums.php', [
			'curriculums' => $curriculums,
		]);
		return apply_filters( 'academy/templates/shortcode/course_curriculums', ob_get_clean() );
	}


	public function curriculum_content( $attributes, $content = '' ) {
		$slug = get_query_var( 'name' );
		$type = get_query_var( 'curriculum_type' );
		$id = \Academy\Helper::get_topic_id_by_topic_name_and_topic_type(
			$slug,
			$type
		);

		ob_start();

		\Academy\Helper::get_template('curriculums/content.php', [
			'id' => $id,
			'type' => $type,
			'course_id' => \Academy\Helper::get_the_current_course_id(),
		]);

		return apply_filters( 'academy/templates/shortcode/course_curriculum_content', ob_get_clean() );
	}

	public function course_announcements( $attributes, $content = '' ) {
		$course_id = \Academy\Helper::get_the_current_course_id();
		$announcements = \Academy\Helper::get_course_announcements_by_course_id( $course_id );

		ob_start();

		\Academy\Helper::get_template('curriculums/announcements.php', [
			'announcements' => $announcements,
		]);

		return apply_filters( 'academy/templates/shortcode/course_announcements', ob_get_clean() );
	}

	public static function course_questions_answers( $attributes, $content = '' ) {
		$course_id = \Academy\Helper::get_the_current_course_id();
		$qas = \Academy\Helper::get_course_qas( $course_id );

		ob_start();

		\Academy\Helper::get_template( 'curriculums/questions-answers.php', [
			'qas' => $qas,
		]);

		return apply_filters( 'academy/templates/shortcode/course_questions_answers', ob_get_clean() );
	}
}
