<?php
/**
 * Student "Grades" dashboard page.
 *
 * This template can be overridden by copying it to
 * yourtheme/academy/frontend-dashboard/pages/grades.php
 *
 * Rendered as a collapsed accordion (one row per course) rather than one
 * always-expanded card per course, since a student can be enrolled in far
 * more courses than comfortably fit on screen at once.
 *
 * @var array $courses Per-course grade summaries, see academy_frontend_dashboard_grades_page().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$courses = isset( $courses ) ? $courses : [];
$grade_book_active = ! empty( $grade_book_active );
?>

<div class="academy-grades-dashboard">
	<?php if ( empty( $courses ) ) : ?>
		<h3 class="academy-not-found"><?php esc_html_e( 'You have not enrolled in any course yet.', 'academy' ); ?></h3>
	<?php else : ?>
		<div class="academy-grades-list">
			<?php foreach ( $courses as $course ) : ?>
				<?php
				// Falls back on empty, not on null: get_post_meta() returns ''
				// (or '0') when a course has no certificate of its own and never
				// null, so a `??` here would never reach the site-wide default.
				$certificate_id = get_post_meta( $course['course_id'], 'academy_course_certificate_id', true );
				if ( empty( $certificate_id ) ) {
					$certificate_id = \Academy\Helper::get_settings( 'academy_primary_certificate_id' );
				}
				$enable_certificate = get_post_meta( $course['course_id'], 'academy_course_enable_certificate', true );
				$show_certificate_link = $course['is_completed'] && $certificate_id && $enable_certificate;
				$quiz_summary = $course['quiz_summary'];
				?>
				<details class="academy-grades-course">
					<summary class="academy-grades-course__summary">
						<span class="academy-grades-course__toggle" aria-hidden="true">
							<span class="academy-icon academy-icon--angle-down"></span>
						</span>

						<span class="academy-grades-course__title">
							<?php echo esc_html( $course['course_title'] ); ?>
						</span>

						<span class="academy-grades-course__progress">
							<span class="academy-grades-progress__track">
								<span class="academy-grades-progress__fill" style="width: <?php echo esc_attr( $course['progress_percentage'] ); ?>%;"></span>
							</span>
							<span class="academy-grades-progress__label">
								<?php
								printf(
									/* translators: %d: course completion percentage */
									esc_html__( '%d%% complete', 'academy' ),
									(int) $course['progress_percentage']
								);
								?>
							</span>
						</span>

						<span class="academy-grades-course__quiz-stat">
							<?php if ( $quiz_summary['total'] > 0 ) : ?>
								<?php
								printf(
									/* translators: 1: number of quizzes passed, 2: number of quizzes attempted, 3: total number of quizzes */
									esc_html__( '%1$d passed · %2$d/%3$d attempted', 'academy' ),
									(int) $quiz_summary['passed'],
									(int) $quiz_summary['attempted'],
									(int) $quiz_summary['total']
								);
								?>
							<?php else : ?>
								<?php esc_html_e( 'No quizzes', 'academy' ); ?>
							<?php endif; ?>
						</span>

						<?php if ( $show_certificate_link ) : ?>
							<a
								class="academy-grades-course__certificate"
								target="_blank"
								href="<?php echo esc_url( add_query_arg( array( 'source' => 'certificate' ), $course['course_permalink'] ) ); ?>"
								onclick="event.stopPropagation();"
							>
								<span class="academy-icon academy-icon--certificate"></span>
								<?php esc_html_e( 'Certificate', 'academy' ); ?>
							</a>
						<?php endif; ?>
					</summary>

					<div class="academy-grades-course__body">
						<p class="academy-grades-course__view-course">
							<a href="<?php echo esc_url( $course['course_permalink'] ); ?>"><?php esc_html_e( 'View course', 'academy' ); ?></a>
						</p>

						<?php if ( ! empty( $course['quiz_results'] ) ) : ?>
							<div class="academy-grades-table-wrap">
								<table class="academy-grades-table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Quiz', 'academy' ); ?></th>
											<th><?php esc_html_e( 'Score', 'academy' ); ?></th>
											<th><?php esc_html_e( 'Percentage', 'academy' ); ?></th>
											<?php if ( $grade_book_active ) : ?>
												<th><?php esc_html_e( 'Grade', 'academy' ); ?></th>
											<?php endif; ?>
											<th><?php esc_html_e( 'Status', 'academy' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $course['quiz_results'] as $quiz ) : ?>
											<?php $attempt = $quiz['attempt']; ?>
											<tr>
												<td><?php echo esc_html( $quiz['quiz_title'] ); ?></td>
												<?php if ( $attempt ) : ?>
													<?php $percentage = \Academy\Helper::calculate_percentage( $attempt->total_marks, $attempt->earned_marks ); ?>
													<td><?php echo esc_html( $attempt->earned_marks . '/' . $attempt->total_marks ); ?></td>
													<td><?php echo esc_html( $percentage . '%' ); ?></td>
													<?php if ( $grade_book_active ) : ?>
														<td><?php echo esc_html( $quiz['grade_letter'] ?? '—' ); ?></td>
													<?php endif; ?>
													<td>
														<span class="academy-<?php echo esc_attr( $attempt->attempt_status ); ?>">
															<?php echo esc_html( ucfirst( $attempt->attempt_status ) ); ?>
														</span>
													</td>
												<?php else : ?>
													<td colspan="<?php echo esc_attr( $grade_book_active ? 4 : 3 ); ?>"><span class="academy-draft"><?php esc_html_e( 'Not attempted yet', 'academy' ); ?></span></td>
												<?php endif; ?>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php endif; ?>
					</div>
				</details>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
