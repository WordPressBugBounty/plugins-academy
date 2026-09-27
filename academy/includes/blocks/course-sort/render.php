<?php
/**
 * Course Sort block.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Blocks\CourseFilters as AcademyCourseFilters;

$academy_values  = AcademyCourseFilters::current();
// The query is already sorted by the site's configured archive order even
// when the URL has no `academy_sort` — show that as the selected option
// instead of the generic "Default" placeholder, which looked like no sort
// was applied at all.
$academy_selected_sort = '' !== $academy_values['sort'] ? $academy_values['sort'] : AcademyCourseFilters::default_sort();
$academy_select  = wp_unique_id( 'academy-course-sort-' );
$academy_count   = null;
$academy_archive = is_post_type_archive( 'academy_courses' ) || is_tax( [ 'academy_courses_category', 'academy_courses_tag' ] );
if ( ! empty( $attributes['showCount'] ) && $academy_archive ) {
	$academy_count = (int) $GLOBALS['wp_query']->found_posts;
}
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?>>
	<?php if ( null !== $academy_count ) : ?>
		<p class="academy-course-sort__count">
			<?php
			/* translators: %s: number of courses. */
			echo esc_html( sprintf( _n( '%s course', '%s courses', $academy_count, 'academy' ), number_format_i18n( $academy_count ) ) );
			?>
		</p>
	<?php endif; ?>
	<form class="academy-course-sort__form" method="get" action="<?php echo esc_url( AcademyCourseFilters::form_action() ); ?>" data-academy-autosubmit="1">
		<?php echo AcademyCourseFilters::hidden_inputs( [ AcademyCourseFilters::PARAMS['sort'] ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in hidden_inputs(). ?>
		<?php foreach ( [ 'category', 'level', 'type' ] as $academy_key ) : ?>
			<?php foreach ( $academy_values[ $academy_key ] as $academy_value ) : ?>
				<input type="hidden" name="<?php echo esc_attr( AcademyCourseFilters::PARAMS[ $academy_key ] ); ?>[]" value="<?php echo esc_attr( $academy_value ); ?>" />
			<?php endforeach; ?>
		<?php endforeach; ?>
		<label class="academy-course-sort__label<?php echo empty( $attributes['showLabel'] ) ? ' screen-reader-text' : ''; ?>" for="<?php echo esc_attr( $academy_select ); ?>"><?php esc_html_e( 'Sort by', 'academy' ); ?></label>
		<select class="academy-course-sort__select" id="<?php echo esc_attr( $academy_select ); ?>" name="<?php echo esc_attr( AcademyCourseFilters::PARAMS['sort'] ); ?>">
			<option value="" <?php selected( '', $academy_selected_sort ); ?>><?php esc_html_e( 'Default', 'academy' ); ?></option>
			<?php foreach ( AcademyCourseFilters::sort_options() as $academy_key => $academy_label ) : ?>
				<option value="<?php echo esc_attr( $academy_key ); ?>" <?php selected( $academy_selected_sort, $academy_key ); ?>><?php echo esc_html( $academy_label ); ?></option>
			<?php endforeach; ?>
		</select>
		<noscript><button type="submit" class="wp-element-button"><?php esc_html_e( 'Sort', 'academy' ); ?></button></noscript>
	</form>
</div>
