<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="academy-widget-enroll__notice <?php echo 'academy-widget-enroll__notice--' . esc_attr( $status ); ?>">
	<?php
		/* translators: %s is a placeholder for enrollment status */
		echo sprintf( esc_html__( 'Your Enrollment Status is %s. Wait for admin approval.', 'academy' ), wp_kses_post( '<strong>' . $status . '</strong>' ) );
	?>
</div>
