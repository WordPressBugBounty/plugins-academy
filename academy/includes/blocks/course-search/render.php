<?php
/**
 * Course Search block.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$academy_archive = get_post_type_archive_link( 'academy_courses' );
if ( ! $academy_archive ) {
	return;
}

$academy_input_id = wp_unique_id( 'academy-course-search-field-' );
$academy_value    = isset( $_GET['academy_search'] ) ? sanitize_text_field( wp_unslash( $_GET['academy_search'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only search.
$academy_button   = ! empty( $attributes['buttonText'] ) ? $attributes['buttonText'] : __( 'Search', 'academy' );

$academy_width   = \Academy\Blocks::css_width( $attributes['width'] ?? '' );
$academy_justify = in_array( $attributes['justify'] ?? 'left', [ 'left', 'center', 'right' ], true ) ? $attributes['justify'] : 'left';
$academy_classes = trim( ( ! empty( $attributes['showButton'] ) ? 'has-button ' : '' ) . 'is-justified-' . $academy_justify );
$academy_wrapper = get_block_wrapper_attributes(
	[
		'class' => $academy_classes,
		'style' => $academy_width ? 'width:' . $academy_width . ';' : '',
	]
);
?>
<form <?php echo $academy_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?> role="search" method="get" action="<?php echo esc_url( $academy_archive ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $academy_input_id ); ?>"><?php esc_html_e( 'Search courses', 'academy' ); ?></label>
	<input class="academy-course-search__input" type="search" id="<?php echo esc_attr( $academy_input_id ); ?>" name="academy_search" value="<?php echo esc_attr( $academy_value ); ?>" placeholder="<?php echo esc_attr( ! empty( $attributes['placeholder'] ) ? $attributes['placeholder'] : __( 'Search courses…', 'academy' ) ); ?>" required />
	<?php if ( ! empty( $attributes['showButton'] ) ) : ?>
		<button class="academy-course-search__button wp-element-button" type="submit"><?php echo esc_html( $academy_button ); ?></button>
	<?php endif; ?>
</form>
