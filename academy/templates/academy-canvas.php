<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The student dashboard can be shown without the theme's header and footer.
$academy_bare = \Academy\FrontendDashboard\Dashboard::is_bare();
if ( $academy_bare ) :
	?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<?php
	wp_body_open();
else :
	academy_get_header();
endif;
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
if ( $academy_bare ) {
	wp_footer();
	echo '</body></html>';
} else {
	academy_get_footer();
}
