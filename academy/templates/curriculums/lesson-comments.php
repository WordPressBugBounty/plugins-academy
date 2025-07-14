<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	\Academy\Helper::get_template( 'curriculums/partial/alert.php', array( 'message' => 'Login Required To Access Comments Feature.' ) );
	return;
}
$topic_name = get_query_var( 'name' );
$lesson_post = \Academy\Helper::get_lesson_by_slug( $topic_name );
if ( ! $lesson_post ) {
	return;
}
?>
<div class="academy-lesson-tab__body">
	<div class="academy-lesson-browse-comment-wrap">
		<div class="academy-comment-lists">
		<?php
		$child_comments = [];
		if ( ! empty( $comments ) ) {
			foreach ( $comments as $comment ) {
				if ( $comment->comment_parent ) {
					$child_comments[ $comment->comment_parent ][] = $comment;
					continue;
				}
				?>
				<div class="academy-comment">
					<?php
					\Academy\Helper::get_template( 'curriculums/lesson-comments/comment.php', array( 'comment' => $comment ) );
					if ( array_key_exists( $comment->comment_ID, $child_comments ) ) {
						?>
						<div class="academy-comment__answer">
							<?php
							foreach ( array_reverse( $child_comments[ $comment->comment_ID ] ) as $child_comment ) {
								\Academy\Helper::get_template( 'curriculums/lesson-comments/comment-reply.php', array( 'comment' => $child_comment ) );
							}
							?>
						</div>
						<?php
					}
					\Academy\Helper::get_template( 'curriculums/lesson-comments/comment-form.php', array(
						'comment' => $comment,
						'course_id' => $course_id,
						'lesson_id' => $lesson_post['ID']
					) );
				?>
				</div>
				<?php
			}//end foreach
		}//end if
		\Academy\Helper::get_template( 'curriculums/lesson-comments/comment-form.php', [
			'course_id' => $course_id,
			'lesson_id' => $lesson_post['ID']
		] );
		?>
		</div>
	</div>
</div>
