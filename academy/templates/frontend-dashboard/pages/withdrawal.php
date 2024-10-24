<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="academy-dashboard-withdrawal-info-wrapper">
	<div class="academy-dashboard-withdrawal-info">
		<div class="academy-dashboard-withdrawal-info--inner-wrap">
			<div class="academy-dashboard-withdrawal-info__icon">
				<span class="academy-icon academy-icon--withdraw"></span>
			</div>
			<div class="academy-dashboard-withdrawal-info__content">
				<span class="academy-cta-sub-title"><?php esc_html_e( 'Available Balance', 'academy' ); ?></span>
				<h4 class="academy-cta-title"><?php echo esc_html( $earning->withdraw_currency_symbol . '' . ( $earning->instructor_amount ? $earning->instructor_amount - $earning->withdraws_amount : 0 ) ); ?></h4>
			</div>
		</div>
		<div class="academy-dashboard-withdrawal-info__action"></div>
	</div>

	<p class="academy-note">
			<span class="academy-icon academy-icon--info-fill"></span>
			<?php esc_html_e( 'Manage your withdrawal method', 'academy' ); ?> <a href="<?php echo esc_url( Academy\Helper::get_frontend_dashboard_endpoint_url( 'withdraw' ) ); ?>"><?php esc_html_e( 'Settings', 'academy' ); ?></a><strong>
	</p>
	<?php
	if ( (int) $earning->instructor_amount > (int) \Academy\Helper::get_settings( 'instructor_minimum_withdraw_amount' ) ) :
		?>
	<form id="academy_withdrawal" class="academy-dashboard-instructor-earning-withdrawal" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'academy_nonce', 'security' ); ?>
		<input type="hidden" name="action" value="academy_multi_instructor/instructor_earning_withdrawal">
		<ul class="academy-dashboard-instructor-earning-withdrawal_type">
		<?php
		if ( 'paypal' === $withdraw_method_type ) :
			?>
			<li>
				<label>
					<span class="academy-icon academy-icon--paypal"></span>
					<p><?php esc_html_e( 'Paypal', 'academy' ); ?></p>
					<input type="radio" name="withdrawal_type" value="paypal" checked>
				</label>
			</li>
			<?php
			elseif ( 'echeck' === $withdraw_method_type ) :
				?>
			<li>
				<label>
					<span class="academy-icon academy-icon--e-check"></span>
					<p>
					<?php esc_html_e( 'E-check', 'academy' ); ?>
					</p>
					<input type="radio" name="withdrawal_type" value="e-check" checked>
				</label>
			</li>
				<?php
				elseif ( 'bank' === $withdraw_method_type ) :
					?>
			<li>
				<label>
					<span class="academy-icon academy-icon--bank-transfer"></span>
					<p>
						<?php esc_html_e( 'Bank Transfer', 'academy' ); ?>
					</p>
					<input type="radio" name="withdrawal_type" value="bank" checked>
				</label>
			</li>
					<?php
				endif;
				?>
		</ul>
		<div class="academy-withdrawal-amount-action">
			<input type="number" name="withdrawal_amount" class="academy-input" value="" placeholder="<?php esc_attr_e( 'Enter withdrawal amount', 'academy' ); ?>">
			<button class="academy-btn academy-btn--preset-purple"><?php echo esc_html__( 'Withdraw', 'academy' ); ?></button>
		</div>
	</form>
		<?php
		else :
			?>
		<p class="academy-info"><?php esc_html_e( 'Sorry, you do not have sufficient balance to withdraw.', 'academy' ); ?></p>
			<?php
		endif;
		?>
</div>


<div class="kzui-table kzui-table--dashboard-course">
	<div class="kzui-table__container">
		<div class="kzui-table__table kzui-table--has-slider">
			<div class="kzui-table__head">
				<div class="kzui-table__head-row">
					<div class="kzui-table__row-cell kzui-table__header-row-cell"><?php esc_html_e( 'Method', 'academy' ); ?></div>
					<div class="kzui-table__row-cell kzui-table__header-row-cell"><?php esc_html_e( 'Requested On', 'academy' ); ?></div>
					<div class="kzui-table__row-cell kzui-table__header-row-cell"><?php esc_html_e( 'Amount', 'academy' ); ?></div>
					<div class="kzui-table__row-cell kzui-table__header-row-cell"><?php esc_html_e( 'Status', 'academy' ); ?></div>
				</div>
			</div>
			<div class="kzui-table__body">
				<?php
				if ( is_array( $withdraw_history ) && count( $withdraw_history ) ) :
					?>
					<?php
					foreach ( $withdraw_history as $withdraw_item ) :
						$method = is_array( $withdraw_item->method_data ) ? $withdraw_item->method_data : json_decode( $withdraw_item->method_data, true );
						?>
						<div class="kzui-table__body-row">
							<div class="kzui-table__row-cell">
								<?php
									echo esc_html( isset( $method['withdraw_method_type'] ) ? $method['withdraw_method_type'] : '' );
								?>
							</div>
							<div class="kzui-table__row-cell">
								<?php
									echo esc_html( $withdraw_item->updated_at );
								?>
							</div>
							<div class="kzui-table__row-cell">
								<?php
									echo esc_html( $withdraw_item->amount );
								?>
							</div>
							<div class="kzui-list-table__table-row-cell academy-<?php echo esc_html( $withdraw_item->status ); ?>">
								<?php
									echo esc_html( $withdraw_item->status );
								?>
							</div>
						</div>
						<?php
						endforeach;
					?>
					<?php else : ?>
					<div class="academy-oops academy-oops__message">
						<div class="academy-oops__icon">
							<img src="<?php echo esc_url( ACADEMY_ASSETS_URI . 'images/NoDataAvailable.svg' ); ?>" alt="oops">
						</div>
						<h3 class="academy-oops__heading"><?php esc_html_e( 'No data Available!!', 'academy' ); ?></h3>
						<h3 class="academy-oops__text"><?php esc_html_e( 'No purchase data was found to see the available list here.', 'academy' ); ?></h3>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
