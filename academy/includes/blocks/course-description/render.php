<?php
/**
 * Course Description block. Renders the [academy_single_course_description] section for the course in context.
 *
 * @var WP_Block $block Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo \Academy\Blocks::render_course_section( 'academy_single_course_description', $block ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the shortcode templates and core.
