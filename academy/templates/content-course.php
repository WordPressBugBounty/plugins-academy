<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


	$column_per_row = (array) Academy\Helper::get_settings( 'course_archive_courses_per_row', array(
		'desktop' => 3,
		'tablet' => 2,
		'mobile' => 1,
	));
	$grid_class = ! empty( $grid_class ) ? $grid_class : Academy\Helper::get_responsive_column( $column_per_row );
	?>
<div class="<?php echo esc_attr( $grid_class ); ?>">
	<div class="academy-course">
		<?php
			do_action( 'academy/templates/before_course_loop' );
			/**
			 * @hook - academy/templates/course_loop_header
			 *
			 * @Hooked - academy_course_loop_header - 10
			 */
			do_action( 'academy/templates/course_loop_header' );
			/**
			 * @hook - academy/templates/course_loop_content
			 *
			 * @Hooked - academy_course_loop_content - 11
			 */
			do_action( 'academy/templates/course_loop_content' );
			/**
			 * @hook - academy/templates/course_loop_footer
			 *
			 * @Hooked - academy_course_loop_footer - 12
			 */
			do_action( 'academy/templates/course_loop_footer' );
			do_action( 'academy/templates/after_course_loop_item' );
		?>
	</div>
</div>
