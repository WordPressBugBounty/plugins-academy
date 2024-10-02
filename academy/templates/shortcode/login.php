<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="academy-login-form-wrapper">
	<?php

		do_action( 'academy/templates/shortcode/before_login' );

	if ( $form_title ) :
		?>
		<h2 class="academy-login-form-heading"><?php echo esc_html( $form_title ); ?></h2>
		<?php
		endif;
	?>
	<div class="academy-login-form-status"></div>
	<form id="academy_login_form" class="academy-login-form" action="#" method="post">
		<?php wp_nonce_field( 'academy_login_nonce' ); ?>
		<input type="hidden" name="login_redirect_url" value="<?php echo esc_attr( $login_redirect_url ); ?>">
		<div class="academy-form-group">
			<?php
			if ( $username_label ) :
				?>
			<label for="username"><?php echo esc_html( $username_label ); ?></label>
				<?php
				endif;
			?>
			<input id="username" type="text" class="academy-form-control" name="username" value="" placeholder="<?php echo esc_attr( $username_placeholder ); ?>">
		</div>
		<div class="academy-form-group">
			<?php
			if ( $password_label ) :
				?>
			<label for="password"><?php echo esc_html( $password_label ); ?></label>
				<?php
				endif;
			?>
			<div>
				<input id="password" type="password" class="academy-form-control" name="password" value="" placeholder="<?php echo esc_attr( $password_placeholder ); ?>">
				<span id="password-icon" class="toggle-password academy-icon academy-icon--eye"></span>
			</div>

		</div>
		<div class="academy-form-group academy-d-flex academy-flex-row academy-justify-content-between">
			<div class="academy-form-group__forgetmenot">
				<label><input type="checkbox" name="remember"> <?php echo esc_html( $remember_label ); ?></label>
			</div>
			<div class="academy-form-group__inner">
				<a class="academy-form-text-link" href="<?php echo esc_url( \Academy\Helper::get_lost_password_url() ); ?>"><?php echo esc_html( $reset_password_label ); ?></a>
			</div>
		</div>
		<?php do_action( 'academy/templates/login_form_before_submit' ); ?>
		<div class="academy-form-group">
			<button class="academy-btn academy-btn--bg-purple" type="submit"><?php echo esc_html( $login_button_label ); ?></button>
		</div>
	</form>
	<p class="academy-login-form-info"><?php esc_html_e( 'Don\'t have an account?', 'academy' ); ?>  <a href="<?php echo esc_url( $student_register_url ? $student_register_url : add_query_arg( 'redirect_to', get_permalink(), \Academy\Helper::get_page_permalink( 'frontend_student_reg_page' ) ) ); ?>"><?php esc_html_e( 'Register Now', 'academy' ); ?></a></p>
	<?php
		do_action( 'academy/templates/shortcode/after_login' );
	?>
</div>
