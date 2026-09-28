<?php
/**
 * Dashboard menu: the person, the view switch and the menu.
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

$academy_user  = wp_get_current_user();
$academy_areas = Helper::available_dashboard_areas();
$academy_view  = Helper::current_dashboard_view();

$academy_wrapper = get_block_wrapper_attributes(
	[
		'class'               => 'academy-dash-sidebar' . ( ! empty( $attributes['sticky'] ) ? ' is-sticky' : '' ),
		'id'                  => 'academy-dash-sidebar',
		'aria-label'          => __( 'Dashboard', 'academy' ),
	]
);
?>
<nav <?php echo $academy_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div
		class="academy-dash-sidebar__head"
		data-wp-interactive="academy/dashboard"
		data-wp-init="callbacks.init"
		data-wp-watch="callbacks.syncFrame"
		data-wp-on-document--keydown="actions.onKeydown"
		data-wp-on-document--click="actions.onDocumentClick"
	>
		<?php if ( ! empty( $attributes['showUser'] ) && $academy_user->ID ) : ?>
			<div class="academy-dash-user" data-wp-class--is-open="state.userOpen">
				<button
					type="button"
					class="academy-dash-user__toggle"
					data-wp-on--click="actions.toggleUser"
					data-wp-bind--aria-expanded="state.userOpen"
					aria-haspopup="menu"
					aria-expanded="false"
				>
					<img class="academy-dash-user__avatar" src="<?php echo esc_url( get_avatar_url( $academy_user->ID, [ 'size' => 80 ] ) ); ?>" alt="" width="36" height="36" />
					<span class="academy-dash-user__text academy-dash-label">
						<b><?php echo esc_html( Helper::get_current_user_full_name() ); ?></b>
						<small><?php echo esc_html( $academy_areas[ $academy_view ]['label'] ?? '' ); ?></small>
					</span>
					<span class="academy-icon academy-icon--angle-down academy-dash-user__caret academy-dash-label" aria-hidden="true"></span>
				</button>
				<div class="academy-dash-user__menu" role="menu">
					<div class="academy-dash-user__email"><?php echo esc_html( $academy_user->user_email ); ?></div>
					<?php do_action( 'academy/templates/frontend_dashboard/user_popover_menu_item_before_profile_menu' ); ?>
					<a role="menuitem" class="academy-dash-user__item" href="<?php echo esc_url( Helper::get_frontend_dashboard_endpoint_url( 'profile' ) ); ?>"><i class="academy-icon academy-icon--profile" aria-hidden="true"></i><?php esc_html_e( 'Profile', 'academy' ); ?></a>
					<a role="menuitem" class="academy-dash-user__item" href="<?php echo esc_url( Helper::get_frontend_dashboard_endpoint_url( 'settings' ) ); ?>"><i class="academy-icon academy-icon--settings" aria-hidden="true"></i><?php esc_html_e( 'Settings', 'academy' ); ?></a>
					<a role="menuitem" class="academy-dash-user__item" href="<?php echo esc_url( Helper::get_frontend_dashboard_endpoint_url( 'logout' ) ); ?>"><i class="academy-icon academy-icon--logout" aria-hidden="true"></i><?php esc_html_e( 'Log out', 'academy' ); ?></a>
				</div>
			</div>
		<?php endif; ?>
		<button
			type="button"
			class="academy-dash-icon-button academy-dash-sidebar__collapse"
			data-wp-on--click="actions.toggleCollapse"
			data-wp-bind--aria-expanded="!state.collapsed"
			aria-controls="academy-dash-sidebar"
			aria-label="<?php esc_attr_e( 'Collapse the menu', 'academy' ); ?>"
			title="<?php esc_attr_e( 'Collapse the menu', 'academy' ); ?>"
		>
			<span class="academy-icon academy-icon--expand-left" aria-hidden="true"></span>
		</button>
		<button type="button" class="academy-dash-icon-button academy-dash-sidebar__close" data-wp-on--click="actions.closeMenu" aria-label="<?php esc_attr_e( 'Close the menu', 'academy' ); ?>">
			<span class="academy-icon academy-icon--close" aria-hidden="true"></span>
		</button>
	</div>

	<?php if ( ! empty( $attributes['showViewSwitch'] ) && count( $academy_areas ) > 1 ) : ?>
		<nav class="academy-dashboard-view-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Dashboard view', 'academy' ); ?>">
			<?php foreach ( $academy_areas as $academy_key => $academy_area ) : ?>
				<a
					role="tab"
					aria-selected="<?php echo $academy_key === $academy_view ? 'true' : 'false'; ?>"
					class="academy-dashboard-view-tabs__tab<?php echo $academy_key === $academy_view ? ' is-current' : ''; ?>"
					href="<?php echo esc_url( add_query_arg( 'view', $academy_key, Helper::get_frontend_dashboard_endpoint_url( 'index' ) ) ); ?>"
					title="<?php echo esc_attr( $academy_area['label'] ); ?>"
				>
					<i class="<?php echo esc_attr( $academy_area['icon'] ); ?>" aria-hidden="true"></i>
					<span class="academy-dashboard-view-tabs__label"><?php echo esc_html( $academy_area['label'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<ul class="academy-dashboard-menu academy-dash-menu">
		<?php academy_frontend_dashboard_menu(); ?>
	</ul>
</nav>
