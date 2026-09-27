<?php
/**
 * Course Benefits & Requirements block.
 *
 * "Default" is the classic section: what you'll learn in a box, the rest in
 * tabs. "Tabs" puts every list in a tab; "Accordion" in panels that open one
 * at a time.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$academy_layout = in_array( $attributes['layout'] ?? 'default', [ 'default', 'tabs', 'accordion' ], true ) ? $attributes['layout'] : 'default';

if ( 'default' === $academy_layout ) {
	echo \Academy\Blocks::render_course_section( 'academy_single_course_addition_info', $block ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the shortcode templates and core.
	return;
}

$academy_course_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_the_ID();
if ( ! $academy_course_id || 'academy_courses' !== get_post_type( $academy_course_id ) ) {
	return;
}

$academy_sections = [];
foreach (
	[
		'benefits'     => [ 'academy_course_benefits', __( 'What You\'ll Learn', 'academy' ) ],
		'requirements' => [ 'academy_course_requirements', __( 'Requirements', 'academy' ) ],
		'audience'     => [ 'academy_course_audience', __( 'Targeted Audience', 'academy' ) ],
		'materials'    => [ 'academy_course_materials_included', __( 'Materials Included', 'academy' ) ],
	] as $academy_key => $academy_section
) {
	$academy_items = array_filter( array_map( 'trim', (array) \Academy\Helper::string_to_array( get_post_meta( $academy_course_id, $academy_section[0], true ) ) ) );
	if ( $academy_items ) {
		$academy_sections[ $academy_key ] = [
			'title' => $academy_section[1],
			'items' => $academy_items,
		];
	}
}
if ( ! $academy_sections ) {
	return;
}

\Academy\Blocks::use_course_assets();

$academy_uid     = wp_unique_id( 'academy-info-' );
$academy_open    = ! empty( $attributes['openFirst'] );
$academy_wrapper = get_block_wrapper_attributes(
	[
		'class' => 'is-layout-' . $academy_layout,
		'style' => \Academy\Blocks::academy_colors_style( $attributes ),
	]
);

$academy_list = static function ( $items ) {
	echo '<ul class="academy-info__list">';
	foreach ( $items as $item ) {
		echo '<li><span class="academy-icon academy-icon--check" aria-hidden="true"></span><span>' . esc_html( $item ) . '</span></li>';
	}
	echo '</ul>';
};
?>
<div <?php echo $academy_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?>>
	<?php if ( 'tabs' === $academy_layout ) : ?>
		<div class="academy-info academy-info--tabs" data-academy-tabs>
			<div class="academy-info__tabs" role="tablist">
				<?php $academy_i = 0; foreach ( $academy_sections as $academy_key => $academy_section ) : ?>
					<button type="button" role="tab" class="academy-info__tab" id="<?php echo esc_attr( $academy_uid . '-tab-' . $academy_key ); ?>" aria-controls="<?php echo esc_attr( $academy_uid . '-panel-' . $academy_key ); ?>" aria-selected="<?php echo 0 === $academy_i ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $academy_i ? '0' : '-1'; ?>">
						<?php echo esc_html( $academy_section['title'] ); ?>
						<span class="academy-info__count"><?php echo esc_html( number_format_i18n( count( $academy_section['items'] ) ) ); ?></span>
					</button>
					<?php ++$academy_i;
endforeach; ?>
			</div>
			<?php $academy_i = 0; foreach ( $academy_sections as $academy_key => $academy_section ) : ?>
				<div class="academy-info__panel" role="tabpanel" id="<?php echo esc_attr( $academy_uid . '-panel-' . $academy_key ); ?>" aria-labelledby="<?php echo esc_attr( $academy_uid . '-tab-' . $academy_key ); ?>" tabindex="0"<?php echo 0 === $academy_i ? '' : ' hidden'; ?>>
					<?php $academy_list( $academy_section['items'] ); ?>
				</div>
				<?php ++$academy_i;
endforeach; ?>
		</div>
	<?php else : ?>
		<div class="academy-info academy-info--accordion" data-academy-accordion>
			<?php $academy_i = 0; foreach ( $academy_sections as $academy_section ) : ?>
				<details class="academy-info__item"<?php echo ( 0 === $academy_i && $academy_open ) ? ' open' : ''; ?>>
					<summary class="academy-info__summary">
						<span class="academy-info__title"><?php echo esc_html( $academy_section['title'] ); ?></span>
						<span class="academy-info__count"><?php echo esc_html( number_format_i18n( count( $academy_section['items'] ) ) ); ?></span>
						<svg class="academy-info__chevron" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</summary>
					<div class="academy-info__body"><?php $academy_list( $academy_section['items'] ); ?></div>
				</details>
				<?php ++$academy_i;
endforeach; ?>
		</div>
	<?php endif; ?>
</div>
