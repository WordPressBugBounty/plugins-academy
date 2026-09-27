<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$course_id = get_the_ID();
$total_completed_lessons = \Academy\Helper::get_total_number_of_completed_course_topics_by_course_and_student_id( $course_id );
$total_topics = \Academy\Helper::get_total_number_of_course_topics( $course_id );
$percentage = \Academy\Helper::calculate_percentage( $total_topics, $total_completed_lessons );
$continue_learning = apply_filters( 'academy/templates/start_course_url', \Academy\Helper::get_start_course_permalink( $course_id ), $course_id );
$start_course_link_attributes = apply_filters(
	'academy/templates/start_course_link_attributes',
	array(
		'class' => 'academy-btn academy-btn--bg-purple',
		'href'  => $continue_learning,
	),
	$course_id
);
$course_type = \Academy\Helper::get_course_type( $course_id );

?>
<div class="academy-widget-enroll__continue">
	<?php if ( ! empty( $enrolled ) && 'completed' === $enrolled->enrolled_status ) : ?>
		<div class="academy-widget-enroll__head">
			<div class="academy-course-type">
				<?php if ( 'free' === $course_type ) {
					$type = __( 'Free', 'academy' );// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				} elseif ( 'paid' === $course_type ) {
					$type = __( 'Paid', 'academy' );// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				} else {
					$type = __( 'Public', 'academy' );// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				}
				echo esc_attr( $type ); ?>
			</div>
		</div>
	<?php endif;

	if ( ! empty( $enrolled ) ) : ?>
		<div class="academy-widget-enroll__enrolled-info">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			printf(
				// translators: %s: Enrollment date
				esc_html__( 'You have been enrolled on %s', 'academy' ),
				wp_kses_post('<span>' . date_i18n(
					get_option( 'date_format' ),
					strtotime( $enrolled->post_date )
				) . '</span>')
			);
			if ( $completed ) {
				printf(
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

		<?php if ( $total_topics > 0 ) : ?>
		<div class="academy-widget-enroll__progress">
			<div class="academy-widget-enroll__progress-head">
				<?php esc_html_e( 'Course Progress', 'academy' ); ?>
			</div>
			<div class="academy-widget-enroll__progress-meta">
				<span><?php echo esc_html( $total_completed_lessons . '/' . $total_topics ); ?></span>
				<span><?php echo esc_html( $percentage ); ?>% <?php esc_html_e( 'Complete', 'academy' ); ?></span>
			</div>
			<div class="academy-progress">
				<div class="academy-progress-bar" style="width: <?php echo esc_attr( $percentage ); ?>%;"></div>
			</div>
		</div>
		<?php endif; ?>
	<?php endif; ?>
	<a<?php foreach ( $start_course_link_attributes as $attribute_name => $attribute_value ) :
		if ( '' === $attribute_value || null === $attribute_value || false === $attribute_value ) {
			continue;
		}
		printf(
			' %1$s="%2$s"',
			esc_attr( $attribute_name ),
			'href' === $attribute_name ? esc_url( $attribute_value ) : esc_attr( $attribute_value )
		);
	endforeach; ?>>
		<?php
		if ( $total_completed_lessons ) {
				esc_html_e( 'Continue Learning', 'academy' );
		} else {
			esc_html_e( 'Start Course', 'academy' );
		}
		?>
	</a>
</div>
