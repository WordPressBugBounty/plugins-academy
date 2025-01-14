<?php
namespace Academy\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

class AcademySingleCourse {

	public function __construct() {
		add_shortcode('academy_single_course_addition_info', [
			$this,
			'single_course_additional_info',
		]);
		add_shortcode( 'academy_single_course_description', [
			$this,
			'single_course_description'
		]);
		add_shortcode('academy_single_course_curriculums', [
			$this,
			'single_course_curriculums',
		]);
		add_shortcode( 'academy_single_course_review_rating', [
			$this,
			'single_course_review_rating'
		]);
		add_shortcode( 'academy_single_course_review_form', [
			$this,
			'single_course_review_form'
		]);
	}

	public function single_course_additional_info( $attributes, $content = '' ) {
		ob_start();

		$audience     = \Academy\Helper::string_to_array( get_post_meta( get_the_ID(), 'academy_course_audience', true ) );
		$requirements = \Academy\Helper::string_to_array( get_post_meta( get_the_ID(), 'academy_course_requirements', true ) );
		$materials    = \Academy\Helper::string_to_array( get_post_meta( get_the_ID(), 'academy_course_materials_included', true ) );
		$tabs_nav     = [];
		$tabs_content = [];
		if ( is_array( $audience ) && count( $audience ) > 0 ) {
			$tabs_nav['audience']     = esc_html__( 'Targeted Audience', 'academy' );
			$tabs_content['audience'] = $audience;
		}
		if ( is_array( $requirements ) && count( $requirements ) > 0 ) {
			$tabs_nav['requirements']     = esc_html__( 'Requirements', 'academy' );
			$tabs_content['requirements'] = $requirements;
		}
		if ( is_array( $materials ) && count( $materials ) > 0 ) {
			$tabs_nav['materials']     = esc_html__( 'Materials Included', 'academy' );
			$tabs_content['materials'] = $materials;
		}

		\Academy\Helper::get_template(
			'single-course/additional-info.php',
			apply_filters(
				'academy/single_course_content_additional_info_args',
				[
					'tabs_nav'     => $tabs_nav,
					'tabs_content' => $tabs_content,
				]
			)
		);

		return apply_filters( 'academy/templates/shortcode/single_course_additional_info', ob_get_clean() );
	}

	public function single_course_description( $attributes, $content = '' ) {
		ob_start();

		\Academy\Helper::get_template(
			'single-course/description.php'
		);

		return apply_filters( 'academy/templates/shortcode/single_course_description', ob_get_clean() );
	}

	public function single_course_curriculums( $attributes, $content = '' ) {
		ob_start();

		$course_id = get_the_ID();
		$curriculums = \Academy\Helper::get_course_curriculum( $course_id, false );
		$topics_first_item_open_status = (bool) \Academy\Helper::get_settings( 'is_opened_course_single_first_topic', true );

		\Academy\Helper::get_template(
			'single-course/curriculums.php',
			array(
				'course_id'                      => $course_id,
				'curriculums'                    => $curriculums,
				'topics_first_item_open_status'  => $topics_first_item_open_status,
			)
		);

		return apply_filters( 'academy/templates/shortcode/single_course_curriculums', ob_get_clean() );
	}

	public function single_course_review_rating( $attributes, $content = '' ) {
		ob_start();
		$course_id = get_the_ID();
		if ( ! (bool) \Academy\Helper::get_settings( 'is_enabled_course_review', true ) || get_post_meta( $course_id, 'academy_is_disabled_course_review', true ) ) {
			return;
		}
		$rating = \Academy\Helper::get_course_rating( $course_id );
		\Academy\Helper::get_template(
			'single-course/feedback.php',
			array(
				'rating' => $rating
			)
		);

		return apply_filters( 'academy/templates/shortcode/single_course_review_rating', ob_get_clean() );
	}

	public function single_course_review_form( $attributes, $content = '' ) {
		ob_start();
		$course_id = get_the_ID();
		if ( ! (bool) \Academy\Helper::get_settings( 'is_enabled_course_review', true ) || get_post_meta( $course_id, 'academy_is_disabled_course_review', true ) ) {
			return;
		}

		\Academy\Helper::get_template(
			'single-course-reviews.php'
		);

		return apply_filters( 'academy/templates/shortcode/single_course_review_form', ob_get_clean() );
	}
}
