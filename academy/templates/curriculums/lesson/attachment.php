<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$attachment_url = isset( $attachment_id ) ? wp_get_attachment_url( $attachment_id ) : '';

?>

<div class="academy-lessons-content__attachment">
	<a href="<?php echo esc_url( $attachment_url ); ?>" class="academy-btn academy-btn--md academy-btn--preset-purple" type="link" rel="noreferrer" target="_blank">
		<span class="academy-icon academy-icon--eye"></span>
		<span class="academy-btn--label">Attachment</span>
	</a>
</div>
