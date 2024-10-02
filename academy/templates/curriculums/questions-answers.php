<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	\Academy\Helper::get_template( 'curriculums/partial/alert.php', array( 'message' => 'Login Required To Access Q&A Feature.' ) );
	return;
}


?>
<div class="academy-lesson-tab__body">
	<div class="academy-lesson-browseqa-wrap">
		<div class="academy-question-lists">
		<?php
		if ( empty( $qas ) ) {
			\Academy\Helper::get_template( 'curriculums/question-answer/no-question-message.php' );
		}
		$child_comments = [];
		foreach ( $qas as $qa ) {
			if ( $qa->comment_parent ) {
				$child_comments[ $qa->comment_parent ][] = $qa;
				continue;
			}
			?>
			<div class="academy-qa">'
				<?php
				\Academy\Helper::get_template( 'curriculums/question-answer/questions.php', array( 'qa' => $qa ) );
				if ( array_key_exists( $qa->comment_ID, $child_comments ) ) {
					?>
					<div class="academy-qa__answer">
						<?php
						foreach ( array_reverse( $child_comments[ $qa->comment_ID ] ) as $child_comment ) {
							\Academy\Helper::get_template( 'curriculums/question-answer/answers.php', array( 'comment' => $child_comment ) );
						}
						?>
					</div>
					<?php
				}
				\Academy\Helper::get_template( 'curriculums/question-answer/answer-form.php', array( 'qa' => $qa ) );
				?>
			</div>
			<?php
		}//end foreach
		\Academy\Helper::get_template( 'curriculums/question-answer/question-asking-form.php' );
		?>
		</div>
	</div>
</div>
