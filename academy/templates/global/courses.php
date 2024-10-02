<div class="academy-courses__body">
	<div class="academy-row">
		<?php
			do_action( 'academy/templates/before_course_loop' );
		if ( have_posts() ) {
			// Load posts loop.
			while ( have_posts() ) {
				the_post();
				/**
				 * Hook: academy/templates/course_loop.
				 */
				do_action( 'academy/templates/course_loop' );

				Academy\Helper::get_template_part( 'content', 'course' );
			}

			/**
			 * Hook: academy/templates/after_course_loop
			 *
			 * @Hooked: academy_course_pagination - 10
			 */
			do_action( 'academy/templates/after_course_loop' );

		} else {
			// If no content, include the "No posts found" template.
			/**
			 * Hook: academy/templates/no_course_found
			 *
			 * @Hooked: academy_no_course_found - 10
			 */
			do_action( 'academy/templates/no_course_found' );
		}//end if
		?>
	</div>
</div>
