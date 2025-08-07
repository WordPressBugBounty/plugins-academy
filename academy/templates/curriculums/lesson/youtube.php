<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="academy-lessons-content__video">
	<?php if ( \Academy\Helper::get_settings( 'is_enabled_academy_player' ) ) : ?>
		<div class="plyr__video-embed" id="academy_player"
			data-lesson-current="<?php echo esc_url( \Academy\Helper::get_youtube_video_id( $url ) );
			?>" 
			data-lesson-next="<?php echo esc_url( $next_topic_play_url ); ?>"
			data-course_id="<?php echo esc_attr( $course_id ); ?>"
			data-topic_id="<?php echo esc_attr( $lesson_id ); ?>"
			data-topic_type="lesson"
		>
		</div>
	<?php else : ?>
	<div class="plyr__video-embed" id="academy_video_player">
		<iframe
			src="<?php echo esc_url( \Academy\Helper::generate_video_embed_url( $url ) ); ?>"
			allowfullscreen
			allowtransparency
			allow="autoplay"
		></iframe>
	</div>
	<?php endif; ?>
</div>
