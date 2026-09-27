<?php
/**
 * Course Price block.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$academy_course = \Academy\Blocks\CourseData::get( isset( $block->context['postId'] ) ? $block->context['postId'] : get_the_ID() );
if ( ! $academy_course ) {
	return;
}

$academy_class = ! empty( $attributes['textAlign'] ) ? 'has-text-align-' . sanitize_key( $attributes['textAlign'] ) : '';
?>
<div <?php echo get_block_wrapper_attributes( [ 'class' => $academy_class ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?>><?php echo $academy_course['price']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized with wp_kses_post() in CourseData. ?></div>
