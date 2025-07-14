<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$prices_args = apply_filters( 'academy/shortcode/storeengine_enroll_form_prices_args', $course_id );

\Academy\Helper::get_template(
	'shortcode/academy-enroll-form/enroll-form.php',
	[
		'price'       => $prices_args['price'],
		'enroll_link' => get_the_permalink( $course_id ),
	]
);
