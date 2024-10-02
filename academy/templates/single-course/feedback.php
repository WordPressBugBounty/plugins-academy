<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="academy-single-course__content-item academy-single-course__content-item--feedback">
	<h2 class="feedback-title"><?php esc_html_e( 'Student Feedback', 'academy' ); ?></h2>
	<div class="academy-student-course-feedback-ratings">
		<div class="academy-row academy-align-items-center">
			<div class="academy-col-md-4">
				<p class="academy-avg-rating">
					<?php
						echo number_format( $rating->rating_avg, 1 );
					?>
				</p>
				<p class="academy-avg-rating-html">
					<?php echo wp_kses_post( \Academy\Helper::star_rating_generator( $rating->rating_avg ) ); ?>
				</p>
				<p class="academy-avg-rating-total"><?php esc_html_e( 'Total', 'academy' ); ?> <span><?php echo esc_html( $rating->rating_count ); ?></span> <?php esc_html_e( 'Ratings', 'academy' ); ?></p>
			</div>
			<div class="academy-col-md-8">
				<div class="academy-ratings-list">
					<?php
					foreach ( $rating->count_by_value as $key => $value ) {
						$rating_count_percent = round( ( $value > 0 ) ? ( $value * 100 ) / $rating->rating_count : 0 ); ?>
						<div class="academy-ratings-list-item">
							<div class="academy-ratings-list-item-col"><?php echo esc_html( $key ); ?></div>
							<div class="academy-ratings-list-item-col"><i class="academy-icon academy-icon--star"></i></div>
							<div class="academy-ratings-list-item-fill">
								<div class="academy-ratings-list-item-fill-bar" style="width: <?php echo esc_html( $rating_count_percent ); ?>%;"></div>
							</div>
							<div class="academy-ratings-list-item-label">
								<?php echo esc_html( $value ) . '<span>(' . esc_html( $rating_count_percent ) . '%)</span>'; ?>
							</div>
						</div>
						<?php
					} ?>
				</div>
			</div>
		</div>
	</div>       
</div>
