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

	$course_id  = get_the_ID();
	$categories = \Academy\Helper::get_the_course_category( $course_id );
	$rating     = \Academy\Helper::get_course_rating( $course_id );
	$reviews_status = Academy\Helper::get_settings( 'is_enabled_course_review', true );

	// Display course category if available
	if ( ! empty( $categories ) ) {
		echo '<p class="academy-course__meta academy-course__meta--category"><a href="' . esc_url( get_term_link( $categories[0]->term_id ) ) . '">' . esc_html( $categories[0]->name ) . '</a></p>';
	}
	?>

	<div class="academy-course__content">
		<h4 class="academy-course__title">
			<a href="<?php echo esc_url( get_the_permalink() ); ?>">
				<?php the_title(); ?>
			</a>
		</h4>
		<?php if ( 'alms_course_bundle' !== get_post_type( $course_id ) ) : ?>
			<div class="academy-course__rating">
				<?php
				if ( $reviews_status ) :
					// Display average rating
					echo esc_html( $rating->rating_avg );
					echo wp_kses_post( \Academy\Helper::star_rating_generator( $rating->rating_avg ) );
					?>
					<span class="academy-course__rating-count">
						<?php echo esc_html( '(' . $rating->rating_count . ')' ); ?>
					</span>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<?php
		Academy\Helper::get_template(
			'loop/author.php'
		); ?>
	</div>

	<?php
	/**
	 * Hook - academy/templates/after_course_loop_content_inner
	 */
	do_action( 'academy/templates/after_course_loop_content_inner' );
	?>
</div>
