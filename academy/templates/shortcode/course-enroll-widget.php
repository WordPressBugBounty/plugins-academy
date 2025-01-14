<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<div class="academy-widget-enroll academy-sticky-widget">
	<?php
		do_action( 'academy/templates/single_course_enroll_before' );
		do_action( 'academy/templates/single_course_enroll_content' );
		do_action( 'academy/templates/single_course_enroll_after' );
	?>
</div>
