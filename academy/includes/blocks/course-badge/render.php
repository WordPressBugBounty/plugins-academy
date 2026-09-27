<?php
/**
 * Course Featured Badge block.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$academy_course = \Academy\Blocks\CourseData::get( isset( $block->context['postId'] ) ? $block->context['postId'] : get_the_ID() );
if ( ! $academy_course || empty( $academy_course['is_sticky'] ) ) {
	return;
}
?>
<span <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?>><?php echo esc_html( ! empty( $attributes['text'] ) ? $attributes['text'] : __( 'Featured', 'academy' ) ); ?></span>
