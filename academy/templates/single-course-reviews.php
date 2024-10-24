<?php
/*
 * If the current post is protected by a password and
 * the visitor has not yet entered the password,
 * return early without loading the comments.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$is_enabled_course_review = (bool) \Academy\Helper::get_settings( 'is_enabled_course_review', true );

if ( post_password_required() || ! $is_enabled_course_review ) {
	return;
}
global $current_user, $post;
$academy_comments_count = get_comments_number();
?>

<div id="comments" class="academy-single-course__content-item academy-single-course__content-item--reviews">
	<?php
		// Get the comments for the logged in user.
		$usercomment = get_comments(array(
			'user_id' => $current_user->ID,
			'post_id' => $post->ID,
		));

		if ( ! $usercomment && Academy\Helper::is_enrolled( $post->ID, $current_user->ID ) ) {
			\Academy\Helper::get_template( 'single-course/review-form.php' );
		}

		if ( have_comments() ) {
			$paged = get_query_var( 'cpage' ) ? get_query_var( 'cpage' ) : 1; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$comments_per_page = 5;

			// Fetch approved comments for the current post.
			$args = array(
				'post_id' => get_the_ID(),
				'status'  => 'approve',
				'number'  => $comments_per_page,
				'paged'   => $paged,
			);

			$comment_query = new WP_Comment_Query();
			$comments = $comment_query->query( $args ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		if ( $comments ) :
			?>
			<ol class="academy-review-list">
				<?php
				foreach ( $comments as $comment ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					apply_filters( 'academy/templates/course_review_list_args', array( academy_review_lists( $comment, null, null ) ) );
				endforeach;
				?>
			</ol>

			<?php
			// Calculate total pages for pagination.
			$total_comments = get_comments( array(
				'post_id' => get_the_ID(),
				'status'  => 'approve',
				'count'   => true,
			));

			$max_pages = ceil( (int) $total_comments / $comments_per_page );

			// Display pagination if multiple pages exist.
			the_comments_pagination(
				array(
					'total'     => $max_pages,
					'current'   => $paged,
					'mid_size'  => 1,
					'prev_text' => sprintf(
						'<span class="nav-prev-text">%s</span>',
						esc_html__( 'Prev', 'academy' )
					),
					'next_text' => sprintf(
						'<span class="nav-next-text">%s</span>',
						esc_html__( 'Next', 'academy' )
					),
				)
			);
			?>
			<?php if ( ! comments_open() ) :
				?>
			<p class="academy-no-reviews"><?php esc_html_e( 'Reviews are closed.', 'academy' ); ?></p>
		<?php endif; ?>
	<?php endif; ?>
</div><!-- #comments -->

