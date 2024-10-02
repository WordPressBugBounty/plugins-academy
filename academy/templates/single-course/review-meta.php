<?php
/**
 * The template to display the reviewers meta data (name, verified owner, review date)
 *
 * This template can be overridden by copying it to yourtheme/academy/single-course/review-meta.php.
 *
 * @package Academy\Templates
 * @version 1.0.0
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}



global $comment;

if ( '0' === $comment->comment_approved ) { ?>
	<p class="academy-review-meta">
		<em class="cademy-review-meta__awaiting-approval">
			<?php esc_html_e( 'Your review is awaiting approval', 'academy' ); ?>
		</em>
	</p>
<?php } else { ?>
	<p class="academy-review-meta">
		<strong class="academy-review-meta__author"><?php comment_author(); ?> </strong>
		<time class="academy-review-meta__published-date" datetime="<?php echo esc_attr( get_comment_date( 'c' ) ); ?>"><?php echo esc_html( get_comment_date( Academy\Helper::get_date_format() ) ); ?></time>
	</p>
	<?php
}
