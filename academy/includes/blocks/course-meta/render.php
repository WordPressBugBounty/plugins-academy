<?php
/**
 * Course Details block.
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

$academy_items = [];
if ( ! empty( $attributes['showLessons'] ) && $academy_course['lessons'] > 0 ) {
	/* translators: %s: number of lessons. */
	$academy_items['lessons'] = sprintf( _n( '%s lesson', '%s lessons', $academy_course['lessons'], 'academy' ), number_format_i18n( $academy_course['lessons'] ) );
}
if ( ! empty( $attributes['showDuration'] ) && '' !== $academy_course['duration'] ) {
	$academy_items['duration'] = $academy_course['duration'];
}
if ( ! empty( $attributes['showLevel'] ) && '' !== $academy_course['level'] ) {
	$academy_items['level'] = $academy_course['level'];
}
if ( ! empty( $attributes['showEnrolled'] ) ) {
	/* translators: %s: number of students. */
	$academy_items['enrolled'] = sprintf( _n( '%s student', '%s students', $academy_course['enrolled'], 'academy' ), number_format_i18n( $academy_course['enrolled'] ) );
}
if ( ! $academy_items ) {
	return;
}
?>
<ul <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?>>
	<?php foreach ( $academy_items as $academy_key => $academy_text ) : ?>
		<li class="academy-course-meta__item academy-course-meta__item--<?php echo esc_attr( $academy_key ); ?>">
			<?php
			if ( ! empty( $attributes['showIcons'] ) ) {
				echo \Academy\Blocks\CourseData::icon( $academy_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup.
			}
			?>
			<span><?php echo esc_html( $academy_text ); ?></span>
		</li>
	<?php endforeach; ?>
</ul>
