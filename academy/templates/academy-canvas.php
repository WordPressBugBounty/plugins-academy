<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

academy_get_header();

?>

<?php
	/**
	 * @Hook - academy/templates/before_main_content
	 */
	do_action( 'academy/templates/before_main_content', 'academy-canvas.php' );
?>
<div class="academy-canvas">
	<div class="<?php academy_get_the_canvas_container_class(); ?>">
		<div class="academy-row">
			<div class="academy-col-12">
			<?php
			while ( have_posts() ) :
				the_post();
				the_content();
				endwhile;
			?> 
			</div>
		</div>
	</div>
</div>

<?php
	/**
	 * @Hook - academy/templates/before_main_content
	 */
	do_action( 'academy/templates/after_main_content', 'academy-canvas.php' );
?>

<?php
academy_get_footer();
