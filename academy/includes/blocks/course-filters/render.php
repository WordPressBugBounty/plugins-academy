<?php
/**
 * Course Filters block.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Blocks\CourseFilters as AcademyCourseFilters;

$academy_values = AcademyCourseFilters::current();
$academy_params = AcademyCourseFilters::PARAMS;

// On a category archive, start from that category.
$academy_current_term = AcademyCourseFilters::current_category();
if ( $academy_current_term && ! in_array( urldecode( $academy_current_term->slug ), $academy_values['category'], true ) ) {
	$academy_values['category'][] = urldecode( $academy_current_term->slug );
}

$academy_checkboxes = function ( $name, array $options, array $checked ) {
	$html = '<ul class="academy-course-filters__options">';
	foreach ( $options as $value => $option ) {
		$label    = is_array( $option ) ? $option['label'] : $option;
		$children = is_array( $option ) && ! empty( $option['children'] ) ? $option['children'] : [];
		$html    .= sprintf(
			'<li><label class="academy-course-filters__option"><input type="checkbox" name="%1$s[]" value="%2$s"%3$s /> <span>%4$s</span></label>',
			esc_attr( $name ),
			esc_attr( $value ),
			checked( in_array( (string) $value, $checked, true ), true, false ),
			esc_html( $label )
		);
		if ( $children ) {
			$html .= '<ul class="academy-course-filters__options academy-course-filters__options--children">';
			foreach ( $children as $child_value => $child_label ) {
				$html .= sprintf(
					'<li><label class="academy-course-filters__option"><input type="checkbox" name="%1$s[]" value="%2$s"%3$s /> <span>%4$s</span></label></li>',
					esc_attr( $name ),
					esc_attr( $child_value ),
					checked( in_array( (string) $child_value, $checked, true ), true, false ),
					esc_html( $child_label )
				);
			}
			$html .= '</ul>';
		}
		$html .= '</li>';
	}//end foreach

	return $html . '</ul>';
};

$academy_categories = [];
if ( ! empty( $attributes['showCategories'] ) ) {
	foreach ( (array) \Academy\Helper::get_all_courses_category_lists() as $academy_term ) {
		if ( ! is_object( $academy_term ) || empty( $academy_term->slug ) ) {
			continue;
		}
		$academy_children = [];
		foreach ( ! empty( $academy_term->children ) ? (array) $academy_term->children : [] as $academy_child ) {
			if ( is_object( $academy_child ) && ! empty( $academy_child->slug ) ) {
				$academy_children[ urldecode( $academy_child->slug ) ] = $academy_child->name;
			}
		}
		$academy_categories[ urldecode( $academy_term->slug ) ] = [
			'label'    => $academy_term->name,
			'children' => $academy_children,
		];
	}
}

$academy_search_id = wp_unique_id( 'academy-course-search-' );
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?>>
	<form class="academy-course-filters__form" method="get" action="<?php echo esc_url( AcademyCourseFilters::filter_action() ); ?>" data-academy-autosubmit="<?php echo ! empty( $attributes['autoApply'] ) ? '1' : '0'; ?>" aria-label="<?php esc_attr_e( 'Filter courses', 'academy' ); ?>">
		<?php echo AcademyCourseFilters::hidden_inputs( [ $academy_params['category'], $academy_params['level'], $academy_params['type'], $academy_params['search'] ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in hidden_inputs(). ?>

		<?php if ( ! empty( $attributes['showSearch'] ) ) : ?>
			<div class="academy-course-filters__group">
				<label class="academy-course-filters__title" for="<?php echo esc_attr( $academy_search_id ); ?>"><?php esc_html_e( 'Search', 'academy' ); ?></label>
				<input class="academy-course-filters__search" type="search" id="<?php echo esc_attr( $academy_search_id ); ?>" name="<?php echo esc_attr( $academy_params['search'] ); ?>" value="<?php echo esc_attr( $academy_values['search'] ); ?>" placeholder="<?php esc_attr_e( 'Search courses…', 'academy' ); ?>" />
			</div>
		<?php endif; ?>

		<?php if ( $academy_categories ) : ?>
			<fieldset class="academy-course-filters__group">
				<legend class="academy-course-filters__title"><?php esc_html_e( 'Category', 'academy' ); ?></legend>
				<?php echo $academy_checkboxes( $academy_params['category'], $academy_categories, $academy_values['category'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the closure. ?>
			</fieldset>
		<?php endif; ?>

		<?php if ( ! empty( $attributes['showLevels'] ) ) : ?>
			<fieldset class="academy-course-filters__group">
				<legend class="academy-course-filters__title"><?php esc_html_e( 'Level', 'academy' ); ?></legend>
				<?php echo $academy_checkboxes( $academy_params['level'], AcademyCourseFilters::levels(), $academy_values['level'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the closure. ?>
			</fieldset>
		<?php endif; ?>

		<?php if ( ! empty( $attributes['showTypes'] ) ) : ?>
			<fieldset class="academy-course-filters__group">
				<legend class="academy-course-filters__title"><?php esc_html_e( 'Price', 'academy' ); ?></legend>
				<?php echo $academy_checkboxes( $academy_params['type'], AcademyCourseFilters::types(), $academy_values['type'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the closure. ?>
			</fieldset>
		<?php endif; ?>

		<div class="academy-course-filters__actions">
			<button type="submit" class="wp-element-button academy-course-filters__submit"><?php esc_html_e( 'Apply filters', 'academy' ); ?></button>
			<?php if ( AcademyCourseFilters::has_filters( AcademyCourseFilters::current() ) ) : ?>
				<a class="academy-course-filters__clear" href="<?php echo esc_url( AcademyCourseFilters::clear_url() ); ?>"><?php esc_html_e( 'Clear filters', 'academy' ); ?></a>
			<?php endif; ?>
		</div>
	</form>
</div>
