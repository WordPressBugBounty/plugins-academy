<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! $enrolled ) :
	?>

<div class="academy-widget-enroll__head">
	<?php
	if ( $is_paid ) {
		if ( $price ) {
			echo '<div class="title">' . esc_html__( 'Onetime', 'academy' ) . '</div>';
			echo '<div class="academy-course-price">' . wp_kses_post( $price ) . '</div>';
		} else {
			echo '<div class="academy-course-type">' . esc_html__( 'Paid', 'academy' ) . '</div>';
		}
	} elseif ( $is_public ) {
		echo '<div class="academy-course-type">' . esc_html__( 'Public', 'academy' ) . '</div>';
	} else {
		echo '<div class="academy-course-type">' . esc_html__( 'Free', 'academy' ) . '</div>';
	}
	?>
</div>
	<?php endif; ?>
