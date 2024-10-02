<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<label class="academy-quiz-answer academy-quiz-answer--image-answer">
	<div class="academy-quiz-image-answer">
		<div class="academy-image" style="background-image: url( <?php echo esc_url( $url ); ?>">
		</div>
	</div>
	<input type="text" class="academy-lesson-quiz-image-answer-input" name="attempt[<?php echo esc_attr( $attempt_id ); ?>][quiz_question][<?php echo esc_attr( $question_id ); ?>][imageAnswer][<?php echo esc_attr( $ans_id ); ?>]"/>
</label>
