<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="academy-reg-thankyou">
	<?php if ( isset( $instructor_status ) && 'pending' === $instructor_status ) : ?> 
		<h2 class="academy-reg-thankyou__heading"><?php esc_html_e( 'Thank you for applying as an Instructor.', 'academy' ); ?></h2>
		<p class="academy-reg-thankyou__error"><?php esc_html_e( "We've received your application and the results will be sent to you by email.", 'academy' ); ?></p>
	<?php elseif ( empty( $instructor_status ) ) : ?>
		<img src="<?php echo esc_url( ACADEMY_ASSETS_URI . 'images/become-an-instructor.svg' ); ?>" alt="instructor" />
		<h2 class="academy-reg-thankyou__heading"><?php esc_html_e( 'Instructor Registration', 'academy' ); ?></h2>
		<p class="academy-reg-thankyou__description">
			<?php esc_html_e( 'Do you want to start your career as an instructor?', 'academy' ); ?> 
			<br/> 
			<?php esc_html_e( 'Apply Now as an Instructor.', 'academy' ); ?>
		</p>
		<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'academy_nonce', 'security' ); ?>
			<input type="hidden" name="action" value="academy/student_register_as_instructor">
			<button type="submit" class="academy-btn academy-btn--bg-purple"><?php esc_html_e( 'Apply Now', 'academy' ); ?></button>
		</form>
	<?php endif; ?>
</div>

