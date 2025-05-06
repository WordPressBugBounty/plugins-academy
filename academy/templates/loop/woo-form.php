<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>


<?php
$product = wc_get_product( $product_id );
if ( $force_login_before_enroll && ! is_user_logged_in() ) : ?>

	<button type="button" class="academy-btn academy-btn--bg-purple academy-btn-popup-login">
		<span class="academy-icon academy-icon--cart" aria-hidden="true"></span>
		<?php echo 'layout_two' !== $card_style ? esc_html__( 'Add to cart', 'academy' ) : ''; ?>
	</button>

<?php elseif ( Academy\Helper::is_product_in_cart( $product_id ) ) : ?>
	<a class="academy-btn academy-btn--preset-purple" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
		<?php esc_html_e( 'View Cart', 'academy' ); ?>
	</a>
<?php elseif ( $product && $product->is_purchasable() ) : ?>
	<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>"
		data-quantity="1"
		class="academy-btn academy-btn--preset-purple add_to_cart_button ajax_add_to_cart product_type_simple"
		data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
		data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>">
		<span class="academy-icon academy-icon--cart" aria-hidden="true"></span>
		<?php echo 'layout_two' !== $card_style ? esc_html( $product->add_to_cart_text() ) : ''; ?>
	</a>
<?php endif; ?>
