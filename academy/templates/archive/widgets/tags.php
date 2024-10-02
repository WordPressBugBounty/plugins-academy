<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( count( $tags ) ) :
	?>
<div class="academy-archive-course-widget academy-archive-course-widget--tags">
	<h4 class="academy-archive-course-widget__title"><?php esc_html_e( 'Tag', 'academy' ); ?>
	</h4>
	<div class="academy-archive-course-widget__body">
	<?php
	foreach ( $tags as $tag_item ) :
		?>
		<label>
			<span><?php echo esc_html( $tag_item->name ); ?></span>
			<input class="academy-archive-course-filter" type="checkbox" name="tags"
				value="<?php echo esc_attr( $tag_item->slug ); ?>" />
			<span class="checkmark"></span>
		</label>
		<?php
		endforeach;
	?>
	</div>
</div>
<?php endif;
