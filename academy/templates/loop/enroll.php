<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="academy-course__enroll">
	<i class="academy-icon academy--shopping-cart" aria-hidden="true"></i>
	<a href="<?php echo esc_url( get_the_permalink() ); ?>"><?php esc_html_e( 'Get Enrolled', 'academy' ); ?></a>
</div>

