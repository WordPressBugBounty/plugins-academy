<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$cart_icon = '<span class="academy-icon academy-icon--cart" aria-hidden="true"></span>';
$current = current( $integration );
if ( $is_enabled_academy_login && ! is_user_logged_in() ) : ?>
	<button type="button" class="academy-btn academy-btn--bg-purple academy-btn-popup-login">
		<span class="academy-icon academy-icon--cart" aria-hidden="true"></span>
		<?php
		if ( 'layout_two' !== $card_style ) {
			esc_html_e( 'Purchase Now', 'academy' );
		}
		?>
	</button>
<?php else : ?>
	<div class="academy-widget-enroll__add-to-cart academy-widget-enroll__add-to-cart--storeengine">
	<form class="storeengine-ajax-add-to-cart-form" action="#" method="post">
		<?php wp_nonce_field( 'storeengine_add_to_cart', 'storeengine_nonce' ); ?>
		<input type="hidden" name="product_id" value="<?php echo esc_attr( $current->integration->get_product_id() ); ?>">
		<input type="hidden" name="academy_course_id" value="<?php echo esc_attr( $current->integration->get_integration_id() ); ?>">
		<?php
		if ( count( $integration ) === 1 ) :
			?>
		<input type="hidden" name="price_id" id="product-<?php echo esc_attr( $current->price->get_product_id() ); ?>-price-<?php echo esc_attr( $current->price->get_id() ); ?>" value="<?php echo esc_attr( current( $integration )->price->get_id() ); ?>" checked/>
		<button class="academy-btn academy-btn--preset-purple academy-btn--add-to-cart storeengine-btn--add-to-cart" type="submit" data-action="buy_now">
			<span class="academy-icon academy-icon--cart" aria-hidden="true"></span>
			<?php
				'layout_two' === $card_style ? $cart_icon : esc_html_e( 'Purchase Now', 'academy' );
			?>
		</button>
			<?php
			else :
				?>
		<div class="academy-widget-enroll__add-to-cart academy-widget-enroll__add-to-cart--surecart">
		<a class="academy-btn academy-btn--bg-purple" href="<?php echo esc_url( $course_link ); ?>">
				<?php
				if ( 1 === $number_of_price ) {
					echo esc_attr( $cart_icon );
				}

				if ( 'layout_two' !== $card_style || $number_of_price > 1 ) {
					esc_html_e( 'Enroll Now', 'academy' );
				}
				?>
		</a>
	</div>
				<?php
			endif;
			?>
	</form>
</div>
<?php endif; ?>
