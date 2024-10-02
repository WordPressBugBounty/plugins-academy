<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$quiz = \AcademyQuizzes\Helper::render_quiz_by_course_and_quiz_id( $course_id, $quiz_id );
$questions_with_options = \AcademyQuizzes\Helper::get_questions_with_options_from_quiz_array( $quiz );

?>

<form id="academy_quiz_player" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method='post'>
	<?php wp_nonce_field( 'academy_nonce', 'security' ); ?>
	<input type="hidden" name="action" value="academy_quizzes_submit_quiz" />
	<input type="hidden" name="course_id" value="<?php echo esc_attr( $course_id ); ?>" />
	<input type="hidden" name="quiz_id" value="<?php echo esc_attr( $quiz_id ); ?>" />
	<div class="academy-lesson-quiz">
		<div class="academy-lesson-quiz__inner">

			<?php
				$question_count = 0;
			foreach ( $questions_with_options as $question_with_option ) :
				$question_count++;
				$question_type   = $question_with_option['question']->question_type;
				$answer_settings = json_decode( $question_with_option['question']->question_settings );
				$is_required = $answer_settings->answer_required;
				?>

				<div 
					class="academy-quiz-single-question__wrapper  academy-quiz-question academy-quiz-question-no-<?php echo esc_html( $question_count ); ?>"
					id="academy-quiz-question-no-<?php echo esc_html( $question_count ); ?>"
					style="display: <?php echo esc_attr( ( 0 === $question_count ? 'block' : 'none' ) ); ?>;"
				>
					<span class="academy-quiz-question__type academy-quiz-<?php echo esc_attr( $question_with_option['question']->question_type ); ?> academy-quiz-ans-required_<?php echo ( $is_required ) ? 'true' : 'false'; ?>" id="academy-quiz-ans-required_<?php echo ( $is_required ) ? 'true' : 'false'; ?>"></span>

				<?php
					$template_paths = array( 'curriculums/quiz/questions/question-top.php', 'curriculums/quiz/question-body.php' );
					$template_args  = array(
						'quiz_id'           => $quiz_id,
						'course_id'         => $course_id,
						'question_count'    => $question_count,
						'question_with_option' => $question_with_option,
						'last_attempt' => $last_attempt,
					);
					// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					foreach ( $template_paths as $path ) {
						\Academy\Helper::get_template( $path, $template_args );
					}
					?>

				</div>

				<?php
				endforeach;
				\Academy\Helper::get_template( 'curriculums/quiz/form-control.php' );
			?>

		</div>
	</div>
</form>
