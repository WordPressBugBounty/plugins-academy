<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="academy-course__price">
	<?php

	$course_price = '';

	if ( $is_paid && $price ) {
		$course_price = wp_kses_post( $price );
	} elseif ( $is_paid ) {
		$course_price = esc_html__( 'Paid', 'academy' );
	} elseif ( 'public' === $course_type ) {
		$course_price = esc_html__( 'Public', 'academy' );
	} else {
		$course_price = esc_html__( 'Free', 'academy' );
	}

	$course_price = apply_filters( 'academy/templates/loop/price', $course_price, get_the_ID() );
	echo wp_kses_post( $course_price );
	?>
</div>
