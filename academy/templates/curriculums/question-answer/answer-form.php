<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="academy-anwser-form">
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
		<?php wp_nonce_field( 'academy_nonce', 'security' ); ?>
		<input type="hidden" name="action" value="academy/insert_question">
		<input type="hidden" name="course_id" value="<?php echo esc_attr( \Academy\Helper::get_the_current_course_id() ); ?>">
		<input type="hidden" name="parent" value="<?php echo esc_attr( $qa->comment_ID ); ?>" >
		<textarea name="content" id="content" placeholder="<?php echo esc_attr( 'Answer' ); ?>"></textarea>
		<button type="submit" class="academy-btn academy-btn--bg-purple" style="width: 120px;">
			<?php echo esc_html( 'Submit' ); ?>
		</button>
	</form>
</div>
