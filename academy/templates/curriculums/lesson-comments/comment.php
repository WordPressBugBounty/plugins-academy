<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$time_different = \Academy\Helper::get_time_different_dynamically_for_any_time( $comment->comment_date_gmt );

?>

<div class="academy-comment__question">
	<div class="academy-comment__meta">
		<div class="academy-course__author-meta">
			<img src="<?php echo esc_url( get_avatar_url( $comment->user_id, [ 'size' => '40' ] ) ); ?>" />
		</div>
		<div class="academy-comment-user-info">
			<h4 class="academy-comment-username">
				<?php echo esc_html( $comment->comment_author ); ?>
			</h4>
			<p class="academy-comment-time">
				<?php
				echo esc_html( $time_different );
				?>
			</p>
		</div>
	</div>
	<div class="academy-comment__body">
		<div class="academy-comment__body-left">
			<div><?php echo esc_html( $comment->comment_content ); ?></div>
		</div>
		<div class="academy-lesson-comment__body-right">
			<button class="academy-btn academy-btn--md academy-btn--preset-transparent" type="button">
				<span class="academy-icon academy-icon--qa">

				</span>
			</button>
		</div>
	</div>
</div>
