<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="academy-enroll-form-shortcode__price">
	<?php echo wp_kses_post( ucwords( $course_type ) ); ?>
</div>
<p class="academy-enroll-form-shortcode__notice">
	<?php esc_html_e( 'NOTE: It is a private course. If an admin manually enrolls you, then you will gain access to this course.', 'academy' ); ?>
</p>
