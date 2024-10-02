<div class="academy-quiz-start-button">
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'academy_nonce', 'security' ); ?>
		<input type="hidden" value="academy_quizzes_start_quiz" name="action"/>
		<input type="hidden" value="<?php echo esc_attr( $quiz_id ); ?>" name="quiz_id"/>
		<input type="hidden" value="<?php echo esc_attr( \Academy\Helper::get_the_current_course_id() ); ?>" name="course_id"/>
		<button type="submit" name="submit"
				class="academy-btn academy-btn--md academy-btn--preset-purple">
			<span class="academy-btn--label"><?php echo esc_html__( 'Start Quiz', 'academy' ); ?></span>
		</button>
	</form>
</div>
