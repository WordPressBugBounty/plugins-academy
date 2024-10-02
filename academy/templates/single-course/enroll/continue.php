<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$course_id = get_the_ID();
$total_completed_lessons = \Academy\Helper::get_total_number_of_completed_course_topics_by_course_and_student_id( $course_id );
$continue_learning = apply_filters( 'academy/templates/start_course_url', \Academy\Helper::get_start_course_permalink( $course_id ) );

?>
<div class="academy-widget-enroll__continue">
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
