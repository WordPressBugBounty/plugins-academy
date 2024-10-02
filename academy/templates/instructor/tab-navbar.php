<ul class="academy-tabs-nav">
	<?php do_action( 'academy/templates/instructor/tabs_add_nav_before' ); ?>
	<li class="active"><a href="#courses"><?php esc_html_e( 'Courses', 'academy' ); ?></a>
	</li>
	<?php
	if ( $is_enabled_instructor_review ) :
		?>
	<li class=""><a href="#reviews"><?php esc_html_e( 'Reviews', 'academy' ); ?></a>
	</li>
		<?php
		endif;
	?>
	<?php do_action( 'academy/templates/instructor/tabs_add_nav_after' ); ?>
</ul>
