<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>


<div class="academy-qa">
	<div class="academy-qa__meta">
		<div class="academy-qa-text-avator">
			<?php echo esc_html( $comment->comment_author ? $comment->comment_author[0] : 'a' ); ?>
		</div>
		<div class="academy-qa-user-info">
			<h4 class="academy-qa-username">
				<?php echo esc_html( $comment->comment_author ); ?>
			</h4>
			<p class="academy-qa-time">
				<?php echo esc_html( \Academy\Helper::get_time_different_dynamically_for_any_time( $comment->comment_date_gmt ) ); ?>
			</p>
		</div>
	</div>
	<div class="academy-qa__body">
		<div>
			<?php echo esc_html( $comment->comment_content ); ?>
		</div>
	</div>
</div>
