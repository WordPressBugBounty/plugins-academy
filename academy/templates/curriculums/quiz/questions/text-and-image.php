<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<label class="academy-quiz-answer academy-quiz-answer--textAndImage">
	<div class="academy-quiz-answer-image">
		<div class="academy-image" style="background-image: url( <?php echo esc_url( $url ); ?>)">
		</div>
		<div class="academy-lesson-quiz-answer-image-input-wrapper">
			<input class="academy-lesson-quiz-answer-input" type="<?php echo 'multipleChoice' === $question_type ? 'checkbox' : 'radio'; ?>" name="attempt[<?php echo esc_attr( $attempt_id ); ?>][quiz_question][<?php echo esc_attr( $question_id ); ?>]<?php echo 'multipleChoice' === $question_type ? '[]' : ''; ?>" value="<?php echo esc_attr( $ans_id ); ?>"/>
		</div>
	</div>
	<span class="academy-lesson-quiz-answer-title">
		<?php echo esc_html( $ans_title ); ?>
	</span>
</label>
