<?php
/**
 * Course Intro Media block. Renders the [academy_course_featured_image] section for the course in context.
 *
 * @var WP_Block $block Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$academy_width   = \Academy\Blocks::css_width( $attributes['width'] ?? '' );
$academy_justify = in_array( $attributes['justify'] ?? 'left', [ 'left', 'center', 'right' ], true ) ? $attributes['justify'] : 'left';

$academy_html = \Academy\Blocks::render_course_section(
	'academy_course_featured_image',
	$block,
	null,
	$academy_width ? [
		'class' => 'is-justified-' . $academy_justify,
		'style' => 'width:' . $academy_width . ';max-width:100%;',
	] : []
);

echo $academy_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the shortcode templates and core.
