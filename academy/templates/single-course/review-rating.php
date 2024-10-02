<?php
/**
 * The template to display the reviewers star rating in reviews
 *
 * This template can be overridden by copying it to yourtheme/academy/review-rating.php.
 *
 * @package Academy\Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $comment;
$rating = intval( get_comment_meta( $comment->comment_ID, 'academy_rating', true ) );
?>
<div class="academy-review__rating">
	<?php echo esc_html( $rating ); ?> <span class="academy-review__rating-count"></span>
	<?php echo wp_kses_post( \Academy\Helper::single_star_rating_generator( $rating ) ); ?>
</div>
