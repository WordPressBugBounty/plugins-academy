<div class="academy-courses-instructor-reviews">
	<?php
	if ( count( $reviews ) ) :
		foreach ( $reviews as $review ) :
			?>
	<div class="academy-courses-instructor-review">
		<div class="academy-courses-instructor-review__header"><?php esc_html_e( 'Course:', 'academy' ); ?><a
				href="<?php echo esc_url( $review->post_permalink ); ?>"><?php echo esc_html( $review->post_title ); ?></a></div>
		<div class="academy-courses-instructor-review__content">
			<div><span class="rating"><i class="academy-icon academy-icon--star" aria-hidden="true"></i><i
						class="academy-icon academy-icon--star" aria-hidden="true"></i><i
						class="academy-icon academy-icon--star" aria-hidden="true"></i><i
						class="academy-icon academy-icon--star" aria-hidden="true"></i><i
						class="academy-icon academy-icon--star" aria-hidden="true"></i></span><span class="time"><?php echo esc_html( $review->comment_date ); ?></span></div>
			<p><?php echo esc_html( $review->comment_content ); ?>
			</p>
		</div>
	</div>
			<?php
			endforeach;
		else :
			?>
	<div class="academy-not-found"><?php esc_html_e( 'Sorry, Review not found.', 'academy' ); ?>
	</div>
	<?php endif; ?>
</div>
