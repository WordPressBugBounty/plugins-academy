<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="academy-comment-form-wrapper">
	<?php
		$is_reply = isset( $comment ) && isset( $comment->comment_ID );
		$form_class = $is_reply ? 'academy-comment-answer-form' : 'academy-comment-form';
		$form_heading = $is_reply ? '' : __( 'Submit your Comments', 'academy' );
		$placeholder = $is_reply ? __( 'Reply', 'academy' ) : __( 'Comments here..', 'academy' );
		$button_class = $is_reply ? 'academy-btn academy-btn--bg-purple' : 'academy-btn academy-btn--lg academy-btn--preset-purple';
	?>
	
	<div class="<?php echo esc_attr( $form_class ); ?>">
		<?php if ( ! $is_reply ) : ?>
			<h3 class="academy-comment-form__heading">
				<?php echo esc_html( $form_heading ); ?>
			</h3>
		<?php endif; ?>

		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<?php wp_nonce_field( 'academy_nonce', 'security' ); ?>
			<input type="hidden" name="action" value="academy/insert_lesson_comments">
			<input type="hidden" name="lesson_id" value="<?php echo esc_attr( $lesson_id ); ?>">
			<input type="hidden" name="course_id" value="<?php echo esc_attr( $course_id ); ?>">

			<?php if ( $is_reply ) : ?>
				<input type="hidden" name="parent" value="<?php echo esc_attr( $comment->comment_ID ); ?>">
			<?php endif; ?>
			
			<textarea class="academy-comment-answer-form__input" name="content" placeholder="<?php echo esc_attr( $placeholder ); ?>"></textarea>
			<button type="submit" class="<?php echo esc_attr( $button_class ); ?>">
				<?php esc_html_e( 'Submit', 'academy' ); ?>
			</button>
		</form>
	</div>
</div>

