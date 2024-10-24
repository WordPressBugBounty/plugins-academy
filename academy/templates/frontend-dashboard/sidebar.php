<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Academy\Helper;
$user = wp_get_current_user();
?>
<div class="academy-frontend-dashboard__sidebar" id="academy-frontend-dashboard-sidebar">
	<div class="academy-frontend-dashboard__user">
		<div class="user-title" id="user-avatar">
			<img src="<?php echo esc_url( get_avatar_url( $user->ID, [ 'size' => '40' ] ) ); ?>" />
			<span class="academy-dashboard-menu__item-label"><?php echo esc_html( Helper::get_current_user_full_name() ); ?></span>
			<span id="user-dropdown-icon" class="academy-icon academy-icon--angle-right"></span>
		</div>
			<ul class="academy-frontend-dashboard__user-dropdown" id="user-avatar-dropdown">
				<li class="academy-user-info">
					<div class="academy-entry-left">
						<img src="<?php echo esc_url( get_avatar_url( $user->ID, [ 'size' => '40' ] ) ); ?>" />
					</div>
					<p class="academy-entry-right">
						<span class="academy-user-name">
							<?php echo esc_html( Helper::get_current_user_full_name() ); ?>
						</span>
						<br>
						<span class="academy-user-email">
							<?php echo esc_html( $user->user_email ); ?>
						</span>
						<?php do_action( 'academy/templates/frontend_dashboard/after_user_email_popover' ); ?>
					</p>
				</li>
				<?php do_action( 'academy/templates/frontend_dashboard/user_popover_menu_item_before_profile_menu' ); ?>
				<li>
					<a href="<?php echo esc_url( Helper::get_frontend_dashboard_endpoint_url( 'profile' ) ); ?>">
						<i class="academy-icon academy-icon--profile"></i>
						<span><?php esc_html_e( 'Profile', 'academy' ); ?></span>    
					</a>
				</li>
				<li>
					<a href="<?php echo esc_url( Helper::get_frontend_dashboard_endpoint_url( 'settings' ) ); ?>">
						<i class="academy-icon academy-icon--settings"></i>
						<span><?php esc_html_e( 'Settings', 'academy' ); ?></span>    
					</a>
				</li>
				<li>
					<a href="<?php echo esc_url( Helper::get_frontend_dashboard_endpoint_url( 'logout' ) ); ?>">
						<i class="academy-icon academy-icon--logout"></i>
						<span><?php esc_html_e( 'Logout', 'academy' ); ?></span>    
					</a>
				</li>
			</ul>
		<div id="academy-collapsible-menu-close-button" class="academy-collapsible-menu academy-collapsible-menu--close" role="presentation">
			<span id="academy-collapsible-menu-close-icon" class="academy-icon academy-icon--expand-left"></span>
		</div>
	</div>

	<ul id="academy-dashboard-menu" class="academy-dashboard-menu">
		<?php
		/**
		 * @hook - academy_frontend_dashboard_menu
		 */
		do_action( 'academy_frontend_dashboard_menu' )
		?>
	</ul>
</div>
