<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit(); // Exit if accessed directly

}
use Academy\Helper;
use EDD\Models\Download;
$monetization_engine = Helper::monetization_engine();
if ( 'woocommerce' === $monetization_engine ) {
	$product = wc_get_product( $product_id ); ?>
	<div class="academy-widget-enroll__add-to-cart">
		<?php if ( $product ) :
			if ( $force_login_before_enroll && ! is_user_logged_in() ) : ?>
				<button type="button" class="academy-btn academy-btn--bg-purple academy-btn-popup-login">
					<span class="academy-icon academy--shopping-cart" aria-hidden="true"></span> <?php echo esc_html( $product->single_add_to_cart_text() ); ?>
				</button>
				<?php
		elseif ( Academy\Helper::is_product_in_cart( $product_id ) ) : ?>
					<a class="academy-btn academy-btn--preset-purple" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
						<?php esc_html_e( 'View Cart', 'academy' ); ?>
					</a>
			<?php
		elseif ( $product->is_purchasable() ) : ?>
					<form class="cart" method="post" enctype='multipart/form-data'>
						<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>"
								class="academy-btn academy-btn--preset-purple">
							<span class="academy-icon academy--shopping-cart" aria-hidden="true"></span> <?php echo esc_html( $product->single_add_to_cart_text() ); ?>
						</button>
					</form>
				<?php
		endif;
	else :
		?>
				<p class="academy-alert academy-alert--warning">
					<?php esc_html_e( 'Please make sure that your product exists and valid for this course', 'academy' ); ?>
				</p>
			<?php
	endif; ?>
	</div>
	<?php
} elseif ( 'edd' === $monetization_engine ) {
	$download = new EDD_Download( $download_id );
	$purchase_link = edd_get_purchase_link( [
		'download_id' => $download->ID,
		'text' => esc_html__( 'Add To Cart', 'academy' ),
		'price' => 'no',
		'class' => 'academy-btn academy-btn--preset-purple',
		'color' => '',
		'style' => ''
	] );
	?>
	<div class="academy-widget-enroll__add-to-cart">
	<?php if ( ! is_user_logged_in() ) : ?>
			<button type="button" class="academy-btn academy-btn--bg-purple academy-btn-popup-login">
					<span class="academy-icon academy--shopping-cart" aria-hidden="true"></span>
				<?php echo esc_html__( 'Add To Cart', 'academy' ); ?>
			</button>
		<?php
	else :
		echo esc_url( $purchase_link );
	endif; ?> 
	</div>
	<?php
}//end if
