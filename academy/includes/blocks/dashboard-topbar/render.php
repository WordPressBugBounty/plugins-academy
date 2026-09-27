<?php
/**
 * Dashboard top bar: the page title and the buttons beside it.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks.
 * @var WP_Block $block      Block.
 *
 * @package Academy
 */

use Academy\FrontendDashboard\Dashboard;
use Academy\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

Dashboard::block_state();

$academy_path  = (string) get_query_var( 'academy_dashboard_page' );
$academy_title = Helper::get_frontend_dashboard_page_title( $academy_path ? $academy_path : 'index', (string) get_query_var( 'academy_dashboard_sub_page' ) );

$academy_wrapper = get_block_wrapper_attributes( [ 'class' => 'academy-dash-topbar' ] );
?>
<header <?php echo $academy_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="academy-dash-topbar__start">
		<button
			type="button"
			class="academy-dash-icon-button academy-dash-topbar__menu"
			data-wp-interactive="academy/dashboard"
			data-wp-on--click="actions.openMenu"
			aria-controls="academy-dash-sidebar"
			aria-label="<?php esc_attr_e( 'Menu', 'academy' ); ?>"
		>
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h12"></path></svg>
		</button>
		<?php if ( ! empty( $attributes['showTitle'] ) ) : ?>
			<h1 class="academy-dash-topbar__title"><?php echo esc_html( $academy_title ); ?></h1>
		<?php endif; ?>
		<?php do_action( 'academy/frontend_dashboard_topbar_after_heading' ); ?>
	</div>
	<div class="academy-dash-topbar__end">
		<?php do_action( 'academy/frontend_dashboard_topbar_right_content' ); ?>
		<?php if ( Helper::get_addon_active_status( 'notifications', true ) ) : ?>
			<div id="academy-notification"></div>
		<?php endif; ?>
	</div>
</header>
