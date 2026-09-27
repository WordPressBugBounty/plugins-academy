<?php
/**
 * Dashboard: the frame around the menu, top bar and pages.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks.
 * @var WP_Block $block      Block.
 *
 * @package Academy
 */

use Academy\FrontendDashboard\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

\Academy\Blocks::use_dashboard_assets();

$academy_settings = Settings::get();

// Only the dashboard's own controls (in the menu and top bar) are interactive,
// so the pages inside the frame keep their own scripts.
$academy_wrapper = get_block_wrapper_attributes(
	[
		'class'        => 'academy-dash' . ( $academy_settings['sidebarCollapsed'] ? ' is-collapsed' : '' ),
		'data-sidebar' => ! empty( $attributes['sidebar'] ) ? $attributes['sidebar'] : 'left',
		'style'        => sprintf( '--academy-dashboard-sidebar-width:%dpx;', $academy_settings['sidebarWidth'] ),
	]
);
?>
<div <?php echo $academy_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<script>
		( function ( frame ) {
			try {
				var saved = window.localStorage.getItem( 'academy_dashboard_collapsed' );
				if ( '1' === saved || '0' === saved ) {
					frame.classList.toggle( 'is-collapsed', '1' === saved );
				}
			} catch ( e ) {}
		} )( document.currentScript.parentNode );
	</script>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<button type="button" class="academy-dash__scrim" tabindex="-1" aria-hidden="true" data-wp-interactive="academy/dashboard" data-wp-on--click="actions.closeMenu"></button>
</div>
