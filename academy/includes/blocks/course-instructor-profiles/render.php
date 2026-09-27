<?php
/**
 * Course Instructor Profiles block. Renders the [academy_course_instructors] section for the course in context.
 *
 * @var WP_Block $block Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo \Academy\Blocks::render_course_section( 'academy_course_instructors', $block ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the shortcode templates and core.
