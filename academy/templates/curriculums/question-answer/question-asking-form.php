<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="academy-question-form">
	<h3 class="academy-question-form__heading">
		<?php echo esc_html( 'Ask a Question' ); ?>
	</h3>
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
		<?php wp_nonce_field( 'academy_nonce', 'security' ); ?>
		<input type="hidden" name="action" value="academy/insert_question">
		<input type="hidden" name="course_id" value="<?php echo esc_attr( \Academy\Helper::get_the_current_course_id() ); ?>">
		<input name="title" id="title" placeholder="Question Title" value="">
		<input type="hidden" name="status" value="<?php echo esc_attr( 'waiting_for_answer' ); ?>">
		<textarea name="content" id="content" placeholder="<?php echo esc_attr( 'Question' ); ?>"></textarea>
		<button class="academy-btn academy-btn--lg academy-btn--preset-purple" type="submit">
			<span class="academy-btn--label">
				<?php echo esc_html( 'Submit' ); ?>
			</span>
		</button>
	</form>
</div>
