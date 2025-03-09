<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="academy-question-form">
	<h3 class="academy-question-form__heading">
		<?php esc_html_e( 'Ask a Question', 'academy' ); ?>
	</h3>
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
		<?php wp_nonce_field( 'academy_nonce', 'security' ); ?>
		<input type="hidden" name="action" value="academy/insert_question">
		<input type="hidden" name="course_id" value="<?php echo esc_attr( \Academy\Helper::get_the_current_course_id() ); ?>">
		<input name="title" id="title" placeholder="<?php esc_attr_e( 'Question Title', 'academy' ); ?>" value="">
		<input type="hidden" name="status" value="<?php echo esc_attr( 'waiting_for_answer' ); ?>">
		<textarea name="content" id="content" placeholder="<?php esc_attr_e( 'Question', 'academy' ); ?>"></textarea>
		<button class="academy-btn academy-btn--lg academy-btn--preset-purple" type="submit">
			<span class="academy-btn--label">
				<?php esc_html_e( 'Submit', 'academy' ); ?>
			</span>
		</button>
	</form>
</div>
