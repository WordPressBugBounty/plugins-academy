<?php
namespace  Academy\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use SureCart\Models;

class AcademyEnrollForm {

	public function __construct() {
		add_shortcode( 'academy_enroll_form', array( $this, 'enroll_form' ) );
	}

	public function enroll_form( $atts ) {
		$attributes = shortcode_atts(array(
			'ID'                        => '',
			'course_id'                 => '',
			'layout'                    => 'legacy',
		), $atts);

		$course_id = (int) isset( $attributes['course_id'] ) ? $attributes['course_id'] : $attributes['ID'];
		$layout = isset( $attributes['layout'] ) ? $attributes['layout'] : 'legacy';
		ob_start();

		if ( 'legacy' === $layout ) {
			echo '<div class="academy-enroll-form-shortcode academy-enroll-form-shortcode--legacy">';
			if ( $course_id ) {
				do_action( 'academy/templates/shortcode/enroll_form_content', $course_id );
			} else {
				echo esc_html__( 'course_id attribute is required.', 'academy' );
			}
			echo '</div>';
		} else {
			echo '<div class="academy-enroll-form-shortcode">';
			$enrolled  = \Academy\Helper::is_enrolled( $course_id, get_current_user_id(), 'any' );
			$course_type      = \Academy\Helper::get_course_type( $course_id );
			$is_public_course = \Academy\Helper::is_public_course( $course_id );
			$prerequisite_courses     = apply_filters( 'academy/templates/single_course/prerequisite_courses', false, $course_id );
			$monetize_engine = \Academy\Helper::monetization_engine();
			$is_surecart_integration = \Academy\Helper::is_plugin_active( 'surecart/surecart.php' ) ? Models\Integration::where( 'integration_id', $course_id )->andWhere( 'model_name', 'product' )->get() : '';
			if ( ( $enrolled || $is_public_course ) && empty( $prerequisite_courses ) && ! $is_surecart_integration ) {
				\Academy\Helper::get_template( 'shortcode/academy-enroll-form/start-course.php', [
					'enrolled' => $enrolled,
					'course_id' => $course_id,
					'course_type' => $course_type,
					'is_public_course' => $is_public_course
				] );
			} elseif ( $prerequisite_courses ) {
				\Academy\Helper::get_template( 'shortcode/academy-enroll-form/course-prerequisite.php', [
					'required_courses' => $prerequisite_courses,
					'course_type' => $course_type,
					'is_free' => 'free' === $course_type ? true : false
				] );
			} elseif ( 'private' === get_post_status( $course_id ) ) {
				\Academy\Helper::get_template( 'shortcode/academy-enroll-form/private-course.php', [ 'course_type' => $course_type ] );
			} elseif ( \Academy\Helper::is_course_fully_booked( $course_id ) ) {
				\Academy\Helper::get_template( 'shortcode/academy-enroll-form/blocked-enroll.php', [ 'course_type' => $course_type ] );
			} else {
				\Academy\Helper::get_template( 'shortcode/academy-enroll-form/monetize-engine.php', [
					'course_id' => $course_id,
					'monetize_engine'  => $monetize_engine,
					'is_surecart_integration' => $is_surecart_integration
				] );
			}//end if
			echo '</div>';
		}//end if
		return apply_filters( 'academy/templates/shortcode/enroll_form', ob_get_clean() );
	}
}


