<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

	global $authordata;
?>

<div class="academy-course__body">
	<?php
		/**
		 * Hook - academy/templates/before_course_loop_content_inner
		 */
		do_action( 'academy/templates/before_course_loop_content_inner' );
		$categories = \Academy\Helper::get_the_course_category( get_the_ID() );
	if ( ! empty( $categories ) ) {
		echo '<span class="academy-course__meta academy-course__meta--categroy"><a href="' . esc_url( get_term_link( $categories[0]->term_id ) ) . '">' . esc_html( $categories[0]->name ) . '</a></span>';
	}
	?>
	<h4 class="academy-course__title"><a href="<?php echo esc_url( get_the_permalink() ); ?>"><?php the_title(); ?></a></h4>
	<div class="academy-course__author-meta">
		<div class="academy-course__author">
			<span class="author"><?php esc_html_e( 'BY -', 'academy' ); ?>
				<?php
				if ( Academy\Helper::get_settings( 'is_show_public_profile' ) ) :
					?>
				<a href="<?php echo esc_url( home_url( '/author/' . $authordata->user_nicename ) ); ?>">
					<?php echo get_the_author(); ?>
				</a>
				<?php else : ?>
					<?php echo get_the_author(); ?>
				<?php endif; ?>
			</span>
		</div>
	</div>
	<?php
		do_action( 'academy/templates/after_course_loop_content_inner' );
	?>
</div>
