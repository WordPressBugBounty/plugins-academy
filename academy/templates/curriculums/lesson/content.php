<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="academy-lessons-content__text">
	<?php
		$content = apply_filters( 'the_content', $content );
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
</div>
