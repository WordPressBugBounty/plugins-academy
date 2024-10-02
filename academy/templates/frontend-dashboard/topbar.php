<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="academy-topbar academy-topbar-tabs">
	<div class="academy-topbar__entry-left">
		<div id="academy-collapsible-menu-open-button" class="academy-collapsible-menu academy-collapsible-menu--open" role="presentation">
			<span id="academy-collapsible-menu-open-icon" class="academy-icon academy-icon--expand-right"></span>
		</div>
		<h3 class="academy-topbar-heading"><?php echo esc_html( $page_title ); ?></h3>
		<?php
			do_action( 'academy/frontend_dashboard_topbar_after_heading' )
		?>
	</div>
	<div class="academy-topbar__entry-right">
		<?php
			do_action( 'academy/frontend_dashboard_topbar_right_content' )
		?>
		<?php if ( \Academy\Helper::get_addon_active_status( 'notifications', true ) ) : ?>
			<div id="academy-notification"></div>
		<?php endif; ?>
	</div>
</div>
