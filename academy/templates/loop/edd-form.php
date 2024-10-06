<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php
$download = new EDD_Download( $download_id );
$purchase_link = edd_get_purchase_link( [
	'download_id' => $download->ID,
	'text'        => 'layout_two' !== $card_style ? esc_html__( 'Add To Cart', 'academy' ) : '',
	'price'       => 'no',
	'class'       => 'academy-btn academy-btn--preset-purple',
	'color'       => '',
	'style'       => ''
] ); ?>
<div class="academy-widget-enroll__add-to-cart">
	<?php if ( ! is_user_logged_in() ) : ?>
		<button type="button" class="academy-btn academy-btn--bg-purple academy-btn-popup-login">
		<span class="academy-icon academy-icon--cart" aria-hidden="true"></span>
		<?php echo 'layout_two' !== $card_style ? esc_html__( 'Add to cart', 'academy' ) : ''; ?>
	</button>
		<?php
	else :
		echo $purchase_link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	endif; ?> 
</div>
