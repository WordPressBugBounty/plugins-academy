<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

	\Academy\Helper::get_template(
		'frontend-dashboard/pages/partials/sub-menu.php',
		[
			'menu' => apply_filters('academy/templates/frontend-dashboard/completed-courses-content-menu', [
				'enrolled-courses' => __( 'Enrolled Courses', 'academy' ),
				'active-courses' => __( 'Active Courses', 'academy' ),
				'complete-courses' => __( 'Complete Courses', 'academy' ),
			])
		]
	);
	?>


<?php
if ( ! empty( $pending_enrolled_courses ) ) :
	?>
	<div class="academy-dashboard-notice">
		<?php
			$course_links = array_map(function( $course_id ) {
				return sprintf( '<a href="%s" target="_blank" rel="noreferrer">%s</a>', get_permalink( $course_id ), get_the_title( $course_id ) );
			}, $pending_enrolled_courses);
			$course_links_str = implode( ', ', $course_links );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			printf( 'You have %d (%s) enrolled courses in pending status. Please wait for admin approval.', count( $pending_enrolled_courses ), $course_links_str );
		?>
	</div>
	<?php
	endif;
?>


<div class="academy-dashboard-enrolled-courses academy-dashboard__content">		        
	<div class="academy-row">
		<?php if ( ! empty( $completed_courses ) ) : ?>
			<?php foreach ( $completed_courses as $course_id ) : ?>
				<?php
				$course_title = get_the_title( $course_id );
				$thumbnail_url = Academy\Helper::get_the_course_thumbnail_url_by_id( $course_id );
				$course_permalink = get_permalink( $course_id );
				$rating                  = \Academy\Helper::get_course_rating( $course_id );
				$total_topics           = \Academy\Helper::get_total_number_of_course_topics( $course_id );
				$total_completed_topics = \Academy\Helper::get_total_number_of_completed_course_topics_by_course_and_student_id( $course_id );
				$percentage              = \Academy\Helper::calculate_percentage( $total_topics, $total_completed_topics );
				?>
		<div class="academy-col-lg-4 academy-col-md-6 academy-col-sm-12">
			<div class="academy-mycourse academy-mycourse-12">
				<div class="academy-mycourse__thumbnail">
					<a href="<?php echo esc_url( $course_permalink ); ?>"><img class="academy-course__thumbnail-image" src="<?php echo esc_url( $thumbnail_url ); ?>" alt="thumbnail"></a>
				</div>
				<div class="academy-mycourse__content">
					<div class="academy-course__rating">
								<?php
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									echo \Academy\Helper::star_rating_generator( $rating->rating_avg );
								?>
								<?php echo esc_html( $rating->rating_avg ); ?> <span
								class="academy-course__rating-count"><?php echo esc_html( '(' . $rating->rating_count . ')' ); ?></span>
						</div>
						<h3><a href="<?php echo esc_url( $course_permalink ); ?>"><?php echo esc_html( $course_title ); ?></a></h3>
						<div class="academy-course__meta">
							<div class="academy-course__meta-item"><?php echo esc_html__( 'Total Topics', 'academy' ); ?>:<span><?php echo esc_html( $total_topics ); ?></span></div>
							<div class="academy-course__meta-item"> <?php echo esc_html__( 'Completed Topics:', 'academy' ); ?><span><?php echo esc_html( $total_topics . '/' . $total_completed_topics ); ?></span>
							</div>
						</div>
						<div class="academy-progress-wrap">
							<div class="academy-progress">
								<div class="academy-progress-bar" style="width: <?php echo esc_attr( $percentage ) . '%'; ?>;">
								</div>
							</div>
							<span class="academy-progress-wrap__percent"><?php echo esc_attr( $percentage ) . '%'; ?><?php echo esc_html__( 'Complete', 'academy' ); ?></span>
						</div>
						<div class="academy-widget-enroll__continue">
						<a class="academy-btn academy-btn--bg-purple" href="<?php echo esc_url( $course_permalink ); ?>"><?php echo esc_html__( 'Start Course', 'academy' ); ?></a></div>
						<div class="academy-widget-enroll__view_details" data-id="<?php echo esc_attr( $course_id ); ?>">
							<button class="academy-btn academy-btn--bg-purple">
								<?php
								esc_html_e( 'View Details', 'academy' );
								?>
							</button>
					</div>
				</div>
			</div>
		</div>
		<?php endforeach; ?>
		<?php else : ?>
			<h3 class="academy-not-found"><?php echo esc_html__( 'You have not completed any course yet.', 'academy' ); ?></h3>
		<?php endif; ?>
	</div>
</div>
