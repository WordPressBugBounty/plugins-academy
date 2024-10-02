<?php
/**
 * The Template for displaying all single courses
 *
 * This template can be overridden by copying it to yourtheme/academy/single-course.php.
 *
 * the readme will list any important changes.
 *
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

	academy_get_header( 'course' );

		/**
		 * @hook - academy/templates/before_main_content
		 */
		do_action( 'academy/templates/before_main_content', 'single-course.php' );
?>

		<div class="academy-single-course">
			<div class="academy-container">
				<div class="academy-row">
					<?php while ( have_posts() ) : ?>
						<?php the_post(); ?>

						<?php Academy\Helper::get_template_part( 'content', 'single-course' ); ?>


					<?php endwhile; // end of the loop. ?>

					<?php
						/**
						 * @hook - academy/templates/single_course_sidebar
						 *
						 * @hooked academy_single_course_sidebar  - 10
						 */
						do_action( 'academy/templates/single_course_sidebar' );
					?>
				</div>
			</div>
		</div>
	<?php
		/**
		 * @hook - academy/templates/after_main_content
		 */
		do_action( 'academy/templates/after_main_content', 'single-course.php' );
	?>

<?php
	academy_get_footer( 'course' );
