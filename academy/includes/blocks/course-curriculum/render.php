<?php
/**
 * Course Curriculum block. Renders the [academy_single_course_curriculums] section for the course in context.
 *
 * @var WP_Block $block Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo \Academy\Blocks::render_course_section( 'academy_single_course_curriculums', $block ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the shortcode templates and core.
