<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="academy-archive-course-widget academy-archive-course-widget--levels">
	<h4 class="academy-archive-course-widget__title"><?php esc_html_e( 'Level', 'academy' ); ?>
	</h4>
	<div class="academy-archive-course-widget__body">
		<?php
		foreach ( $levels as $key => $label ) :
			?>
		<label>
			<span><?php echo esc_html( $label ); ?></span>
			<input class="academy-archive-course-filter" type="checkbox" name="levels"
				value="<?php echo esc_attr( $key ); ?>" />
			<span class="checkmark"></span>
		</label>
			<?php
		endforeach;
		?>
	</div>
</div>
