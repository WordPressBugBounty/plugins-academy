<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

	\Academy\Helper::get_template(
		'frontend-dashboard/pages/partials/sub-menu.php',
		[
			'menu' => apply_filters('academy/templates/frontend-dashboard/received-reviews-content-menu', [
				'reviews' => __( 'Given Reviews', 'academy' ),
				'received-reviews' => __( 'Received Reviews', 'academy' ),
			])
		]
	);
	?>

<div class="academy-dashboard-reviews academy-dashboard__content">
		<div class="academy-dashboard-mini-tabs">
			<div id="tab-panel-0-given-view" role="tabpanel" aria-labelledby="tab-panel-0-given" class="components-tab-panel__tab-content" tabindex="0">
				<div class="academy-tab-content">
					<?php if ( ! empty( $reviews ) ) : ?>
						<?php foreach ( $reviews as $review ) : ?>
							<?php
							$post_title     = get_the_title( $review->comment_post_ID );
							$post_permalink = esc_url( get_the_permalink( $review->comment_post_ID ) );
							?>
						<div class="academy-dashboard-reviews__received">
						<div class="academy-dashboard-review">
							<div class="academy-dashboard-review__header">Course:<a href="<?php echo esc_html( $post_permalink ); ?>"><?php echo esc_html( $post_title ); ?></a></div>
							<div class="academy-dashboard-review__content"><div>
								<?php echo wp_kses_post( \Academy\Helper::star_rating_generator( $review->rating ) ); ?>
								<span class="time"><?php echo esc_html( $review->comment_date ); ?> </span>
							</div>
							<p><?php echo esc_html( $review->comment_content ); ?></p>
						</div>
						</div>
						<?php endforeach; ?>
						<?php else : ?>
						<div class="academy-not-found"><?php echo esc_html__( 'You havent received any reviews yet.', 'academy' ); ?></div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>
