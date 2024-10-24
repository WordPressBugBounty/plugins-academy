<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php if ( $is_enabled_academy_login && ! is_user_logged_in() ) : ?>

		<button type="button" class="academy-btn academy-btn--bg-purple academy-btn-popup-login">
			<span class="academy-icon academy-icon--cart" aria-hidden="true"></span>
			<?php echo 'layout_two' !== $card_style ? esc_html__( 'Add to cart', 'academy' ) : ''; ?>
		</button>	
	<?php else : ?>
		<div class="academy-widget-enroll__add-to-cart academy-widget-enroll__add-to-cart--surecart">
			<?php foreach ( $prices as $price ) : ?>
				<a class="academy-btn academy-btn--bg-purple"
					href="
						<?php
						echo esc_url(
							add_query_arg(
								[
									'line_items' => [
										[
											'price_id' => $price->id,
											'quantity' => 1,
										],
									],
								],
								\SureCart::pages()->url( 'checkout' )
							)
						);
						?>
				">
				<?php esc_html_e( 'Add to Cart', 'academy' ); ?>
				</a>
			<?php endforeach; ?>
		</div>
<?php endif; ?>
