<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$download_id = Academy\Helper::get_course_download_id( $course_id );
$download = new EDD_Download( $download_id );
$price = 0;
if ( $download ) {
	$price   = edd_price( $download_id, false );
}

\Academy\Helper::get_template(
	'shortcode/academy-enroll-form/enroll-form.php',
	[
		'price'         => $price,
		'enroll_link'   => get_the_permalink( $course_id ),
	]
);
