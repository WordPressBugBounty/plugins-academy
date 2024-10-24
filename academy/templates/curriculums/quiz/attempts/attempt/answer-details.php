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
				<div class="kzui-table kzui-table--quiz-result-answer-details ">
					<div class="kzui-table__container">
						<div class="kzui-table__table kzui-table--has-slider">
							<div class="kzui-table__head">
								<div class="kzui-table__head-row">
									<div class="kzui-table__row-cell kzui-table__header-row-cell">
									<?php echo esc_html__( 'No', 'academy' ); ?>
									</div>
									<div class="kzui-table__row-cell kzui-table__header-row-cell">
										<?php echo esc_html__( 'Type', 'academy' ); ?>
									</div>
									<div class="kzui-table__row-cell kzui-table__header-row-cell">
										<?php echo esc_html__( 'Question', 'academy' ); ?>
									</div>
									<div class="kzui-table__row-cell kzui-table__header-row-cell">
										<?php echo esc_html__( 'Correct Answer', 'academy' ); ?>
									</div>
									<div class="kzui-table__row-cell kzui-table__header-row-cell">
										<?php echo esc_html__( 'Given Answer', 'academy' ); ?>
									</div>
									<div class="kzui-table__row-cell kzui-table__header-row-cell">
										<?php echo esc_html__( 'Answer', 'academy' ); ?>
									</div>
								</div>
							</div>
							<div class="kzui-table__body">
								<?php
								$count = 0;
								foreach ( $attempt_answer_details as $attempt_answer_detail ) :
									$count ++;
									$question_type = $attempt_answer_detail->question_type;
									?>
									<div class="kzui-table__body-row">
										<div class="kzui-table__row-cell">
											<?php echo esc_html( $count ); ?>
										</div>
										<div class="kzui-table__row-cell">
											<?php
											echo esc_html( \Academy\Helper::convert_camel_case_to_words( $question_type ) );
											?>
										</div>
										<div class="kzui-table__row-cell">
											<?php echo esc_html( $attempt_answer_detail->question_title ); ?>
										</div>
										<div class="kzui-table__row-cell">
											<?php
											foreach ( $attempt_answer_detail->correct_answer as $correct ) :
												echo is_array( $correct ) ? esc_html( $correct['answer_title'] ) : esc_html( $correct->answer_title ?? $correct );
												if ( isset( $correct->image_url ) ) : ?>
													<div class="academy-quiz-table-answers-item">
														<img src="<?php echo esc_url( $correct->image_url ); ?>" width="50" class="academy-quiz-table-answers-item" alt="<?php echo esc_attr( $correct->answer_title ); ?>">
													</div>
												<?php elseif ( is_array( $correct ) && ! empty( $correct['image_url'] ) ) : ?>
													<div class="academy-quiz-table-answers-item">
														<img src="<?php echo esc_url( $correct['image_url'] ); ?>" width="50" class="academy-quiz-table-answers-item" alt="<?php echo esc_attr( $correct['answer_title'] ); ?>">
													</div>
												<?php endif;
											endforeach; ?>
										</div>
										<div class="kzui-table__row-cell">
											<?php
											foreach ( $attempt_answer_detail->given_answer as $given ) :
												if ( isset( $given->image_url ) ) : ?>
													<div class="academy-quiz-table-answers-item">
														<img src="<?php echo esc_url( $given->image_url ); ?>" width="50" class="academy-quiz-table-answers-item" alt="<?php echo esc_attr( $given->answer_title ); ?>">
													</div>

												<?php elseif ( is_array( $given ) && ! empty( $given['image_url'] ) ) : ?>
													<div class="academy-quiz-table-answers-item">
														<img src="<?php echo esc_url( $given['image_url'] ); ?>" width="50" class="academy-quiz-table-answers-item" alt="<?php echo esc_attr( $given['answer_title'] ); ?>">
													</div>

												<?php endif;

												echo is_array( $given ) ? esc_html( $given['answer_title'] ) : esc_html( $given->answer_title );
											endforeach;
											?>
										</div>
										<div class="kzui-table__row-cell">
											<?php if ( $attempt_answer_detail->is_correct ) : ?>
												<span class="academy-passed">
													<?php echo esc_html( 'Correct' ); ?>
												</span>
											<?php else : ?>
												<span class="academy-failed">
													<?php echo esc_html( 'Incorrect' ); ?>
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
</div>
