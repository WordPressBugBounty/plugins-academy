<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$attempt_id = isset( $_GET['attempt_id'] ) ? (int) sanitize_text_field( $_GET['attempt_id'] ) : 0;
$attempt_answer_details = \AcademyQuizzes\Helper::get_quiz_attempt_answer_details_by_attempt_id( $attempt_id );
$quiz_attempt = \AcademyQuizzes\Classes\Query::get_quiz_attempt( $attempt_id );

if ( ! $quiz_attempt ) {
	\Academy\Helper::get_template( 'curriculums/not-found.php' );
	return;
}

?>
<div class="academy-quiz-attempt-content__wrapper">
	<div class="academy-quiz-attempt-content__inner-wrapper">
		<?php
			\Academy\Helper::get_template( 'curriculums/quiz/attempts/attempt/overview.php', array( 'quiz_attempt' => $quiz_attempt ) );

			\Academy\Helper::get_template( 'curriculums/quiz/attempts/attempt/answer-details.php', array( 'attempt_answer_details' => $attempt_answer_details ) );

		?>
	</div>
</div>
