<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_id = \Academy\Helper::get_course_product_id( $course_id );
$product    = wc_get_product( $product_id );
$price_html = '';
if ( $product ) {
	$price_html = $product->get_price_html();
}

\Academy\Helper::get_template(
	'shortcode/academy-enroll-form/enroll-form.php',
	[
		'price'         => $price_html,
		'enroll_link' => get_the_permalink( $course_id ),
	]
);
