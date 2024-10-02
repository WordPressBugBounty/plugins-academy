<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="academy-lessons-content__video">
	<div class="plyr__video-embed" id="academy_video_player">
		<iframe
			src="<?php echo esc_url( 'https://player.vimeo.com/video/' . \Academy\Helper::vimeo_id_from_url( $url ) ); ?>"
			allowfullscreen
			allowtransparency
			allow="autoplay"
		></iframe>
	</div>
</div>
