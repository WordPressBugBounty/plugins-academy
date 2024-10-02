<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="academy-course__rating">
	<?php echo wp_kses_post( \Academy\Helper::single_star_rating_generator( $rating->rating_avg ) ); ?>
	<?php echo esc_html( $rating->rating_avg ); ?> <span class="academy-course__rating-count"><?php echo esc_html( '(' . $rating->rating_count . ')' ); ?></span>
</div>
