<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="academy-archive-course-widget academy-archive-course-widget--type">
	<h4 class="academy-archive-course-widget__title"><?php esc_html_e( 'Type', 'academy' ); ?>
	</h4>
	<div class="academy-archive-course-widget__body">
		<?php
		foreach ( $type as $key => $type_name ) :
			?>
		<label>
			<span><?php echo esc_html( $type_name ); ?></span>
			<input class="academy-archive-course-filter" type="checkbox" name="type"
				value="<?php echo esc_attr( $key ); ?>" />
			<span class="checkmark"></span>
		</label>
			<?php
			endforeach;
		?>
	</div>
</div>
