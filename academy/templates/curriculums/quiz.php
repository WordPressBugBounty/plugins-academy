<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	\Academy\Helper::get_template( 'curriculums/partial/login-alert.php', array( 'message' => 'Login Required To Unlock Quiz Features' ) );
	return;
}

$attempts = \AcademyQuizzes\Classes\Query::get_quiz_attempt_details_by_quiz_id( array(
	'course_id' => $course_id,
	'quiz_id' => $quiz_id,
));

$is_retry_mode_enabled = false;
$default_template_path = 'curriculums/quiz/quiz-start-before-attempt.php';
$default_template_args = array(
	'course_id' => $course_id,
	'quiz_id' => $quiz_id,
);

if ( $attempts && count( $attempts ) ) {
	$last_attempt            = ( ! empty( $attempts ) ) ? current( $attempts ) : '';
	$last_attempt_status     = ( ! empty( $last_attempt ) ) ? $last_attempt->attempt_status : '';
	$last_attempt_id         = ( ! empty( $last_attempt ) ) ? $last_attempt->attempt_id : 0;
	$is_already_attempt      = \AcademyQuizzes\Classes\Query::get_total_pending_attempt_by_attempt_id( $last_attempt_id );
	$is_time_expired         = \AcademyQuizzes\Helper::check_if_quiz_time_is_expired_by_quiz_id_and_attempt_id( $course_id, $quiz_id, $last_attempt_id );

	if ( 'pending' === $last_attempt_status && ! $is_already_attempt ) {
		if ( ! $is_time_expired ) {
			$default_template_path = 'curriculums/quiz/question-form.php';
			$default_template_args = wp_parse_args( array( 'last_attempt' => $last_attempt ), $default_template_args );
		} else {
			$quiz_attempt_data = array(
				'course_id'                => $course_id,
				'quiz_id'                  => $quiz_id,
				'attempt_id'               => $last_attempt_id,
				'total_questions'          => 0,
				'total_answered_questions' => 0,
				'total_marks'              => 0,
				'earned_marks'             => 0,
				'attempt_status'           => 'time expired',
			);
			\AcademyQuizzes\Classes\Query::quiz_attempt_insert( $quiz_attempt_data );
			$default_template_path = 'curriculums/quiz/time-expired.php';
		}
	} else {
		$default_template_path = 'curriculums/quiz/attempts/results.php';
	}//end if
}//end if

echo '<div class="academy-lessons-content__quiz">';
\Academy\Helper::get_template( $default_template_path, $default_template_args );
echo '</div>';
