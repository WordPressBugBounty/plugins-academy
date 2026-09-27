<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="academy-quiz-buttons">
	<!-- For 'all' layout, only a submit button is needed -->
	<div></div>
	<button
		type="submit"
		class="academy-btn academy-btn--next"
		id="academy_quiz_form_submit"
		style="display:block;"
	>
	<?php echo esc_html__( 'Submit Quiz', 'academy' ); ?>
	</button>
</div>
