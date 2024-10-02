<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="academy-lesson-quiz-answer">
	<label>
		<textarea class="academy-lesson-quiz-short-answer" cols="80" name="attempt[<?php echo esc_attr( $attempt_id ); ?>][quiz_question][<?php echo esc_attr( $question_id ); ?>][shortAnswer]"></textarea>
	</label>
</div>
