<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$course_id = get_the_ID();
$total_completed_lessons = \Academy\Helper::get_total_number_of_completed_course_topics_by_course_and_student_id( $course_id );
$continue_learning = apply_filters( 'academy/templates/start_course_url', \Academy\Helper::get_start_course_permalink( $course_id ), $course_id );
$course_type = ucfirst( \Academy\Helper::get_course_type( $course_id ) );

?>
<div class="academy-widget-enroll__continue">
	<?php if ( isset( $enrolled ) && 'completed' === $enrolled->enrolled_status ) : ?>
		<div class="academy-widget-enroll__head">
			<div class="academy-course-type">
				<?php echo esc_attr( $course_type ); ?>
			</div>
		</div>
	<?php endif;

	if ( ! empty( $enrolled ) ) : ?>
		<div class="academy-widget-enroll__enrolled-info">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo sprintf(
				// translators: %s: Enrollment date
				esc_html__( 'You have been enrolled on %s', 'academy' ),
				wp_kses_post('<span>' . date_i18n(
					get_option( 'date_format' ),
					strtotime( $enrolled->post_date )
				) . '</span>')
			);
			if ( $completed ) {
				echo sprintf(
					// translators: %s: Completed date
					esc_html__( 'and completed on %s', 'academy' ),
					wp_kses_post('<span> ' . date_i18n(
						get_option( 'date_format' ),
						strtotime( $completed->completion_date )
					) . '</span>')
				);
			}
			?>
		</div>
	<?php endif; ?>
	<a class="academy-btn academy-btn--bg-purple" href="<?php echo esc_url( $continue_learning ); ?>">
		<?php
		if ( $total_completed_lessons ) {
				esc_html_e( 'Continue Learning', 'academy' );
		} else {
			esc_html_e( 'Start Course', 'academy' );
		}
		?>
	</a>
</div>
