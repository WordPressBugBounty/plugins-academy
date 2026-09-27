<?php
/**
 * Course Wishlist Button block.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$academy_course_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_the_ID();
if ( 'academy_courses' !== get_post_type( $academy_course_id ) || ! \Academy\Helper::get_settings( 'is_enabled_course_wishlist', true ) ) {
	return;
}

\Academy\Blocks::use_course_assets();

$academy_saved = is_user_logged_in() && in_array( (string) $academy_course_id, array_map( 'strval', (array) get_user_meta( get_current_user_id(), 'academy_course_wishlist', false ) ), true );
$academy_label = $academy_saved ? __( 'WishListed', 'academy' ) : __( 'WishList', 'academy' );
$academy_attrs = get_block_wrapper_attributes(
	[
		'class'           => 'academy-add-wishlist-btn' . ( $academy_saved ? ' is-saved' : '' ),
		'data-course-id'  => $academy_course_id,
		'data-show-label' => ! empty( $attributes['showLabel'] ) ? '1' : '',
		'aria-label'      => $academy_saved ? __( 'Remove from wishlist', 'academy' ) : __( 'Add to wishlist', 'academy' ),
	]
);
?>
<button type="button" <?php echo $academy_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?>><i class="academy-icon academy-icon--<?php echo $academy_saved ? 'heart' : 'heart-o'; ?>" aria-hidden="true"></i><?php echo ! empty( $attributes['showLabel'] ) ? esc_html( $academy_label ) : ''; ?></button>
