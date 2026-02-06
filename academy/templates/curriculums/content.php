<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="academy-lessons-content-wrap academy-lessons-expanded-sidebar academy-lessons-content-scroll">
	<div class="academy-lessons-content">
		<?php if ( ! is_user_logged_in() ) : ?>		
			<div class="academy-lessons__login-container">
				<div class="academy-lessons__login-header">
					<div  class="academy-lessons__login-header-left">
						<h4 ><?php esc_html_e( 'You are not logged in.', 'academy' ); ?></h4>
						<p class="academy-lessons__login-subtext"><?php esc_html_e( 'If you want to track your course progress, then you can log in and continue learning.', 'academy' ); ?></p>
					</div>

					<button type="button" class="academy-btn academy-btn--bg-purple academy-btn-popup-login">
						<span class="academy-icon" aria-hidden="true"></span>
						<?php esc_html_e( 'Login', 'academy' ); ?>
					</button>
				</div>
			</div>
			<?php
			do_action( 'academy/templates/curriculum/' . $type . '_content', $course_id, $id );
			if ( $is_previewable ) {
				// Load the content template for the current curriculum type
				do_action( 'academy/templates/curriculum/' . $type . '_content', $course_id, $id );
			} ?>
		<?php elseif ( empty( $type ) || empty( $id ) || empty( $course_id ) ) : ?>
			<?php \Academy\Helper::get_template( 'curriculums/not-found.php' ); ?>
		<?php else : ?>
			<?php
			// Load the content template for the current curriculum type
			do_action( 'academy/templates/curriculum/' . $type . '_content', $course_id, $id );

			// Load the template for previous and next topics
			do_action( 'academy/templates/curriculum/previous_and_next_template' );
			?>
		<?php endif; ?>
	</div>
</div>
