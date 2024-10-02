<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="academy-quiz-buttons">
	<button type="button" class="academy-btn academy-btn--disabled" id="academy_quiz_question_prev_button">
	<span class="academy-btn--label">
		<?php echo esc_html__( 'Previous', 'academy' ); ?>
	</span>
	</button>
	<button type="button" class="academy-btn academy-btn--next" id="academy_quiz_question_next_button">
	<span class="academy-btn--label">
		<?php echo esc_html__( 'Next', 'academy' ); ?>
	</span>
	</button>
	<button type="submit" class="academy-btn academy-btn--next" id="academy_quiz_form_submit" style="display:none;">
		<?php echo esc_html__( 'Submit Quiz', 'academy' ); ?>
	</button>
</div>
