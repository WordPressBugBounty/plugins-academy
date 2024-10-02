<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$time_different = \Academy\Helper::get_time_different_dynamically_for_any_time( $qa->comment_date_gmt );

?>

<div class="academy-qa__question">
	<div class="academy-qa__meta">
		<div class="academy-qa-text-avator">
			<?php echo esc_html( $qa->comment_author ? $qa->comment_author[0] : 'a' ); ?>
		</div>
		<div class="academy-qa-user-info">
			<h4 class="academy-qa-username">
				<?php echo esc_html( $qa->comment_author ); ?>
			</h4>
			<p class="academy-qa-time">
				<?php
				echo esc_html( $time_different );
				?>
			</p>
		</div>
	</div>
	<div class="academy-qa__body">
		<div class="academy-qa__body-left">
			<h3 class="academy-qa-title"><?php echo esc_html( $qa->title ); ?></h3>
			<div><?php echo esc_html( $qa->comment_content ); ?></div>
		</div>
		<div class="academy-qa__body-right">
			<button class="academy-btn academy-btn--md academy-btn--preset-transparent" type="button">
				<span class="academy-icon academy-icon--qa">

				</span>
			</button>
		</div>
	</div>
</div>
