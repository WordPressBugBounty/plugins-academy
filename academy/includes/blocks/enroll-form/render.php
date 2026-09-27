<?php
/**
 * Course Enroll Form block.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$academy_course_id = ! empty( $attributes['courseId'] ) ? (int) $attributes['courseId'] : ( isset( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_the_ID() );
if ( 'academy_courses' !== get_post_type( $academy_course_id ) ) {
	return;
}

$academy_html = \Academy\Blocks::render_shortcode_block(
	'academy_enroll_form',
	[
		'course_id' => $academy_course_id,
		'layout'    => 'modern' === $attributes['layout'] ? 'modern' : 'legacy',
	],
	$block
);

echo $academy_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the shortcode templates and core.
