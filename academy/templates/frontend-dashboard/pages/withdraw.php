<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Helper;

$menu_items = array(
	'settings' => __( 'Profile', 'academy' ),
	'reset-password' => __( 'Reset Password', 'academy' ),
);

$withdraw_method_type = get_user_meta( $user_id, 'academy_instructor_withdraw_method_type', true );
$paypal = Helper::get_settings( 'is_enabled_instructor_paypal_withdraw', false );
$echeck = Helper::get_settings( 'is_enabled_instructor_echeck_withdraw', false );
$bank   = Helper::get_settings( 'is_enabled_instructor_bank_withdraw', false );
$min_amount = \Academy\Helper::get_settings( 'instructor_minimum_withdraw_amount' );

if ( \Academy\Helper::current_user_has_access_frontend_dashboard_menu( 'withdraw' ) ) {
	$menu_items['withdraw'] = __( 'Withdraw', 'academy' );
}

	\Academy\Helper::get_template(
		'frontend-dashboard/pages/partials/sub-menu.php',
		[
			'menu' => apply_filters( 'academy/templates/frontend-dashboard/withdraw-content-menu', $menu_items )
		]
	);

	?>


<div class="academy-dashboard-settings__withdraw">
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'academy_nonce', 'security' ); ?>
		<input type="hidden" name="action" value="academy_multi_instructor/save_frontend_dashboard_withdraw_settings">
		<div class="academy-settings-info-heading"><?php echo esc_html__( 'Select a withdraw method', 'academy' ); ?></div>
		<div class="academy-form-block academy-form-block--withdraw-method">
			<?php
			if ( ! $paypal && ! $echeck && ! $bank ) :
				?>
				<span class="academy-sub-title">No Payment Method Selected in Admin Dashboard, Make Sure to Select a Payment Method to Withdrawal Money.</span>
			<?php endif; ?>

			<?php
			if ( $paypal ) :
				?>
			<label class="<?php echo esc_attr( 'academy-withdraw-method' . ( 'paypal' === $withdraw_method_type ? ' academy-withdraw-method--selected' : '' ) ); ?>" id="paypal-label">
				<h3 class="academy-withdraw-method__heading"><?php echo esc_html__( 'Paypal', 'academy' ); ?></h3>
				<p class="academy-withdraw-method__subheading"><?php echo sprintf( esc_html__( 'Min withdraw $%s', 'academy' ), esc_html( $min_amount ) ); ?></p>
				<input name="withdrawMethodType" type="radio" value="paypal" <?php checked( $withdraw_method_type, 'paypal', true ); ?>>
			</label>
				<?php
				endif;
			if ( $echeck ) :
				?>
			<label class="<?php echo esc_attr( 'academy-withdraw-method' . ( 'echeck' === $withdraw_method_type ? ' academy-withdraw-method--selected' : '' ) ); ?>" id="echeck-label">
				<h3 class="academy-withdraw-method__heading">E-Check</h3>
				<p class="academy-withdraw-method__subheading"><?php echo sprintf( esc_html__( 'Min withdraw $%s', 'academy' ), esc_html( $min_amount ) ); ?></p>
				<input name="withdrawMethodType" type="radio" value="echeck" <?php checked( $withdraw_method_type, 'echeck', true ); ?>>
			</label>
				<?php
				endif;
			if ( $bank ) :
				?>
			<label class="<?php echo esc_attr( 'academy-withdraw-method' . ( 'bank' === $withdraw_method_type ? ' academy-withdraw-method--selected' : '' ) ); ?>" id="bank-label">
				<h3 class="academy-withdraw-method__heading"><?php echo esc_html__( 'Bank Transfer', 'academy' ); ?></h3>
				<p class="academy-withdraw-method__subheading"><?php echo sprintf( esc_html__( 'Min withdraw $%s', 'academy' ), esc_html( $min_amount ) ); ?>
				</p>
				<input name="withdrawMethodType" type="radio" value="bank" <?php checked( $withdraw_method_type, 'bank', true ); ?>>
			</label>
			<?php endif; ?>
		</div>
		<?php
		if ( Helper::get_settings( 'is_enabled_instructor_paypal_withdraw', false ) ) :
			?>	
		<!-- Paypal -->
		<div id="paypal" class="<?php echo esc_attr( 'academy-form-block academy-withdraw-method-form' . ( 'paypal' === $withdraw_method_type ? ' academy-withdraw-method-form--active' : '' ) ); ?>">
			<label for="paypalEmailAddress"><?php echo esc_html__( 'PayPal E-Mail Address', 'academy' ); ?></label>
			<input name="paypalEmailAddress" id="paypalEmailAddress" type="text" value="<?php echo esc_html( get_user_meta( $user_id, 'academy_instructor_withdraw_paypal_email', true ) ); ?>">
			<p class="academy-note"><?php echo esc_html__( 'We will use this email address to send the money to your Paypal account', 'academy' ); ?></p>
		</div>
			<?php
			endif;
		if ( Helper::get_settings( 'is_enabled_instructor_echeck_withdraw', false ) ) :
			?>
		<!-- e-check -->
		<div id="echeck" class="<?php echo esc_attr( 'academy-form-block academy-withdraw-method-form' . ( 'echeck' === $withdraw_method_type ? ' academy-withdraw-method-form--active' : '' ) ); ?>">
			<label for="echeckAddress"><?php echo esc_html__( 'Your Physical Address', 'academy' ); ?></label>
			<textarea name="echeckAddress" id="echeckAddress"><?php echo esc_html( get_user_meta( $user_id, 'academy_instructor_withdraw_echeck_address', true ) ); ?></textarea>
			<p class="academy-note"><?php echo esc_html__( 'We will send you an E-Check to this address directly.', 'academy' ); ?></p>
		</div>
			<?php
			endif;
		if ( Helper::get_settings( 'is_enabled_instructor_bank_withdraw', false ) ) :
			?>
		<!-- Bank -->
		<div id="bank" class="<?php echo esc_attr( 'academy-form-block academy-withdraw-method-form' . ( 'bank' === $withdraw_method_type ? ' academy-withdraw-method-form--active' : '' ) ); ?>">
			<div class="academy-form-block">
				<label for="bankAccountName"><?php echo esc_html__( 'Account Name', 'academy' ); ?></label>
				<input name="bankAccountName" id="bankAccountName" type="text" value="<?php echo esc_html( get_user_meta( $user_id, 'academy_instructor_withdraw_bank_acocunt_name', true ) ); ?>">
			</div>

			<div class="academy-form-block">
				<label for="bankAccountNumber"><?php echo esc_html__( 'Account Number', 'academy' ); ?></label>
				<input name="bankAccountNumber" id="bankAccountNumber" type="text" value="<?php echo esc_html( get_user_meta( $user_id, 'academy_instructor_withdraw_bank_acocunt_number', true ) ); ?>">
			</div>

			<div class="academy-form-block">
				<label for="bankName"><?php echo esc_html__( 'Bank Name', 'academy' ); ?></label>
				<input name="bankName" id="bankName" type="text" value="<?php echo esc_html( get_user_meta( $user_id, 'academy_instructor_withdraw_bank_name', true ) ); ?>">
			</div>

			<div class="academy-form-block">
				<label for="bankIBAN"><?php echo esc_html__( 'IBAN', 'academy' ); ?></label>
				<input name="bankIBAN" id="bankIBAN" type="text" value="<?php echo esc_html( get_user_meta( $user_id, 'academy_instructor_withdraw_bank_iban', true ) ); ?>">
			</div>

			<div class="academy-form-block">
				<label for="bankSWIFTCode"><?php echo esc_html__( 'BIC / SWIFT', 'academy' ); ?></label>
				<input name="bankSWIFTCode" id="bankSWIFTCode" type="text" value="<?php echo esc_html( get_user_meta( $user_id, 'academy_instructor_withdraw_bank_swiftcode', true ) ); ?>">
			</div>
		</div>
		<?php endif; ?>
		<button type="submit" class="academy-btn academy-btn--bg-purple"><?php esc_html_e( 'Save Settings', 'academy' ); ?></button>
	</form>
</div>
