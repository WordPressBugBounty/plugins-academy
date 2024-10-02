<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

	do_action( 'academy/templates/before_single_course' );

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

?>
	<div class="academy-col-lg-8">
	<?php
		/**
		 * @hook - academy/templates/single_course_content
		 *
		 * @hooked - academy_single_course_header - 10
		 * @Hooked - academy_single_course_description - 20
		 * @Hooked - academy_single_course_topics - 20
		 * @Hooked - academy_single_course_instructors - 15
		 * @Hooked - academy_single_course_feedback - 35
		 * @Hooked - academy_single_course_reviews - 40
		 * @Hooked - academy_single_course_additional_info - 25
		 */
		do_action( 'academy/templates/single_course_content' );
	?>
	</div>
	<?php
	do_action( 'academy/templates/after_single_course' );
