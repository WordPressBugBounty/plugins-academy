<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="academy-quiz-attempt-answer-details">
	<h3 class="academy-quiz-attempt-entry-title-details">
		<?php echo esc_html__( 'Attempt Answer Details', 'academy' ); ?>
	</h3>
	<div class="academy-list-wrap academy-dashboard__content">
		<div class="academy-quiz-table-wrapper">
			<div class="academy-list-wrap academy-dashboard__content">
				<div class="academy-table academy-table--quiz-result-answer-details ">
					<div class="academy-table__container">
						<div class="academy-table__table academy-table--has-slider">
							<div class="academy-table__head">
								<div class="academy-table__head-row">
									<div class="academy-table__row-cell academy-table__header-row-cell">
									<?php echo esc_html__( 'No', 'academy' ); ?>
									</div>
									<div class="academy-table__row-cell academy-table__header-row-cell">
										<?php echo esc_html__( 'Type', 'academy' ); ?>
									</div>
									<div class="academy-table__row-cell academy-table__header-row-cell">
										<?php echo esc_html__( 'Question', 'academy' ); ?>
									</div>
									<div class="academy-table__row-cell academy-table__header-row-cell">
										<?php echo esc_html__( 'Correct Answer', 'academy' ); ?>
									</div>
									<div class="academy-table__row-cell academy-table__header-row-cell">
										<?php echo esc_html__( 'Given Answer', 'academy' ); ?>
									</div>
									<div class="academy-table__row-cell academy-table__header-row-cell">
										<?php echo esc_html__( 'Answer', 'academy' ); ?>
									</div>
								</div>
							</div>
							<div class="academy-table__body">
								<?php
								$count = 0;
								foreach ( $attempt_answer_details as $attempt_answer_detail ) :
									++$count;
									$question_type = $attempt_answer_detail->question_type;
									?>
									<div class="academy-table__body-row">
										<div class="academy-table__row-cell">
											<?php echo esc_html( $count ); ?>
										</div>
										<div class="academy-table__row-cell">
											<?php
											echo esc_html( \Academy\Helper::convert_camel_case_to_words( $question_type ) );
											?>
										</div>
										<div class="academy-table__row-cell">
											<?php echo wp_kses_post( $attempt_answer_detail->question_title ); ?>
										</div>
										<div class="academy-table__row-cell">
											<div class="academy-items-column">
												<?php
												foreach ( $attempt_answer_detail->correct_answer as $correct ) : ?>
													<div class="academy-quiz-table-answer-entry">
														<?php
														// Answer titles are rich-text HTML (bold/italic/sub/sup/etc, same
														// as question_title above) — esc_html() here previously escaped
														// that markup to literal "&lt;p&gt;..." text instead of
														// rendering it.
														echo wp_kses_post( is_array( $correct ) ? $correct['answer_title'] : ( $correct->answer_title ?? $correct ) );
														if ( isset( $correct->image_url ) ) : ?>
															<div class="academy-quiz-table-answers-item">

																<?php

																// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
																echo '<img src="' . esc_url( $correct->image_url ) . '" width="50" class="academy-quiz-table-answers-item" alt="' . esc_attr( $correct->answer_title ) . '">'; ?>


															</div>
														<?php elseif ( is_array( $correct ) && ! empty( $correct['image_url'] ) ) : ?>
															<div class="academy-quiz-table-answers-item">
																<?php

																// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
																echo '<img src="' . esc_url( $correct['image_url'] ) . '" width="50" class="academy-quiz-table-answers-item" alt="' . esc_attr( $correct['answer_title'] ) . '">'; ?>
															</div>
														<?php endif; ?>
													</div>
												<?php endforeach; ?>
											</div>
										</div>
										<div class="academy-table__row-cell">
											<div class="academy-items-column">
												<?php
												foreach ( $attempt_answer_detail->given_answer as $given ) : ?>
													<div class="academy-quiz-table-answer-entry">
														<?php if ( isset( $given->image_url ) ) : ?>
															<div class="academy-quiz-table-answers-item">
																<?php
																// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
																echo '<img src="' . esc_url( $given->image_url ) . '" width="50" class="academy-quiz-table-answers-item" alt="' . esc_attr( $given->answer_title ) . '">'; ?>
															</div>

														<?php elseif ( is_array( $given ) && ! empty( $given['image_url'] ) ) : ?>
															<div class="academy-quiz-table-answers-item">
																<?php
																// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
																echo '<img src="' . esc_url( $given['image_url'] ) . '" width="50" class="academy-quiz-table-answers-item" alt="' . esc_attr( $given['answer_title'] ) . '">'; ?>
															</div>

														<?php endif;

														// Same rich-text HTML rendering fix as the Correct Answer column above.
														echo wp_kses_post( is_array( $given ) ? $given['answer_title'] : $given->answer_title );
														?>
													</div>
												<?php endforeach; ?>
											</div>
										</div>
										<div class="academy-table__row-cell">
											<?php if ( $attempt_answer_detail->is_correct ) : ?>
												<span class="academy-passed">
													<?php echo esc_html__( 'Correct', 'academy' ); ?>
												</span>
											<?php elseif ( $attempt_answer_detail->is_skipped_question ) : ?>
												<span class="academy-skipped">
													<?php echo esc_html__( 'Skipped', 'academy' ); ?>
												</span>
											<?php else : ?>
												<span class="academy-failed">
													<?php echo esc_html__( 'Incorrect', 'academy' ); ?>
												</span>
											<?php endif; ?>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="academy-quiz-attempt-feedback">
		<h3 class="academy-quiz-attempt-entry-title">
			<?php esc_html_e( 'Instructor Feedback', 'academy' ); ?>
		</h3>
		<?php if ( '' !== trim( wp_strip_all_tags( (string) $instructor_feedback ) ) ) : ?>
			<div class="academy-quiz-attempt-feedback__message">
				<?php echo wp_kses_post( $instructor_feedback ); ?>
			</div>
		<?php else : ?>
			<p class="academy-quiz-attempt-feedback__empty">
				<?php esc_html_e( 'No feedback given yet.', 'academy' ); ?>
			</p>
		<?php endif; ?>
	</div>
</div>
