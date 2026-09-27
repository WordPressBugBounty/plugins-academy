<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="academy-lessons-content__video">
	<div class="plyr__video-embed" id="academy_video_player">
		<iframe
			src="<?php echo esc_url( \Academy\Helper::generate_video_embed_url( $url ) ); ?>"
			allowfullscreen
			allowtransparency
			allow="autoplay"
		></iframe>
	</div>
</div>
