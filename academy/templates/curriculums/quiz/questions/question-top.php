<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$attempts = \AcademyQuizzes\Classes\Query::get_quiz_attempt_details_by_quiz_id( array(
	'course_id' => $course_id,
	'quiz_id' => $quiz_id,
));
$quiz                    = \AcademyQuizzes\Helper::render_quiz_by_course_and_quiz_id( $course_id, $quiz_id );
$questions_with_options  = \AcademyQuizzes\Helper::get_questions_with_options_from_quiz_array( $quiz );
$max_attempt             = ( 'retry' === $quiz['settings']['quiz_feedback_mode'] ) ? $quiz['settings']['quiz_max_attempts_allowed'] : 1;
$is_hide_question_number = $quiz['settings']['quiz_hide_question_number'];
$is_quiz_time_enabled    = $quiz['settings']['quiz_time'];

?>
<div class="academy-lesson-quiz__header">
	<div class="academy-quiz-head-item academy-quiz-head-item--question">

		<?php
			echo esc_html__( 'Question No : ', 'academy' );

		if ( ! $is_hide_question_number ) {
			echo '<span id="academy-quiz-question-number">' . esc_html( $question_count ) . '</span>/' . count( $questions_with_options );
		} else {
			echo esc_html__( 'Hidden', 'academy' );
		}
		?>

	</div>
	<div class="academy-quiz-head-item academy-quiz-head-item--attempts">

		<?php
			echo esc_html__( 'Total Attempted : ', 'academy' );
			echo esc_html( count( $attempts ) . '/' . $max_attempt );
		?>

	</div>
	<div class="academy-quiz-head-item academy-quiz-head-item--time">

		<?php if ( 0 === $is_quiz_time_enabled ) : ?>

			<span class="academy-quiz-time-no-limit">
				<?php esc_html__( 'No time limit', 'academy' ); ?>
			</span>

		<?php else : ?>

			<span class="academy-quiz-timer">
				<?php esc_html__( 'Time Remaining', 'academy' ); ?>
				<span id="academy_quiz_timer" data-time="<?php echo esc_attr( $quiz['settings']['quiz_time'] ); ?>" data-unit="<?php echo esc_attr( $quiz['settings']['quiz_time_unit'] ); ?>">00:00</span>
			</span>

		<?php endif; ?>

	</div>
</div>
