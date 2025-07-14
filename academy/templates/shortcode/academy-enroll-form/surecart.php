<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use SureCart\Support\Currency;
$integrations = apply_filters( 'academy/surecart/get_course_prices', $course_id );

$price = '';

if ( is_array( $integrations ) && ! empty( $integrations ) ) {
	$prices = array_map(
		fn( $integration ) => (int) ( $integration->amount ?? 0 ),
		$integrations
	);

	$currency = $integrations[0]->price->currency ?? 'USD';

	$min = min( $prices );
	$max = max( $prices );

	$price = $min === $max
		? Currency::format( $min, $currency )
		: Currency::format( $min, $currency ) . ' - ' . Currency::format( $max, $currency );
}

\Academy\Helper::get_template(
	'shortcode/academy-enroll-form/enroll-form.php',
	[
		'price'       => $price,
		'enroll_link' => get_the_permalink( $course_id ),
	]
);
