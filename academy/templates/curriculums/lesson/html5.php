<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="academy-lessons-content__video">
	<video id="academy_video_player" playsinline controls>
		<source src="<?php echo esc_url( $url ); ?>" type="video/mp4"/>
	</video>
</div>
