<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="academy-widget-enroll academy-sticky-widget">
	<?php
		do_action( 'academy/templates/single_course_enroll_before' );
	?>
	<div class="academy-widget-enroll__content">
		<?php
			do_action( 'academy/templates/single_course_enroll_content' );
		?>
	</div>
	<?php
		do_action( 'academy/templates/single_course_enroll_after' );
	?>
</div>
