<?php
/**
 * Course Rating block.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$academy_course = \Academy\Blocks\CourseData::get( isset( $block->context['postId'] ) ? $block->context['postId'] : get_the_ID() );
if ( ! $academy_course || ! $academy_course['reviews_enabled'] ) {
	return;
}

$academy_average = (float) $academy_course['rating']['average'];
$academy_count   = (int) $academy_course['rating']['count'];
if ( ! empty( $attributes['hideWhenEmpty'] ) && 0 === $academy_count ) {
	return;
}

// Filled and empty star colours: hex, rgb(a) or a theme palette colour.
$academy_style = '';
foreach ( [
	'starColor' => '--academy-star-color',
	'emptyStarColor' => '--academy-star-empty-color'
] as $academy_key => $academy_var ) {
	if ( ! empty( $attributes[ $academy_key ] ) && preg_match( '/^(#[0-9a-f]{3,8}|rgba?\([\d\s.,%]+\)|var\(--wp--preset--color--[a-z0-9-]+\))$/i', $attributes[ $academy_key ] ) ) {
		$academy_style .= $academy_var . ':' . $attributes[ $academy_key ] . ';';
	}
}
// One star: filled once the course has a rating, beside the average.
$academy_single = isset( $attributes['starCount'] ) && 'one' === $attributes['starCount'];
$academy_fill   = $academy_single ? ( $academy_count > 0 ? 100 : 0 ) : max( 0, min( 100, $academy_average / 5 * 100 ) );
$academy_wrapper = get_block_wrapper_attributes(
	[
		'style' => $academy_style,
		'class' => $academy_single ? 'is-single-star' : '',
	]
);
?>
<div <?php echo $academy_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?>>
	<span class="academy-course-rating__stars" role="img" aria-label="
	<?php
	/* translators: %s: average rating, e.g. 4.5. */
	echo esc_attr( sprintf( __( 'Rated %s out of 5', 'academy' ), number_format_i18n( $academy_average, 1 ) ) );
	?>
	"><span class="academy-course-rating__fill" style="width:<?php echo esc_attr( $academy_fill ); ?>%"></span></span>
	<?php if ( ! empty( $attributes['showAverage'] ) ) : ?>
		<span class="academy-course-rating__average"><?php echo esc_html( number_format_i18n( $academy_average, 1 ) ); ?></span>
	<?php endif; ?>
	<?php if ( ! empty( $attributes['showCount'] ) ) : ?>
		<span class="academy-course-rating__count">
		<?php
		/* translators: %s: number of reviews. */
		echo esc_html( sprintf( _n( '(%s review)', '(%s reviews)', $academy_count, 'academy' ), number_format_i18n( $academy_count ) ) );
		?>
		</span>
	<?php endif; ?>
</div>
