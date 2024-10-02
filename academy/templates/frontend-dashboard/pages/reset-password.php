<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

	$menu_items = array(
		'settings' => __( 'Profile', 'academy' ),
		'reset-password' => __( 'Reset Password', 'academy' ),
	);

	if ( \Academy\Helper::current_user_has_access_frontend_dashboard_menu( 'withdraw' ) ) {
		$menu_items['withdraw'] = __( 'Withdraw', 'academy' );
	}

	\Academy\Helper::get_template(
		'frontend-dashboard/pages/partials/sub-menu.php',
		[
			'menu' => apply_filters( 'academy/templates/frontend-dashboard/settings-content-menu', $menu_items )
		]
	);

	?>

<div class="academy-dashboard-settings__reset-form">
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'academy_nonce', 'security' ); ?>
		<input type="hidden" name="action" value="academy/save_frontend_dashboard_reset_password">
		<div class="academy-form-block">
			<label for="current_password"><?php echo esc_html__( 'Current Password', 'academy' ); ?></label>
			<input name="current_password" id="current_password" type="password" placeholder="" required="" value="">
		</div>
		<div class="academy-form-block">
			<label for="new_password"><?php echo esc_html__( 'New Password', 'academy' ); ?></label>
			<input name="new_password" id="new_password" type="password" required="" value="">
		</div>
		<div class="academy-form-block">
			<label for="confirm_new_password"><?php echo esc_html__( 'Confirm New Password', 'academy' ); ?></label>
			<input name="confirm_new_password" id="confirm_new_password" type="password" required="" value="">
		</div>
		<button class="academy-btn academy-btn--bg-purple" type="submit"><?php echo esc_html__( 'Reset Password', 'academy' ); ?></button>
	</form>
</div>
