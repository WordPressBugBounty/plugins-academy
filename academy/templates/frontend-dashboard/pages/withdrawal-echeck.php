<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

\Academy\Helper::get_template(
	'frontend-dashboard/pages/partials/sub-menu.php',
	[
		'menu' => apply_filters('academy/templates/frontend-dashboard/withdrawal-content-menu', [
			'settings' => __( 'Profile', 'academy' ),
			'withdrawal' => __( 'Withdrawal', 'academy' ),
			'reset-password' => __( 'Reset Password', 'academy' ),
		])
	]
);

?>

<div id="tab-panel-0-withdraw-view" role="tabpanel" aria-labelledby="tab-panel-0-withdraw" class="components-tab-panel__tab-content" tabindex="0">
	<div class="academy-tab-content">
		<div class="academy-dashboard-settings__withdraw">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'academy_nonce', 'security' ); ?>
				<input type="hidden" name="action" value="academy/save_frontend_dashboard_withdraw_settings">
				<div class="academy-settings-info-heading">Select a withdraw method</div>
				<div class="academy-form-block academy-form-block--withdraw-method">
					<a href="<?php echo esc_url( get_site_url() . '/dashboard/withdrawal/' ); ?>" id="paypal-label" class="academy-withdraw-method">
						<h3 class="academy-withdraw-method__heading">Paypal</h3>
						<p class="academy-withdraw-method__subheading">Min withdraw $ 100</p>
						<input name="withdrawMethodType" id="paypal" type="radio" value="paypal">
					</a>	
					<a href="<?php echo esc_url( get_site_url() . '/dashboard/withdrawal-echeck/' ); ?>" id="echeck-label" class="academy-withdraw-method academy-withdraw-method--selected">
						<h3 class="academy-withdraw-method__heading">E-Check</h3>
						<p class="academy-withdraw-method__subheading">Min withdraw $ 100</p>
						<input name="withdrawMethodType" id="echeck" type="radio" value="echeck">
					</a>

					<a href="<?php echo esc_url( get_site_url() . '/dashboard/withdrawal-bank/' ); ?>" id="bank-label" class="academy-withdraw-method">
						<h3 class="academy-withdraw-method__heading">Bank Transfer</h3>
						<p class="academy-withdraw-method__subheading">Min withdraw $ 100</p>
						<input name="withdrawMethodType" id="bank" type="radio" value="bank">
					</a>
				</div>
						<div id="echeck" class="academy-form-block">
						<label for="echeckAddress">Your Physical Address</label>
							<textarea name="echeckAddress" id="echeckAddress" required="">
							<?php echo esc_html( get_user_meta( $user_id, 'academy_instructor_withdraw_echeck_address', true ) ); ?>
							</textarea>
							<p class="academy-note">We will send you an E-Check to this address directly.</p>
						</div>
						<input class="academy-btn academy-btn--bg-purple" type="submit" value="Save Settings">
				</form>
			</div>
	</div>
</div>
