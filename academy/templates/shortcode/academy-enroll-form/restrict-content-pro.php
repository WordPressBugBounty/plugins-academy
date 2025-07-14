<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

\Academy\Helper::get_template( 'shortcode/academy-enroll-form/enroll-form.php', [
	'price'        => __( 'Paid', 'academy' ),
	'enroll_link'   => get_the_permalink( $course_id ),
] );
