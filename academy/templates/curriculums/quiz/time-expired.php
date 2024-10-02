<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$attempts = \AcademyQuizzes\Classes\Query::get_quiz_attempt_details_by_quiz_id( array(
	'course_id' => $course_id,
	'quiz_id' => $quiz_id,
));
$total_attempt = count( $attempts );
$quiz          = \AcademyQuizzes\Helper::render_quiz_by_course_and_quiz_id( $course_id, $quiz_id );
$max_attempt   = ( 'retry' === $quiz['settings']['quiz_feedback_mode'] ) ? $quiz['settings']['quiz_max_attempts_allowed'] : 1;

?>

<div class="academy-lesson-quiz__inner">
	<div class="academy-lesson-quiz__expire-message academy-lesson-quiz--expire-wrap">
		<div class="academy-lesson-quiz__expire-image">
			<img class="academy-expire-image" src="<?php echo esc_url( ACADEMY_ASSETS_URI . 'images/expire.png' ); ?>" alt="image" />
		</div>
		<div class="academy-lesson-quiz__expire">
			<span class="academy-quiz-expire-message">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 0C114.6 0 0 114.6 0 256s114.6 256 256 256s256-114.6 256-256S397.4 0 256 0zM232 152C232 138.8 242.8 128 256 128s24 10.75 24 24v128c0 13.25-10.75 24-24 24S232 293.3 232 280V152zM256 400c-17.36 0-31.44-14.08-31.44-31.44c0-17.36 14.07-31.44 31.44-31.44s31.44 14.08 31.44 31.44C287.4 385.9 273.4 400 256 400z"></path></svg>
				<span>
					<?php
					echo esc_html(
						'Your time limit for this quiz has expired, please reattempt the quiz. Total Attempted : ' . $total_attempt . '/' . $max_attempt
					)
					?>
				</span>
			</span>
			<?php
			if ( $total_attempt < $max_attempt ) {
				\Academy\Helper::get_template( 'curriculums/quiz/start.php', array( 'quiz_id' => $quiz_id ) );
			}
			?>
		</div>
	</div>
</div>
