<?php
/**
 * Continue learning: courses in progress.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks.
 * @var WP_Block $block      Block.
 *
 * @package Academy
 */

use Academy\FrontendDashboard\Dashboard;
use Academy\FrontendDashboard\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Switched off on Customize → Dashboard, for both dashboards.
if ( ! Settings::get()['continue'] ) {
	return;
}

ob_start();
Dashboard::render_continue(
	[
		'count'  => (int) ( $attributes['count'] ?? 0 ),
		'layout' => (string) ( $attributes['layout'] ?? 'grid' ),
		'image'  => ! empty( $attributes['showImage'] ),
		'title'  => (string) ( $attributes['title'] ?? '' ),
	]
);
$academy_inner = ob_get_clean();

// Nothing in progress, or not the Learning view: nothing to show.
if ( '' === trim( $academy_inner ) ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( [ 'class' => 'academy-dash-continue' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo $academy_inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
