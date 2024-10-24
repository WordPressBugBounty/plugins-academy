<?php

use AcademyProGradeBook\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$attempt_info = json_decode( $quiz_attempt->attempt_info );
$correct_answers = $attempt_info ? $attempt_info->total_correct_answers : 0;
$latest_attempt_grade = apply_filters( 'academy_quizzes/grade_book/latest_attempt_grade', false, $quiz_attempt->quiz_id, $quiz_attempt->course_id );

?>
<div class='academy-quiz-attempt-details'>
	<h3 class='academy-quiz-attempt-entry-title'>
		<?php echo esc_html__( 'Attempt Details', 'academy' ); ?>
	</h3>
	<div class='academy-list-wrap academy-dashboard__content'>
		<div class="academy-quiz-table-wrapper">
			<div class="academy-list-wrap academy-dashboard__content">
				<div class="kzui-table kzui-table--quiz-result-overview">
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
									<?php if ( ! empty( $latest_attempt_grade ) ) { ?>
										<div class="kzui-table__row-cell kzui-table__header-row-cell">
											<?php echo esc_html__( 'Final Grade', 'academy' ); ?>
										</div>
									<?php } ?>
								</div>
							</div>
							<div class="kzui-table__body">
								<div class="kzui-table__body-row">
									<div class="kzui-table__row-cell">
									<?php
										$attempt_date = new DateTime( $quiz_attempt->attempt_started_at );
									?>
										<div class="academy-items-column">
											<p>
												<?php echo esc_html( $attempt_date->format( 'F jS, Y' ) ); ?>
											</p>
											<p>
												<?php echo esc_html( $attempt_date->format( 'h:i:s A' ) ); ?>
											</p>
										</div>
									</div>
									<div class="kzui-table__row-cell">
										<div class="academy-items-column">
											<p>
												<b><?php echo esc_html__( 'Total : ', 'academy' ); ?></b> <?php echo esc_html( $quiz_attempt->total_questions ); ?>
											</p>
											<p>
												<b><?php echo esc_html__( 'Answered : ', 'academy' ); ?></b> <?php echo esc_html( $quiz_attempt->total_answered_questions ); ?>
											</p>
										</div>
									</div>
									<div class="kzui-table__row-cell">
										<div class="academy-items-column">
											<p>
												<b><?php echo esc_html__( 'Total : ', 'academy' ); ?></b> <?php echo esc_html( $quiz_attempt->total_marks ); ?>
											</p>
											<p>
												<b><?php echo esc_html__( 'Earned : ', 'academy' ); ?></b> <?php echo esc_html( $quiz_attempt->earned_marks ); ?>
											</p>
										</div>
									</div>
									<div class="kzui-table__row-cell">
										<div class="academy-items-column">
											<p>
												<b><?php echo esc_html__( 'Correct : ', 'academy' ); ?></b> <?php echo esc_html( $correct_answers ); ?>
											</p>
											<p>
												<b><?php echo esc_html__( 'Incorrect : ', 'academy' ); ?></b> <?php echo esc_html( $quiz_attempt->total_answered_questions - $correct_answers ); ?>
											</p>
										</div>
									</div>
									<div class="kzui-table__row-cell">
										<span class="academy-<?php echo esc_attr( $quiz_attempt->attempt_status ); ?>">
											<?php echo esc_html( $quiz_attempt->attempt_status ); ?>
										</span>
									</div>
									<?php if ( ! empty( $latest_attempt_grade ) ) { ?>
										<div class="kzui-table__row-cell">
											<div class="academy-table-title-wrap">
												<div class="academy-table-title">
													<div class="academy-grade-color" style="background: <?php echo esc_attr( $latest_attempt_grade->grade_config['grade_color'] ); ?>">

													</div>
													<span><?php echo esc_html( $latest_attempt_grade->grade_name ); ?> (<?php echo esc_html( $latest_attempt_grade->user_grade_point ); ?>)</span>
												</div>
											</div>
										</div>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
