<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="academy-lessons-content-wrap academy-lessons-expanded-sidebar academy-lessons-content-scroll">
	<div class="academy-lessons-content">
		<?php if ( ! is_user_logged_in() && ! \Academy\Helper::is_public_course( \Academy\Helper::get_the_current_course_id() ) ) : ?>
			<?php if ( $is_previewable ) : ?>
				<div class="academy-lessons__preview-notice">
					<span class="academy-lessons__preview-notice-icon" aria-hidden="true">
						<span class="academy-icon academy-icon--lock"></span>
					</span>
					<p class="academy-lessons__preview-notice-text">
						<?php esc_html_e( 'You’re previewing this lesson. Log in to track your progress and continue learning.', 'academy' ); ?>
					</p>
					<button type="button" class="academy-btn academy-btn--bg-purple academy-btn-popup-login academy-lessons__preview-notice-btn">
						<?php esc_html_e( 'Log in', 'academy' ); ?>
					</button>
				</div>
				<div id="academy-btn-popup-login"></div>
				<?php
				// Load the content template for the current curriculum type
				do_action( 'academy/templates/curriculum/' . $type . '_content', $course_id, $id );
				?>
			<?php else : ?>
				<?php $academy_is_paid_course = 'paid' === \Academy\Helper::get_course_type( $course_id ); ?>
				<div class="academy-lessons__access-notice">
					<span class="academy-lessons__access-notice-icon" aria-hidden="true">
						<span class="academy-icon academy-icon--lock"></span>
					</span>
					<?php if ( $academy_is_paid_course ) : ?>
						<h4 class="academy-lessons__access-notice-title"><?php esc_html_e( 'Purchase or enroll required', 'academy' ); ?></h4>
						<p class="academy-lessons__access-notice-subtext"><?php esc_html_e( 'This section is available for enrolled students only. Please purchase this course or enroll to access this section.', 'academy' ); ?></p>
						<a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>" class="academy-btn academy-btn--bg-purple academy-lessons__access-notice-btn">
							<span class="academy-icon academy-icon--cart" aria-hidden="true"></span>
							<?php esc_html_e( 'Purchase Now', 'academy' ); ?>
						</a>
					<?php else : ?>
						<h4 class="academy-lessons__access-notice-title"><?php esc_html_e( 'Enrollment required', 'academy' ); ?></h4>
						<p class="academy-lessons__access-notice-subtext"><?php esc_html_e( 'This section is available for enrolled students only. Please log in and enroll to access this section.', 'academy' ); ?></p>
						<button type="button" class="academy-btn academy-btn--border-purple academy-btn-popup-login academy-lessons__access-notice-btn academy-lessons__access-notice-btn--outline">
							<span class="academy-icon academy-icon--arrow-right" aria-hidden="true"></span>
							<?php esc_html_e( 'Login to Enroll', 'academy' ); ?>
						</button>
					<?php endif; ?>
				</div>
				<div id="academy-btn-popup-login"></div>
			<?php endif; ?>
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
