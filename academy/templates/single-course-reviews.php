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

		if ( have_comments() ) :  ?>
		<ol class="academy-review-list">
				<?php wp_list_comments( apply_filters( 'academy/templates/course_review_list_args', array( 'callback' => 'academy_review_lists' ) ) ); ?>
		</ol><!-- .comment-list -->
			<?php
			the_comments_pagination(
				array(
					'before_page_number' => esc_html__( 'Course', 'academy' ) . ' ',
					'mid_size'           => 0,
					'prev_text'          => sprintf(
						'<span class="nav-prev-text">%s</span>',
						esc_html__( 'Older comments', 'academy' )
					),
					'next_text'          => sprintf(
						'<span class="nav-next-text">%s</span>',
						esc_html__( 'Newer comments', 'academy' )
					),
				)
			);
			?>
			<?php if ( ! comments_open() ) : ?>
			<p class="academy-no-reviews"><?php esc_html_e( 'Review are closed.', 'academy' ); ?></p>
		<?php endif; ?>
		<?php endif; ?>
</div><!-- #comments -->
