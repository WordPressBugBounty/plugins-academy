<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="academy-courses__sidebar">
	<div class="academy-author-info">
		<img class="academy-author-info__thumbnail"
			src="<?php echo esc_url( Academy\Helper::get_the_author_thumbnail_url( $author_ID ) ); ?>"
			alt="<?php esc_attr_e( 'thumbnail', 'academy' ); ?>" />
		<h3 class="academy-author-info__name"><?php echo esc_html( Academy\Helper::get_the_author_name( $author_ID ) ); ?>
		</h3>
		<p class="academy-author-info__designation"><?php echo esc_html( get_the_author_meta( 'academy_profile_designation', $author_ID ) ); ?>
		</p>
		<?php
		if ( $is_enabled_instructor_review ) :
			?>
		<div class="academy-author-info__rating">
			<?php
			echo wp_kses_post( \Academy\Helper::star_rating_generator( $reviews->rating_avg ) );
			?>
			<span class="academy-author-info__rating-number"><?php echo esc_html( $reviews->rating_avg ) . ' <span>(' . esc_html( $reviews->rating_count ) . ' ' . esc_html__( 'Reviews', 'academy' ) . ')</span>'; ?></span>
		</div>
			<?php
			endif;
		?>
		<h4 class="academy-author-info__bio-heading"><?php esc_html_e( 'BIO', 'academy' ); ?>
		</h4>
		<p class="academy-author-info__bio-details"><?php echo wp_kses_post( Academy\Helper::get_the_author_info( $author_ID ) ); ?>
		</p>
		<div class="academy-author-info__course-details">
			<div>
				<span class="academy-author-info__course-details-number"><?php echo esc_html( count_user_posts( $author_ID, 'academy_courses' ) ); ?></span>
				<span class="academy-author-info__course-details-text"><?php esc_html_e( 'Courses', 'academy' ); ?></span>
			</div>
			<div>
				<span class="academy-author-info__course-details-number"><?php echo esc_html( Academy\Helper::get_total_number_of_students_by_instructor( $author_ID ) ); ?></span>
				<span class="academy-author-info__course-details-text"><?php esc_html_e( 'Students', 'academy' ); ?></span>
			</div>
		</div>
		<?php
		if ( $website_url || $facebook_url || $github_url || $twitter_url || $linkdin_url ) :
			?>
		<div class="academy-author-info__social-links">
			<h4 class="academy-author-info__social-links-heading"><?php esc_html_e( 'Social Links', 'academy' ); ?>
			</h4>
			<ul class="academy-author-info__social-links-lists">
			<?php
			if ( ! empty( $website_url ) ) :
				?>
				<li><a href="<?php echo esc_url( $website_url ); ?>"><span
							class="academy-icon academy-icon--website"></span></a></li>
				<?php
				endif;

			if ( ! empty( $facebook_url ) ) :
				?>
				<li><a href="<?php echo esc_url( $facebook_url ); ?>"><span
							class="academy-icon academy-icon--facebook"></span></a></li>
					<?php
					endif;

			if ( ! empty( $twitter_url ) ) :
				?>
				<li><a href="<?php echo esc_url( $twitter_url ); ?>"><span
							class="academy-icon academy-icon--twitter"></span></a></li>
					<?php
					endif;

			if ( ! empty( $linkdin_url ) ) :
				?>
				<li><a href="<?php echo esc_url( $linkdin_url ); ?>"><span
							class="academy-icon academy-icon--linkedIn"></span></a></li>
					<?php
					endif;

			if ( ! empty( $github_url ) ) :
				?>
				<li><a href="<?php echo esc_url( $github_url ); ?>"><span
							class="academy-icon academy-icon--github"></span></a></li>
					<?php
					endif;
			?>
			</ul>
		</div>
		<?php endif; ?>
	</div>
</div>
