<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$course_id    = isset( $course_id ) ? $course_id : 0;
$current_type = isset( $current_type ) ? $current_type : '';
$current_id   = isset( $current_id ) ? $current_id : 0;
?>
<div class="academy-learn-footer-bar">
	<?php if ( ! empty( $previous['link'] ) ) : ?>
	<a href="<?php echo esc_url( $previous['link'] ); ?>" class="academy-learn-footer-bar__nav" role="presentation">
		<span class="academy-icon academy-icon--arrow-left"></span>
		<span class="academy-learn-footer-bar__nav-text">
			<small><?php esc_html_e( 'Previous', 'academy' ); ?></small>
			<b><?php echo esc_html( $previous['name'] ); ?></b>
		</span>
	</a>
	<?php else : ?>
	<span class="academy-learn-footer-bar__nav" aria-disabled="true" style="opacity:.4;pointer-events:none;">
		<span class="academy-icon academy-icon--arrow-left"></span>
		<span class="academy-learn-footer-bar__nav-text">
			<small><?php esc_html_e( 'Previous', 'academy' ); ?></small>
			<b><?php esc_html_e( 'Start of course', 'academy' ); ?></b>
		</span>
	</span>
	<?php endif; ?>

	<?php if ( is_user_logged_in() && $current_id ) : ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:contents">
		<?php wp_nonce_field( 'academy_nonce', 'security' ); ?>
		<input type="hidden" name="action" value="academy/save_topic_mark_as_complete">
		<input type="hidden" name="course_id" value="<?php echo esc_attr( $course_id ); ?>">
		<input type="hidden" name="topic_type" value="<?php echo esc_attr( $current_type ); ?>">
		<input type="hidden" name="topic_id" value="<?php echo esc_attr( $current_id ); ?>">
		<button type="submit" class="academy-learn-footer-bar__complete">
			<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"></path></svg>
			<?php esc_html_e( 'Mark as Complete', 'academy' ); ?>
		</button>
	</form>
	<?php else : ?>
	<span></span>
	<?php endif; ?>

	<?php if ( ! empty( $next['link'] ) ) : ?>
	<a href="<?php echo esc_url( $next['link'] ); ?>" class="academy-learn-footer-bar__nav academy-learn-footer-bar__nav--next" role="presentation">
		<span class="academy-learn-footer-bar__nav-text">
			<small><?php esc_html_e( 'Next', 'academy' ); ?></small>
			<b><?php echo esc_html( $next['name'] ); ?></b>
		</span>
		<span class="academy-icon academy-icon--arrow-right"></span>
	</a>
	<?php else : ?>
	<span class="academy-learn-footer-bar__nav academy-learn-footer-bar__nav--next" aria-disabled="true" style="opacity:.4;pointer-events:none;">
		<span class="academy-learn-footer-bar__nav-text">
			<small><?php esc_html_e( 'Next', 'academy' ); ?></small>
			<b><?php esc_html_e( 'End of course', 'academy' ); ?></b>
		</span>
		<span class="academy-icon academy-icon--arrow-right"></span>
	</span>
	<?php endif; ?>
</div>
