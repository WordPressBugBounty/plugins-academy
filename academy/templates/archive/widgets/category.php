<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


$queried_object = get_queried_object();
$selected_category = ( $queried_object && isset( $queried_object->slug ) ) ? $queried_object->slug : '';

if ( count( $categories ) ) :
	?>
<div class="academy-archive-course-widget academy-archive-course-widget--category">
	<h4 class="academy-archive-course-widget__title"><?php esc_html_e( 'Category', 'academy' ); ?>
	</h4>
	<div class="academy-archive-course-widget__body">
		<?php
		foreach ( $categories as $parent_category ) :
			?>
		<label class="parent-term">
			<span><?php echo esc_html( $parent_category->name ); ?></span>
			<input class="academy-archive-course-filter" type="checkbox" name="category"
				value="<?php echo esc_attr( urldecode( $parent_category->slug ) ); ?>" <?php checked( urldecode( $parent_category->slug ), $selected_category, true ); ?> />
			<span class="checkmark"></span>
		</label>
			<?php
			if ( count( $parent_category->children ) ) :
				foreach ( $parent_category->children as $child_category ) :
					?>
					<label class="child-term">
					<span><?php echo esc_html( $child_category->name ); ?></span>
						<input class="academy-archive-course-filter" type="checkbox" name="category"
							value="<?php echo esc_attr( urldecode( $child_category->slug ) ); ?>" <?php checked( urldecode( $child_category->slug ), $selected_category, true ); ?> />
						<span class="checkmark"></span>
					</label>
					<?php
				endforeach;
		endif;
		endforeach;
		?>
	</div>
</div>
	<?php
endif;
