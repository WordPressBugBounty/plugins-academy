<?php
namespace  Academy\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AcademyEnrollForm {

	public function __construct() {
		add_shortcode( 'academy_enroll_form', array( $this, 'enroll_form' ) );
	}

	public function enroll_form( $atts ) {
		$attributes = shortcode_atts(array(
			'ID'                        => '',
			'course_id'                => '',
		), $atts);

		$course_id = (int) isset( $attributes['course_id'] ) ? $attributes['course_id'] : $attributes['ID'];
		ob_start();
		echo '<div class="academy-enroll-form">';
		if ( $course_id ) {
			do_action( 'academy/templates/shortcode/enroll_form_content', $course_id );
		} else {
			echo esc_html__( 'course_id attribute is required.', 'academy' );
		}
		echo '</div>';
		return apply_filters( 'academy/templates/shortcode/enroll_form', ob_get_clean() );
	}
}


