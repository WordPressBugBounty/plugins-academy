<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div class="academy-course__body">
	<?php
	/**
	 * Hook - academy/templates/before_course_loop_content_inner
	 */
	do_action( 'academy/templates/before_course_loop_content_inner' );

	$course_id  = get_the_ID();
	$categories = \Academy\Helper::get_the_course_category( $course_id );

	// Display course category if available
	if ( ! empty( $categories ) ) {
		echo '<p class="academy-course__meta academy-course__meta--category"><a href="' . esc_url( get_term_link( $categories[0]->term_id ) ) . '">' . esc_html( $categories[0]->name ) . '</a></p>';
	}

	$course_type = get_post_meta( $course_id, 'academy_course_type', true );
	$is_paid     = \Academy\Helper::is_course_purchasable( $course_id );
	$reviews_status = Academy\Helper::get_settings( 'is_enabled_course_review', true );
	$price       = '';

	// Check if WooCommerce is active and the course is purchasable
	if ( \Academy\Helper::is_active_woocommerce() && $is_paid ) {
		$product_id = \Academy\Helper::get_course_product_id( $course_id );
		if ( $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$price = $product->get_price_html();
			}
		}
	}

	// Get course rating
	$rating = \Academy\Helper::get_course_rating( $course_id );
	?>
	<?php if ( 'alms_course_bundle' !== get_post_type( $course_id ) ) : ?>
		<div class="academy-d-flex academy-align-items-center academy-justify-content-between">
			<div class="academy-course__rating">
				<?php
				if ( $reviews_status ) :
					// Display course rating
					echo wp_kses_post( \Academy\Helper::single_star_rating_generator( $rating->rating_avg ) );
					echo esc_html( $rating->rating_avg );
					?>
					<span class="academy-course__rating-count">
						<?php echo esc_html( '(' . $rating->rating_count . ')' ); ?>
					</span>
				<?php endif; ?>
			</div>	
			<div>
				<?php
				// Load template for price display
				\Academy\Helper::get_template(
					'loop/price.php',
					apply_filters( 'academy/template/loop/price_args', array(
						'price'       => $price,
						'is_paid'     => $is_paid,
						'course_type' => $course_type,
					), $course_id )
				);
				?>
			</div>
		</div>
	<?php endif; ?>
	<h4 class="academy-course__title academy-mt-4">
		<a href="<?php echo esc_url( get_the_permalink() ); ?>">
			<?php the_title(); ?>
		</a>
	</h4>
</div>
