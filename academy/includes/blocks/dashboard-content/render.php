<?php
/**
 * Dashboard pages: the page someone opened from the menu, or on the home page
 * the blocks inside this one.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks: the home page.
 * @var WP_Block $block      Block.
 *
 * @package Academy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$academy_page = (string) get_query_var( 'academy_dashboard_page' );

$academy_wrapper = get_block_wrapper_attributes( [ 'class' => 'academy-dash-content academy-frontend-dashboard__content' ] );
?>
<main <?php echo $academy_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php
	if ( '' !== $academy_page && 'index' !== $academy_page && has_action( 'academy_frontend_dashboard_' . $academy_page . '_endpoint' ) ) {
		// A page from the menu, drawn by its own template.
		do_action( 'academy_frontend_dashboard_' . $academy_page . '_endpoint', $academy_page );
	} else {
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>
</main>
