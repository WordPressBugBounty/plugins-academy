<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
academy_get_header();
?>

<div class="academy-password-reset-form-wrapper">
	<div class="academy-dashboard-settings__reset-form">
		<form method="post" class="academy-password-reset-form" action="#" method="post">
			<?php wp_nonce_field( 'academy_nonce', 'security' ); ?>
			<div class="academy-form-group">
				<label for="new_password"><?php echo esc_html__( 'New Password', 'academy' ); ?></label>
				<input name="new_password" class="academy-form-control" id="new_password" type="password" required="" value="">
			</div>
			<div class="academy-form-group">
				<label for="confirm_new_password"><?php echo esc_html__( 'Confirm New Password', 'academy' ); ?></label>
				<input name="confirm_new_password" class="academy-form-control" id="confirm_new_password" type="password" required="" value="">
			</div>
			<button name="academy_reset_submit" class="academy-btn academy-btn--bg-purple" type="submit"><?php echo esc_html__( 'Reset Password', 'academy' ); ?></button>
		</form>
	</div>
</div>
<?php academy_get_footer(); ?>
