<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="academy-password-reset-form-wrapper">
	<?php
	if ( $form_title ) :
		?>
		<h2 class="academy-password-reset-form-heading"><?php echo esc_html( $form_title ); ?></h2>
		<?php
		endif;
	?>
	<div class="academy-password-reset-form-status"></div>
	<form id="academy_reset_form" class="academy-password-reset-form" action="#" method="post">
		<?php wp_nonce_field( 'academy_reset_nonce' ); ?>
		<div class="academy-form-group">
			<?php
			if ( $username_label ) :
				?>
			<label for="username"><?php echo esc_html( $username_label ); ?></label>
				<?php
				endif;
			?>
			<input id="username" type="text" class="academy-form-control" name="username" value="">
		</div>
		<?php do_action( 'academy/templates/password_reset_form_before_submit' ); ?>
		<div class="academy-form-group">
			<button class="academy-btn academy-btn--bg-purple" type="submit"><?php echo esc_html( $reset_button_label ); ?></button>
		</div>
	</form>
	<p class="academy-password-reset-form-info">
		<a href="<?php echo esc_url( \Academy\Helper::get_page_permalink( 'frontend_dashboard_page' ) ); ?>"><?php echo esc_html( $login_button_label ); ?></a>
	</p>
</div>
