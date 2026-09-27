<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// load lesson title if it is enabled
if ( \Academy\Helper::get_settings( 'is_enabled_lessons_content_title' ) ) {
	\Academy\Helper::get_template( 'curriculums/lesson/title.php', [ 'lesson' => $lesson ] );
}

$status = isset( $lesson->lesson_status ) ? $lesson->lesson_status : '';// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$has_video = ! empty( $lesson_meta['video_source']['type'] ) && 'publish' === $status;
$has_featured_image = 'publish' === $status && ! empty( $lesson_meta['featured_media'] );

if ( $has_video ) {
	$template_path = '';
	$template_args = [];

	// Providers the custom lesson player renders + tracks (resume + watch-gate).
	$academy_provider = '';
	$academy_src      = '';

	switch ( $lesson_meta['video_source']['type'] ) {
		case 'youtube':
		case 'vimeo':
			$academy_provider = $lesson_meta['video_source']['type'];
			$academy_src      = $lesson_meta['video_source']['url'];
			break;

		case 'html5':
			$academy_provider = 'html5';
			$academy_src      = wp_get_attachment_url( $lesson_meta['video_source']['id'] );
			break;

		case 'external':
			$video = $lesson_meta['video_source'];
			// first check external URL contain html5 video or not
			if ( \Academy\Helper::is_html5_video_link( $video['url'] ) ) {
				$embed_url        = \Academy\Helper::get_basic_url_to_embed_url( $video['url'] );
				$academy_provider = 'html5';
				$academy_src      = ( isset( $embed_url['url'] ) && ! empty( $embed_url['url'] ) ) ? $embed_url['url'] : $video['url'];
			} else {
				$template_path = 'curriculums/lesson/external.php';
				$template_args = \Academy\Helper::get_basic_url_to_embed_url( $video['url'] );
			}
			break;
		case 'embedded':
			$video = $lesson_meta['video_source'];
			$host_url = \Academy\Helper::generate_video_embed_url( $video['url'] );
			$path = 'external.php';// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			if ( $video['url'] === $host_url ) {
				$path  = 'embedded.php';// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
			$template_path = 'curriculums/lesson/' . $path;
			$template_args = \Academy\Helper::get_basic_url_to_embed_url( $video['url'] );
			break;
		case 'short_code':
			$short_code = \Academy\Helper::get_content_html( stripslashes( $lesson_meta['video_source']['url'] ) );
			$template_path = 'curriculums/lesson/shortcode.php';
			$template_args = [ 'shortcode' => $short_code ];
			break;
		case 'offline':
		case 'online':
			$template_path = 'curriculums/lesson/online.php';
			$meta = ! empty( $lesson_meta['video_source']['url'] ) ? $lesson_meta['video_source']['url'] : \Academy\Helper::get_settings( 'lesson_offline_class_address' );
			$template_args = [ 'meta' => $meta ];
			break;
	}//end switch

	// Route controllable providers through the shared lesson-player template,
	// injecting the student's resume position + the completion-gate config.
	if ( $academy_provider ) {
		$academy_user_id  = (int) get_current_user_id();
		$academy_position = 0;
		if ( $academy_user_id ) {
			$academy_saved = get_user_meta( $academy_user_id, "academy_{$course_id}lesson_video_{$lesson->ID}_progress", true );
			if ( is_array( $academy_saved ) ) {
				$academy_position = (float) ( $academy_saved['position'] ?? 0 );
			}
		}
		$academy_threshold = (int) \Academy\Helper::get_settings( 'lessons_video_completion_threshold' );
		// The lesson thumbnail doubles as the video poster — the player shows it
		// for embedded providers too, not just html5.
		$academy_poster = ! empty( $lesson_meta['featured_media'] )
			? (string) wp_get_attachment_url( $lesson_meta['featured_media'] )
			: '';

		$template_path = 'curriculums/lesson/academy-player.php';
		$template_args = [
			'provider'  => $academy_provider,
			'src'       => $academy_src,
			'course_id' => $course_id,
			'topic_id'  => $lesson->ID,
			'next_url'  => $next_topic_play_url,
			'resume'    => $academy_position,
			'threshold' => $academy_threshold,
			'lock_seek' => ( $academy_threshold > 0 && \Academy\Helper::get_settings( 'is_disabled_lessons_video_skip' ) ),
			'poster'    => $academy_poster,
			// Lessons live in their own table, so the title comes off the row —
			// get_the_title() would resolve against wp_posts and return nothing.
			'title'     => isset( $lesson->lesson_title ) ? $lesson->lesson_title : '',
			// "Auto Play (HTML5 Videos)" — scoped to self-hosted media, matching
			// the setting's own wording; embeds keep their click-to-start.
			'autoplay'  => ( 'html5' === $academy_provider && \Academy\Helper::get_settings( 'lesson_self_hosted_video_autoplay', true ) ),
		];
	}//end if

	if ( $template_path ) {
		\Academy\Helper::get_template( $template_path, $template_args );
	}

	// featured image
} elseif ( $has_featured_image ) {
	\Academy\Helper::get_template( 'curriculums/lesson/featured-image.php', [ 'url' => wp_get_attachment_url( $lesson_meta['featured_media'] ) ] );
}//end if

// content
$content = '';
if ( 'publish' === $status ) {
	$content = \Academy\Helper::get_content_html( stripslashes( $lesson->lesson_content ) );
}

// Lesson has no video, no featured image, and no written content — the React
// player shows a "Content Not Found!" state for this same case (TextContent
// component); the PHP path had nothing, leaving a blank content area.
if ( ! $has_video && ! $has_featured_image && '' === trim( wp_strip_all_tags( $content ) ) ) {
	\Academy\Helper::get_template( 'curriculums/not-found.php' );
	return;
}

\Academy\Helper::get_template( 'curriculums/lesson/content.php', [ 'content' => $content ] );

// attachment
if ( 'publish' === $status && ! empty( $lesson_meta['attachment'] ) ) {
	\Academy\Helper::get_template( 'curriculums/lesson/attachment.php', [ 'attachment_id' => $lesson_meta['attachment'] ] );
}
