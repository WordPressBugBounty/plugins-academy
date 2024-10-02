<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<form id="academy_course_enroll_form" class="academy-widget-enroll__enroll-form" method="post" action="#">
	<?php wp_nonce_field( 'academy_nonce' ); ?>
	<input type="hidden" name="course_id" value="<?php echo esc_attr( get_the_ID() ); ?>">
	<?php
	if ( $is_enabled_academy_login && ! is_user_logged_in() ) :
		?>
		<button type="button" class="academy-btn academy-btn--bg-purple academy-btn-popup-login">
			<?php esc_html_e( 'Enroll Now', 'academy' ); ?>
		</button>
		<?php
			else :
				?>
		<button type="submit" class="academy-btn academy-btn--bg-purple">
				<?php esc_html_e( 'Enroll Now', 'academy' ); ?>
		</button>
				<?php
		endif;
			?>
</form>
