<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$attempts = \AcademyQuizzes\Classes\Query::get_quiz_attempt_details_by_quiz_id( array(
	'course_id' => $course_id,
	'quiz_id' => $quiz_id,
));
$successful_attempts     = \AcademyQuizzes\Helper::get_successful_attempts_from_attempts( $attempts, $quiz_id );
$quiz                    = \AcademyQuizzes\Helper::render_quiz_by_course_and_quiz_id( $course_id, $quiz_id );
$max_attempt             = ( 'retry' === $quiz['settings']['quiz_feedback_mode'] ) ? $quiz['settings']['quiz_max_attempts_allowed'] : 1;
$quiz_time               = $quiz['settings']['quiz_time'];
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$attempt_id = isset( $_GET['attempt_id'] ) ? (int) sanitize_text_field( $_GET['attempt_id'] ) : 0;

if ( $attempt_id ) {
	\Academy\Helper::get_template( 'curriculums/quiz/attempts/attempt.php',
		array(
			'course_id' => $course_id,
			'quiz_id' => $quiz_id,
		)
	);
	return;
}

?>

<div class="academy-lesson-quiz-attempts">
	<div class="academy-lesson-quiz__result-details">
		<div class="academy-quiz-result">
			<h3 class='academy-quiz-heading'><?php echo esc_html__( 'Quiz', 'academy' ); ?></h3>
			<div class='academy-quiz-result__details'>
				<p>
					<span>
						<?php echo esc_html__( 'Total Question : ', 'academy' ); ?>
					</span>
					<span>
						<?php echo esc_html( count( $quiz['questions'] ) ); ?>
					</span>
				</p>
				<p>
					<span>
						<?php echo esc_html__( 'Quiz Time : ', 'academy' ); ?>
					</span>
					<span>
						<?php if ( 0 === $quiz_time ) : ?>
							<span class="academy-quiz-time-no-limit">
								<?php echo esc_html__( 'No Time Limit ', 'academy' ); ?>
							</span>
						<?php endif; ?>
					</span>
				</p>
				<p>
					<span>
						<?php echo esc_html__( 'Total Attempted : ', 'academy' ); ?>
					</span>
					<span>
						<?php echo esc_html( count( $successful_attempts ) ); ?>
					</span>
				</p>
				<p>
					<span>
						<?php echo esc_html__( 'Passing Grade : ', 'academy' ); ?>
					</span>
					<span>
						<?php echo esc_html( $quiz['settings']['quiz_passing_grade'] . '%' ); ?>
					</span>
				</p>
			</div>


			<div class="academy-quiz-table-wrapper">
				<div class="academy-list-wrap academy-dashboard__content">
					<div class="kzui-table kzui-table--quiz-result ">
						<div class="kzui-table__container">
							<div class="kzui-table__table kzui-table--has-slider">
								<div class="kzui-table__head">
									<div class="kzui-table__head-row">
										<div class="kzui-table__row-cell kzui-table__header-row-cell">
											<?php echo esc_html__( 'Date', 'academy' ); ?>
										</div>
										<div class="kzui-table__row-cell kzui-table__header-row-cell">
											<?php echo esc_html__( 'Questions', 'academy' ); ?>
										</div>
										<div class="kzui-table__row-cell kzui-table__header-row-cell">
											<?php echo esc_html__( 'Marks', 'academy' ); ?>
										</div>
										<div class="kzui-table__row-cell kzui-table__header-row-cell">
											<?php echo esc_html__( 'Answers', 'academy' ); ?>
										</div>
										<div class="kzui-table__row-cell kzui-table__header-row-cell">
											<?php echo esc_html__( 'Result', 'academy' ); ?>
										</div>
										<div class="kzui-table__row-cell kzui-table__header-row-cell">
											<?php echo esc_html__( 'Details', 'academy' ); ?>
										</div>
									</div>
								</div>
								<div class="kzui-table__body">
									<?php foreach ( $successful_attempts as $successful_attempt ) : ?>
										<div class="kzui-table__body-row">
										<div class="kzui-table__row-cell kzui-table__row-cell__quiz-date">
											<?php
												$attempt_date = new DateTime( $successful_attempt->attempt_started_at );
											?>
											<p>
												<?php echo esc_html( $attempt_date->format( 'F jS, Y' ) ); ?>
											</p>
											<p>
												<?php echo esc_html( $attempt_date->format( 'h:i:s A' ) ); ?>
											</p>
										</div>
											<div class="kzui-table__row-cell">
												<div>
													<p>
														<span><?php echo esc_html__( 'Total : ', 'academy' ); ?></span>
														<span><?php echo esc_html( $successful_attempt->total_questions ); ?></span>
													</p>
													<p>
														<span><?php echo esc_html__( 'Answered : ', 'academy' ); ?></span>
														<span><?php echo esc_html( $successful_attempt->total_answered_questions ); ?></span>
													</p>
												</div>
											</div>
											<div class="kzui-table__row-cell">
												<div>
													<p>
														<span> <?php echo esc_html__( 'Total : ', 'academy' ); ?> </span>
														<span><?php echo esc_html( $successful_attempt->total_marks ); ?></span>
													</p>
													<p>
														<span><?php echo esc_html__( 'Earned : ', 'academy' ); ?></span>
														<span><?php echo esc_html( $successful_attempt->earned_marks ); ?></span>
													</p>
												</div>
											</div>
											<div class="kzui-table__row-cell">
												<div>
													<p>
														<span><?php echo esc_html__( 'Correct : ', 'academy' ); ?> </span>
														<span><?php echo esc_html( $successful_attempt->total_correct_answer ); ?></span>
													</p>
													<p>
														<span><?php echo esc_html__( 'Incorrect : ', 'academy' ); ?></span>
														<span><?php echo esc_html( $successful_attempt->total_answered_questions - $successful_attempt->total_correct_answer ); ?></span>
													</p>
												</div>
											</div>
											<div class="kzui-table__row-cell">
												<span class="academy-<?php echo esc_attr( $successful_attempt->attempt_status ); ?>">
													<?php echo esc_html( $successful_attempt->attempt_status ); ?>
												</span>
											</div>
											<div class="kzui-table__row-cell">
												<a href="<?php echo esc_url( add_query_arg( array( 'attempt_id' => $successful_attempt->attempt_id ), $_SERVER['REQUEST_URI'] ) ); ?>">
													<button type="button" class="academy-btn academy-btn--md academy-btn--preset-light-purple academy-btn--border-purple academy-btn--border-rounded">
														<?php echo esc_html__( 'Details', 'academy' ); ?>
													</button>
												</a>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<?php

			if ( count( $attempts ) < $max_attempt ) {
				\Academy\Helper::get_template( 'curriculums/quiz/start.php', array( 'quiz_id' => $quiz_id ) );
			}
			?>

		</div>
	</div>
</div>
