<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	\Academy\Helper::get_template( 'curriculums/partial/login-alert.php', array( 'message' => 'Login Required To Unlock Quiz Features' ) );
	return;
}

echo '<div class="academy-lessons-content__quizpress-quiz">';
echo do_shortcode( '[quizpress_quiz quiz_id="' . absint( $quiz_id ) . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</div>';
