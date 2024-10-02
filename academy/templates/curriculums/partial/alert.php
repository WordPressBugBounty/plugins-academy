<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class='academy-lessons-content--topic-error'>
	<div>
		<div class='academy-render-topic-error-data'>
			<span class='academy-icon academy-icon--information'></span>
			<span class='academy-render-topic-error-message'>
				<?php echo esc_html( $message ); ?>
			</span>
		</div>
	</div>
</div>
