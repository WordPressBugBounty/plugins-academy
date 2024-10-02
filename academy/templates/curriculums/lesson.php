<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// load lesson title if it is enabled
if ( \Academy\Helper::get_settings( 'is_enabled_lessons_content_title' ) ) {
	\Academy\Helper::get_template( 'curriculums/lesson/title.php', [ 'lesson' => $lesson ] );
}

if ( ! empty( $lesson_meta['video_source']['type'] ) ) {
	$template_path = '';
	$template_args = [];

	switch ( $lesson_meta['video_source']['type'] ) {
		case 'youtube':
			$template_path = 'curriculums/lesson/youtube.php';
			$template_args = [ 'url' => $lesson_meta['video_source']['url'] ];
			break;

		case 'vimeo':
			$template_path = 'curriculums/lesson/vimeo.php';
			$template_args = [ 'url' => $lesson_meta['video_source']['url'] ];
			break;

		case 'html5':
			$url = wp_get_attachment_url( $lesson_meta['video_source']['id'] );
			$template_path = 'curriculums/lesson/html5.php';
			$template_args = [ 'url' => $url ];
			break;

		case 'external':
		case 'embedded':
			$video = $lesson_meta['video_source'];
			// first check external URL contain html5 video or not
			if ( \Academy\Helper::is_html5_video_link( $video['url'] ) ) {
				$video['type'] = 'html5';
				$embed_url = \Academy\Helper::get_basic_url_to_embed_url( $video['url'] );
				if ( isset( $embed_url['url'] ) && ! empty( $embed_url['url'] ) ) {
					$video['url'] = $embed_url['url'];
				}
			} else {
				$video['url'] = \Academy\Helper::get_basic_url_to_embed_url( $video['url'] );
			}

			$template_path = 'curriculums/lesson/' . ( 'html5' === $video['type'] ? 'html5.php' : 'external.php' );
			$template_args = $video['url'];
			break;
		case 'short_code':
			$short_code = \Academy\Helper::get_content_html( stripslashes( $lesson_meta['video_source']['url'] ) );
			$template_path = 'curriculums/lesson/shortcode.php';
			$template_args = [ 'shortcode' => $short_code ];
			break;
	}//end switch

	if ( $template_path ) {
		\Academy\Helper::get_template( $template_path, $template_args );
	}
}//end if

// content
$content = \Academy\Helper::get_content_html( stripslashes( $lesson->lesson_content ) );
\Academy\Helper::get_template( 'curriculums/lesson/content.php', [ 'content' => $content ] );

// attachment
if ( $lesson_meta['attachment'] ) {
	\Academy\Helper::get_template( 'curriculums/lesson/attachment.php', [ 'attachment_id' => $lesson_meta['attachment'] ] );
}
