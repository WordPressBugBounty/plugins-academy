<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="academy-single-course__content-item academy-single-course__content-item--instructors">
	<?php
	foreach ( $instructors as $instructor ) :
		$reviews = \Academy\Helper::get_instructor_ratings( get_the_author_meta( 'ID', $instructor->ID ) );
		?>
	<div class="course-single-instructor">
		<div class="instructor-info">
			<div class="instructor-info__thumbnail">
			<?php
			if ( Academy\Helper::get_settings( 'is_show_public_profile' ) ) :
				?>
				<a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID', $instructor->ID ) ) ); ?>">
					<img src="<?php echo esc_url( get_avatar_url( $instructor->ID ) ); ?>" alt="<?php esc_attr_e( 'profile', 'academy' ); ?>">
				</a>
				<?php
				else :
					?>
					<img src="<?php echo esc_url( get_avatar_url( $instructor->ID ) ); ?>" alt="<?php esc_attr_e( 'profile', 'academy' ); ?>">
				<?php endif; ?>
			</div>
			<div class="instructor-info__content">
				<span class="instructor-title"><?php esc_html_e( 'Instructor', 'academy' ); ?></span>
				<h4 class="instructor-name">
				<?php
				if ( Academy\Helper::get_settings( 'is_show_public_profile' ) ) :
					?>
					<a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID', $instructor->ID ) ) ); ?>">
					<?php echo esc_html( $instructor->display_name ); ?>
					</a>
					<?php else : ?>
						<?php echo esc_html( $instructor->display_name ); ?>
					<?php endif; ?>
				</h4>
			</div>
		</div>
		<?php
		if ( $instructor_reviews_status ) :
			?>
		<div class="instructor-review">
			<span class="instructor-review__title"><?php esc_html_e( 'Reviews', 'academy' ); ?></span>
			<span class="instructor-review__rating">
			<?php
			echo wp_kses_post( \Academy\Helper::star_rating_generator( $reviews->rating_avg ) );
			?>
				<span class="instructor-review__rating-number"><?php echo esc_html( $reviews->rating_avg ) . ' <span>(' . esc_html( $reviews->rating_count ) . ' ' . esc_html__( 'Reviews', 'academy' ) . ')</span>'; ?></span> 
			</span>
		</div>
			<?php
			endif;
		?>
	</div>
	<?php endforeach; ?>
</div>
