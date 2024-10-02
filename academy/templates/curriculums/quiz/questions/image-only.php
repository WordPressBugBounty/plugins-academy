<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<label class="academy-quiz-answer academy-quiz-answer--image">
	<div class="academy-quiz-answer-image">
		<div class="academy-image" style="background-image: url( <?php echo esc_url( $url ); ?>)">
		</div>
		<div class="academy-lesson-quiz-answer-image-input-wrapper">
			<input type="<?php echo 'multipleChoice' === $question_type ? 'checkbox' : 'radio'; ?>" name="attempt[<?php echo esc_attr( $attempt_id ); ?>][quiz_question][<?php echo esc_attr( $question_id ); ?>]<?php echo 'multipleChoice' === $question_type ? '[]' : ''; ?>" value="<?php echo esc_attr( $ans_id ); ?>"/>
		</div>
	</div>
</label>

