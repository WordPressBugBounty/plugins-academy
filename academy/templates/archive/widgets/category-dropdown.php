<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


$queried_object = get_queried_object();
$selected_category = ( $queried_object && isset( $queried_object->slug ) ) ? $queried_object->slug : '';
$selected_category_name = '';

if ( $selected_category ) {
	foreach ( $categories as $parent_category ) {
		if ( urldecode( $parent_category->slug ) === $selected_category ) {
			$selected_category_name = $parent_category->name;
			break;
		}
		if ( count( $parent_category->children ) ) {
			foreach ( $parent_category->children as $child_category ) {
				if ( urldecode( $child_category->slug ) === $selected_category ) {
					$selected_category_name = $child_category->name;
					break 2;
				}
			}
		}
	}
}

if ( count( $categories ) ) :
	?>
<div class="academy-archive-course-widget academy-archive-course-widget--category-dropdown">
	<h4 class="academy-archive-course-widget__title"><?php esc_html_e( 'Category', 'academy' ); ?>
	</h4>
	<div class="academy-archive-category-dropdown">
		<button type="button" class="academy-archive-category-dropdown__control" aria-haspopup="true" aria-expanded="false">
			<span class="academy-archive-category-dropdown__control-label" data-placeholder="<?php echo esc_attr__( 'Select Categories', 'academy' ); ?>"><?php echo esc_html( $selected_category_name ? $selected_category_name : __( 'Select Categories', 'academy' ) ); ?></span>
		</button>
		<div class="academy-archive-category-dropdown__panel academy-archive-course-widget__body" role="group" aria-label="<?php echo esc_attr__( 'Category', 'academy' ); ?>">
			<?php
			foreach ( $categories as $parent_category ) :
				?>
			<label class="parent-term">
				<input class="academy-archive-course-filter" type="checkbox" name="category"
					value="<?php echo esc_attr( urldecode( $parent_category->slug ) ); ?>" data-id="<?php echo esc_attr( $parent_category->term_id ); ?>" <?php checked( urldecode( $parent_category->slug ), $selected_category, true ); ?> />
				<span class="checkmark"></span>
				<img class="academy-archive-course-widget__category-thumb" src="<?php echo esc_url( \Academy\Helper::get_the_course_category_image_url( $parent_category->term_id ) ); ?>" alt="" />
				<span><?php echo esc_html( $parent_category->name ); ?></span>
			</label>
				<?php
				if ( count( $parent_category->children ) ) :
					foreach ( $parent_category->children as $child_category ) :
						?>
						<label class="child-term">
							<input class="academy-archive-course-filter" type="checkbox" name="category"
								value="<?php echo esc_attr( urldecode( $child_category->slug ) ); ?>" data-id="<?php echo esc_attr( $child_category->term_id ); ?>" <?php checked( urldecode( $child_category->slug ), $selected_category, true ); ?> />
							<span class="checkmark"></span>
							<img class="academy-archive-course-widget__category-thumb" src="<?php echo esc_url( \Academy\Helper::get_the_course_category_image_url( $child_category->term_id ) ); ?>" alt="" />
							<span><?php echo esc_html( $child_category->name ); ?></span>
						</label>
						<?php
					endforeach;
			endif;
			endforeach;
			?>
		</div>
	</div>
</div>
	<?php
endif;
