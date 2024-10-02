<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class='academy-lessons-content-wrap academy-lessons-expanded-sidebar academy-lessons-content-scroll' >
	<div class='academy-lessons-content' >
		<?php
		if ( ! $type || ! $id || ! $course_id ) {
			\Academy\Helper::get_template( 'curriculums/not-found.php' );
		} else {
			do_action( 'academy/templates/curriculum/' . $type . '_content', $course_id, $id );
			// load the template for previous and next topic
			do_action( 'academy/templates/curriculum/previous_and_next_template' );
		}?>
	</div>
</div>
