<?php
/**
 * Course grid for the [academy_enrolled_courses] shortcode.
 *
 * Runs against the query the shortcode has already set up, so the loop and
 * pagination behave exactly as they do on the course archive.
 *
 * This template can be overridden by copying it to
 * yourtheme/academy/shortcode/academy-enrolled-courses/courses.php
 *
 * @var string $grid_class     Responsive column classes for each card.
 * @var bool   $has_pagination Whether to render pagination below the grid.
 * @var int    $paged          Current page number.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="academy-courses academy-courses--grid academy-enrolled-courses">
	<div class="academy-courses__body">
		<div class="academy-row">
			<?php
			do_action( 'academy/templates/before_enrolled_course_loop' );

			if ( have_posts() ) {
				while ( have_posts() ) {
					the_post();
					\Academy\Helper::get_template( 'content-course.php', array( 'grid_class' => $grid_class ) );
				}
				wp_reset_postdata();

				if ( $has_pagination ) {
					\Academy\Helper::get_template( 'archive/pagination.php', array( 'paged' => $paged ) );
				}
			} else {
				\Academy\Helper::get_template( 'archive/course-none.php' );
			}

			do_action( 'academy/templates/after_enrolled_course_loop' );
			?>
		</div>
	</div>
</div>
