<div class="academy-tabs-content">
	<?php do_action( 'academy/templates/instructor/tabs_add_content_before' ); ?>
	<div id="courses">
		<?php do_action( 'academy/templates/instructor/tabs_content_courses' ); ?>
	</div>
	<?php
	if ( $is_enabled_instructor_review ) :
		?>
	<div id="reviews">
		<?php do_action( 'academy/templates/instructor/tabs_content_reviews' ); ?>
	</div>
		<?php
		endif;
	?>
	<?php do_action( 'academy/templates/instructor/tabs_add_content_after' ); ?>
</div>
