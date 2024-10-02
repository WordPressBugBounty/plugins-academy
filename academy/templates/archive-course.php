<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

	academy_get_header( 'course' );

	$sidebar_position = \Academy\Helper::get_settings( 'course_archive_sidebar_position', 'right' );
?>

<?php
	/**
	 * @hook - academy/templates/before_main_content
	 */
	do_action( 'academy/templates/before_main_content', 'archive-course.php' );
?>

<div class="academy-courses academy-courses--archive">
	<div class="academy-container">
		<div class="academy-row">
			<div class="academy-col-12">
				<?php do_action( 'academy/templates/archive_course_header' ); ?>
			</div>
			<?php
			if ( 'left' === $sidebar_position ) :
				?>
				<div class="academy-col-md-3"><?php do_action( 'academy/templates/archive_course_sidebar' ); ?></div> 
				<?php
				endif;
			?>
			<div class="<?php echo esc_attr( 'none' === $sidebar_position ? 'academy-col-12' : 'academy-col-md-9' ); ?>"><?php do_action( 'academy/templates/archive_course_content' ); ?></div>
			<?php
			if ( 'right' === $sidebar_position ) :
				?>
				<div class="academy-col-md-3"><?php do_action( 'academy/templates/archive_course_sidebar' ); ?></div> 
				<?php
				endif;
			?>
			<div class="academy-col-12"><?php do_action( 'academy/templates/archive_course_footer' ); ?>
			</div>
		</div>
	</div>
</div>

<?php
	/**
	 * @hook - academy/templates/after_main_content
	 */
	do_action( 'academy/templates/after_main_content', 'archive-course.php' );
?>

<?php
academy_get_footer( 'course' );
