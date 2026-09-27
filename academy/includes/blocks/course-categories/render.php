<?php
/**
 * Course Categories block.
 *
 * One list of categories drawn in any of several layouts. Blocks saved with
 * the older style presets (Pills, List, Image tiles) keep their look until a
 * layout is chosen.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$academy_layouts = [ 'cards', 'horizontal', 'image-cards', 'overlay', 'featured', 'circles', 'carousel', 'pills', 'list' ];
$academy_layout  = $attributes['layout'] ?? '';
if ( ! in_array( $academy_layout, $academy_layouts, true ) ) {
	$academy_layout = 'cards';
	if ( preg_match( '/\bis-style-(pills|list|overlay)\b/', $attributes['className'] ?? '', $academy_match ) ) {
		$academy_layout = $academy_match[1];
	}
}

$academy_columns = max( 1, min( 8, (int) $attributes['columns'] ) );
$academy_args    = [
	'taxonomy'   => 'academy_courses_category',
	'hide_empty' => ! empty( $attributes['hideEmpty'] ),
	'number'     => max( 1, min( 100, (int) $attributes['max'] ) ),
	'orderby'    => 'count',
	'order'      => 'DESC',
];
if ( ! empty( $attributes['parentOnly'] ) ) {
	$academy_args['parent'] = 0;
}
$academy_terms = get_terms( $academy_args );
if ( empty( $academy_terms ) || is_wp_error( $academy_terms ) ) {
	return;
}

// Layouts where the picture is the tile get a larger image.
$academy_big_image = in_array( $academy_layout, [ 'image-cards', 'overlay', 'featured', 'carousel', 'circles' ], true );
$academy_show_desc = ! empty( $attributes['showDescription'] ) && ! in_array( $academy_layout, [ 'pills', 'circles' ], true );
$academy_arrow     = ! empty( $attributes['showArrow'] ) && in_array( $academy_layout, [ 'horizontal', 'list' ], true );

// Categories without a picture get a coloured tile with their first letter,
// each in the next colour of a small set so a row doesn't look uniform.
$academy_tones = [ '#6c5ce7', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#ec4899', '#14b8a6', '#8b5cf6' ];

$academy_wrapper = get_block_wrapper_attributes(
	[
		'class' => 'academy-categories--' . $academy_layout,
		'style' => '--academy-category-columns:' . $academy_columns . ';',
	]
);
?>
<ul <?php echo $academy_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?>>
	<?php
	foreach ( $academy_terms as $academy_index => $academy_term ) :
		$academy_image_id  = (int) get_term_meta( $academy_term->term_id, 'academy_category_image', true );
		$academy_image_url = $academy_image_id ? wp_get_attachment_image_url( $academy_image_id, $academy_big_image ? 'medium_large' : 'thumbnail' ) : '';
		$academy_term_link = get_term_link( $academy_term );
		if ( is_wp_error( $academy_term_link ) ) {
			continue;
		}
		$academy_initial   = function_exists( 'mb_substr' ) ? mb_strtoupper( mb_substr( $academy_term->name, 0, 1 ) ) : strtoupper( substr( $academy_term->name, 0, 1 ) );
		?>
		<li class="academy-course-categories__item" style="--academy-cat-tone:<?php echo esc_attr( $academy_tones[ $academy_index % count( $academy_tones ) ] ); ?>">
			<a class="academy-course-categories__link" href="<?php echo esc_url( $academy_term_link ); ?>">
				<?php if ( ! empty( $attributes['showImage'] ) || in_array( $academy_layout, [ 'overlay', 'featured' ], true ) ) : ?>
					<?php if ( $academy_image_url ) : ?>
						<span class="academy-course-categories__media">
							<img class="academy-course-categories__image" src="<?php echo esc_url( $academy_image_url ); ?>" alt="" loading="lazy" />
						</span>
					<?php else : ?>
						<span class="academy-course-categories__media is-empty" aria-hidden="true">
							<span class="academy-course-categories__initial"><?php echo esc_html( $academy_initial ); ?></span>
						</span>
					<?php endif; ?>
				<?php endif; ?>
				<span class="academy-course-categories__text">
					<span class="academy-course-categories__name"><?php echo esc_html( $academy_term->name ); ?></span>
					<?php if ( $academy_show_desc && '' !== trim( $academy_term->description ) ) : ?>
						<span class="academy-course-categories__description"><?php echo esc_html( wp_trim_words( $academy_term->description, 14 ) ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $attributes['showCount'] ) ) : ?>
						<span class="academy-course-categories__count">
							<?php
							/* translators: %s: number of courses. */
							echo esc_html( sprintf( _n( '%s course', '%s courses', $academy_term->count, 'academy' ), number_format_i18n( $academy_term->count ) ) );
							?>
						</span>
					<?php endif; ?>
				</span>
				<?php if ( $academy_arrow ) : ?>
					<span class="academy-course-categories__arrow" aria-hidden="true">
						<svg viewBox="0 0 24 24" width="18" height="18"><path d="M9 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</span>
				<?php endif; ?>
			</a>
		</li>
	<?php endforeach; ?>
</ul>
