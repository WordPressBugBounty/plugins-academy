<?php
namespace Academy\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Comments {
	public static function init() {
		$self = new self();
		add_action( 'comment_post', array( $self, 'add_comment_rating' ), 1 );
	}

	/**
	 * Rating field for comments.
	 *
	 * @param int $comment_id Comment ID.
	 */
	public function add_comment_rating( $comment_id ) {
		// Runs on core's `comment_post`, after wp-comments-post.php has handled the
		// (nonce-less, by core design) comment submission.
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['comment_post_ID'] ) && 'academy_courses' === get_post_type( absint( $_POST['comment_post_ID'] ) ) ) {
			$comment_post_ID = absint( $_POST['comment_post_ID'] );
			$academy_rating  = isset( $_POST['academy_rating'] ) ? absint( $_POST['academy_rating'] ) : 0;
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			wp_update_comment(
				[
					'comment_ID'   => $comment_id,
					'comment_type' => 'academy_courses',
				]
			);

			if ( ! $academy_rating || $academy_rating > 5 ) {
				return;
			}

			if ( $academy_rating ) {
				add_comment_meta( $comment_id, 'academy_rating', $academy_rating, true );
			}
			do_action( 'academy/frontend/after_course_rating', $comment_id, $comment_post_ID, $academy_rating );
		}//end if
	}
}
