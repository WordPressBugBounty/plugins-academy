<?php
/**
 * Review Comments Template
 *
 * Closing li is left out on purpose!.
 *
 * This template can be overridden by copying it to yourtheme/academy/single-product/review.php.
 *
 * @package Academy\Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<li <?php comment_class(); ?> id="academy-review-<?php comment_ID(); ?>">

	<div id="comment-<?php comment_ID(); ?>" class="academy-review_container">

		<?php
		/**
		 * The academy/templates/review_before hook
		 */
		do_action( 'academy/templates/review_before', $comment );
		?>

		<div class="academy-review-thumnail">
			<?php
				do_action( 'academy/templates/review_thumbnail', $comment )
			?>
		</div>
		<div class="academy-review-content">

			<?php
			/**
			 * The academy/templates/review_before_comment_meta hook.
			 *
			 * @hooked academy_review_display_rating - 10
			 */
			do_action( 'academy/templates/review_before_comment_meta', $comment );

			/**
			 * The academy/templates/review_meta hook.
			 *
			 * @hooked academy_review_display_meta - 10
			 */
			do_action( 'academy/templates/review_meta', $comment );

			do_action( 'academy/templates/review_before_comment_text', $comment );

			/**
			 * The academy/templates/review_comment_text hook
			 *
			 * @hooked academy_review_display_comment_text - 10
			 */
			do_action( 'academy/templates/review_comment_text', $comment );

			do_action( 'academy/templates/review_after_comment_text', $comment );
			?>

		</div>
	</div>
