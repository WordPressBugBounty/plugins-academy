<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="academy-courses">
	<div class='academy-dashboard-wishlist-courses academy-dashboard__content'>
		<div class="academy-row">
			<?php
			if ( $wishlist_courses && $wishlist_courses->have_posts() ) :
				while ( $wishlist_courses->have_posts() ) :
							$wishlist_courses->the_post();
					?>
					<?php \Academy\Helper::get_template_part( 'content', 'course' ); ?>				
					<?php
					endwhile;
					wp_reset_query();
				else :
					?>
					<h3 class='academy-not-found'><?php esc_html_e( 'Your wishlist is empty. Save courses you like to find them here.', 'academy' ); ?></h3>
					<?php
				endif;
				?>
		</div>
	</div>
</div>
<?php
