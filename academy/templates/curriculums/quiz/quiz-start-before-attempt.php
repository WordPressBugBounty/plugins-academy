<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$attempts = \AcademyQuizzes\Classes\Query::get_quiz_attempt_details_by_quiz_id( array(
	'course_id' => $course_id,
	'quiz_id' => $quiz_id,
));
$quiz           = \AcademyQuizzes\Helper::render_quiz_by_course_and_quiz_id( $course_id, $quiz_id );
$max_attempt    = ( 'retry' === $quiz['settings']['quiz_feedback_mode'] ) ? $quiz['settings']['quiz_max_attempts_allowed'] : 1;

?>

<div class="academy-lesson-quiz-start">
	<div class="academy-lesson-quiz__start">
		<div class="academy-quiz-start">
			<div class="academy-quiz-start__top">
				<h5>
					<?php echo esc_html__( 'Quiz', 'academy' ); ?>
				</h5>
				<h3>
					<?php echo esc_html( $quiz['title'] ); ?>
				</h3>
			</div>
			<div class="academy-quiz-start__body">
				<div class="academy-quiz-start__details">
					<p>
						<span>
							<?php echo esc_html__( 'Questions : ', 'academy' ); ?>
						</span>
						<span>
							<?php echo esc_html( count( $quiz['questions'] ) ); ?>
						</span>
					</p>
					<p>
						<span>
							<?php echo esc_html__( 'Total Attempted : ', 'academy' ); ?>
						</span>
						<span>
							<?php echo esc_html( count( $attempts ) . '/' . $max_attempt ); ?>
						</span>
					</p>
					<p>
						<span>
							<?php echo esc_html__( 'Passing Grade : ', 'academy' ); ?>
						</span>
						<span>
							<?php echo esc_html( $quiz['settings']['quiz_passing_grade'] . '%' ); ?>
						</span>
					</p>
				</div>
				<div class="academy-quiz-start__buttons">
					<?php
						\Academy\Helper::get_template( 'curriculums/quiz/start.php', array( 'quiz_id' => $quiz_id ) );
					?>
				</div>
			</div>
		</div>
	</div>
</div>
