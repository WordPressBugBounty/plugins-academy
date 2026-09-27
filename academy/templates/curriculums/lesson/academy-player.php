<?php
/**
 * Lesson-player mount for the PHP-rendered learn page. The player + progress
 * wiring is handled in frontendPhpCurriculums.js, driven by these data-attrs.
 *
 * @var string $provider  html5 | youtube | vimeo
 * @var string $src       media URL (html5) or video id (youtube/vimeo)
 * @var int    $course_id
 * @var int    $topic_id
 * @var string $next_url
 * @var float  $resume
 * @var int    $threshold
 * @var bool   $lock_seek
 * @var string $poster    lesson thumbnail, shown before playback
 * @var string $title     lesson title
 * @var bool   $autoplay  start playing as soon as the media is ready
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="academy-lessons-content__video">
	<div
		class="academy-lesson-player"
		data-academy-lesson-player="1"
		data-provider="<?php echo esc_attr( $provider ); ?>"
		data-src="<?php echo esc_attr( $src ); // phpcs:ignore WordPressVIPMinimum.Security.ProperEscapingFunction.hrefSrcEscUrl -- a YouTube/Vimeo video id for those providers, not always a URL. ?>"
		data-course-id="<?php echo esc_attr( $course_id ); ?>"
		data-topic-id="<?php echo esc_attr( $topic_id ); ?>"
		data-next-url="<?php echo esc_url( $next_url ); ?>"
		data-resume="<?php echo esc_attr( $resume ); ?>"
		data-threshold="<?php echo esc_attr( $threshold ); ?>"
		data-lock-seek="<?php echo $lock_seek ? '1' : '0'; ?>"
		data-poster="<?php echo esc_url( $poster ); ?>"
		data-title="<?php echo esc_attr( $title ); ?>"
		data-autonext="<?php echo \Academy\Helper::is_auto_load_next_lesson() ? '1' : '0'; ?>"
		data-autocomplete="<?php echo \Academy\Helper::is_auto_complete_topic() ? '1' : '0'; ?>"
		data-autoplay="<?php echo $autoplay ? '1' : '0'; ?>"
	></div>
</div>
