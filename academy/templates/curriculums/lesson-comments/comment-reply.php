<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="academy-comment-reply-wrap">
	<div class="academy-comment-answer">
		<div class="academy-comment__meta">
			<div class="academy-course__author-meta">
				<img src="<?php echo esc_url( get_avatar_url( $comment->user_id, [ 'size' => '40' ] ) ); ?>" />
			</div>
			<div class="academy-comment-user-info">
				<h4 class="academy-comment-username">
					<?php echo esc_html( $comment->comment_author ); ?>
				</h4>
				<p class="academy-comment-time">
					<?php echo esc_html( \Academy\Helper::get_time_different_dynamically_for_any_time( $comment->comment_date_gmt ) ); ?>
				</p>
			</div>
		</div>
		<div class="academy-comment-reply__body">
			<div>
				<?php echo esc_html( $comment->comment_content ); ?>
			</div>
		</div>
	</div>
</div>
