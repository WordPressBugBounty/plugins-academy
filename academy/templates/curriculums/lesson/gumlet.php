<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="academy-lessons-content__video">
	<div
		class="academy-embed-responsive academy-embed-responsive-16by9"
		id="academy_gumlet_player"
		data-next-lesson="<?php echo esc_url( $next_lesson ); ?>"
		data-course-id="<?php echo esc_attr( $course_id ); ?>"
		data-topic-id="<?php echo esc_attr( $lesson_id ); ?>"
	>
		<iframe
			src="<?php echo esc_url( $src ); ?>"
			class="academy-embed-responsive-item"
			width="100%"
			height="100%"
			frameborder="0"
			allow="autoplay; fullscreen; picture-in-picture; encrypted-media"
			allowfullscreen
			title="<?php esc_attr_e( 'Gumlet Video Player', 'academy' ); ?>"
		></iframe>
	</div>
</div>
