<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php if ( $is_enabled_academy_login && ! is_user_logged_in() ) : ?>

		<button type="button" class="academy-btn academy-btn--bg-purple academy-btn-popup-login">
			<span class="academy-icon academy-icon--cart" aria-hidden="true"></span>
			<?php echo 'layout_two' !== $card_style ? esc_html__( 'Buy Now', 'academy' ) : ''; ?>
		</button>	
	<?php
	else :
		foreach ( $required_levels as $level ) :
			$level_page_id = apply_filters( 'academy_pmpro_checkout_page_id', pmpro_getOption( 'checkout_page_id' ) );
			?>
			<div class="academy-pmpro-pricing__item-body">
				<a href="<?php echo esc_url( add_query_arg( array( 'level' => $level->id ), get_the_permalink( $level_page_id ) ) ); ?>" class="academy-btn academy-btn--preset-purple">
					<?php esc_html_e( 'Buy Now', 'academy' ); ?>
				</a>
			</div>
		<?php endforeach; ?>
<?php endif; ?>
