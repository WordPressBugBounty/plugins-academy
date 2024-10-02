<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( 0 === count( $tabs_nav ) ) {
	return;
}
?>
<div class="academy-single-course__content-item academy-single-course__content-item--additional-info">
	<ul class="academy-tabs-nav">
		<?php
			$nav_count = 0;
		foreach ( $tabs_nav as $nav_key => $nav_name ) {
			$class_name = '';
			if ( 0 === $nav_count ) {
				$class_name = 'active';
				$nav_count++;
			} ?>
				<li class="<?php echo esc_attr( $class_name ); ?>"><a href="<?php echo esc_attr( '#' . $nav_key ); ?>"><?php echo esc_html( $nav_name ); ?></a></li>
				<?php
		}
		?>
	</ul>
	<div class="academy-tabs-content">
		<?php
		foreach ( $tabs_content as $tab_id => $tab_contents ) {
			?>
			<div id="<?php echo esc_attr( $tab_id ); ?>">
				<ul class="academy-lists">
				<?php
				if ( is_array( $tab_contents ) ) {
					foreach ( $tab_contents as $tab_content ) {
						?>
								<li>
									<i class="academy-icon academy-icon--check"></i>
									<span> <?php echo esc_html( $tab_content ); ?></span>
								</li>
							<?php
					}
				} ?>
				</ul>
			</div>
			<?php
		}
		?>
	</div>
</div>
