<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$attempt_id = isset( $_GET['attempt_id'] ) ? (int) sanitize_text_field( wp_unslash( $_GET['attempt_id'] ) ) : 0;
$attempt_answer_details = \AcademyQuizzes\Helper::get_quiz_attempt_answer_details_by_attempt_id( $attempt_id );
$quiz_attempt = \AcademyQuizzes\Classes\Query::get_quiz_attempt( $attempt_id );
$feedback = json_decode( $quiz_attempt->attempt_info, true );
$instructor_feedback = isset( $feedback['instructor_feedback'] ) ? $feedback['instructor_feedback'] : '';
if ( ! $quiz_attempt ) {
	\Academy\Helper::get_template( 'curriculums/not-found.php' );
	return;
}

$back_topic = array(
	'type' => 'quiz',
	'slug' => get_query_var( 'name' ),
);

?>
<div class="academy-quiz-attempt-content__wrapper">
	<div class="academy-quiz-attempt-back academy-quiz-attempt-back--top">
		<a class="academy-quiz-attempt-back__link" href="<?php echo esc_url( \Academy\Helper::get_topic_play_link( $back_topic ) ); ?>">
			<span class="academy-icon academy-icon--arrow-left"></span>
			<?php esc_html_e( 'Back', 'academy' ); ?>
		</a>
	</div>
	<div class="academy-quiz-attempt-content__inner-wrapper">
		<?php
			\Academy\Helper::get_template( 'curriculums/quiz/attempts/attempt/overview.php', array( 'quiz_attempt' => $quiz_attempt ) );

			\Academy\Helper::get_template( 'curriculums/quiz/attempts/attempt/answer-details.php', array(
				'attempt_answer_details' => $attempt_answer_details,
				'instructor_feedback' => $instructor_feedback
			) );

			?>
	</div>
</div>
