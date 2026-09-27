<?php
/**
 * Course Review Form block. Renders the [academy_single_course_review_form] section for the course in context.
 *
 * @var WP_Block $block Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$academy_html = \Academy\Blocks::render_course_section(
	'academy_single_course_review_form',
	$block,
	// The editor preview: the form an enrolled student sees, opened.
	static function () {
		if ( ! (bool) \Academy\Helper::get_settings( 'is_enabled_course_review', true ) ) {
			return;
		}
		ob_start();
		\Academy\Helper::get_template( 'single-course/review-form.php' );
		// Opened, as after "Add Review".
		echo str_replace( 'class="academy-review-form"', 'class="academy-review-form academy-review-form--open-form"', (string) ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the escaped template.
	}
);

echo $academy_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the shortcode templates and core.
