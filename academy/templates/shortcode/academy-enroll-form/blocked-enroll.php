<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="academy-enroll-form-shortcode__price">
	<?php echo wp_kses_post( ucwords( $course_type ) ); ?>
</div>
<p class="academy-enroll-form-shortcode__notice">
	<span><?php esc_html_e( '100% Booked', 'academy' ); ?></span>
	<?php esc_html_e( 'Closed for Enrollment', 'academy' ); ?>
</p>
