<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="academy-lessons-content__video">
	<div class="plyr__video-embed" id="academy_video_player">
		<div class="academy-embed-responsive">
			<iframe
					src="<?php echo esc_url( $url ); ?>"
					allowfullscreen
					allowtransparency
					allow="autoplay"
			></iframe>
		</div>
	</div>
</div>
