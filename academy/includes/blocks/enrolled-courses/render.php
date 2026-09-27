<?php
/**
 * Enrolled Courses block.
 *
 * One card per course with how much of it the student has done: topics
 * finished out of the total, what the course holds, when they enrolled, and
 * a button back into it.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$academy_count      = max( 1, min( 48, (int) $attributes['count'] ) );
$academy_columns    = max( 1, min( 6, (int) $attributes['columns'] ) );
$academy_pagination = ! empty( $attributes['pagination'] );

if ( ! is_user_logged_in() ) {
	ob_start();
	\Academy\Helper::get_template(
		'shortcode/academy-enrolled-courses/login-required.php',
		[ 'message' => __( 'Please log in to view your enrolled courses.', 'academy' ) ]
	);
	printf( '<div %1$s>%2$s</div>', get_block_wrapper_attributes( [ 'style' => \Academy\Blocks::academy_colors_style( $attributes ) ] ), ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core wrapper attributes and an escaped template.
	return;
}

$academy_user_id = get_current_user_id();
$academy_show    = in_array( $attributes['show'] ?? 'all', [ 'all', 'in-progress', 'completed' ], true ) ? $attributes['show'] : 'all';

// A student can hold more than one enrolment record for a course.
$academy_ids       = array_values( array_unique( array_map( 'intval', (array) \Academy\Helper::get_enrolled_courses_ids_by_user( $academy_user_id ) ) ) );
$academy_completed = array_map( 'intval', (array) \Academy\Helper::get_completed_courses_ids_by_user( $academy_user_id ) );

if ( 'completed' === $academy_show ) {
	$academy_ids = array_values( array_intersect( $academy_ids, $academy_completed ) );
} elseif ( 'in-progress' === $academy_show ) {
	$academy_ids = array_values( array_diff( $academy_ids, $academy_completed ) );
}

\Academy\Blocks::use_course_assets();
$academy_wrapper = get_block_wrapper_attributes(
	[
		'class' => 'is-style-progress',
		'style' => \Academy\Blocks::academy_colors_style( $attributes ),
	]
);

if ( empty( $academy_ids ) ) {
	$academy_messages = [
		'all'         => __( 'You have not enrolled in any courses yet.', 'academy' ),
		'in-progress' => __( 'No courses in progress. Every course you enrolled in is finished.', 'academy' ),
		'completed'   => __( 'No finished courses yet. Keep going!', 'academy' ),
	];
	ob_start();
	\Academy\Helper::get_template(
		'shortcode/academy-enrolled-courses/empty-state.php',
		[
			'message'    => $academy_messages[ $academy_show ],
			'browse_url' => get_post_type_archive_link( 'academy_courses' ),
		]
	);
	printf( '<div %s>%s</div>', $academy_wrapper, ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core wrapper attributes and an escaped template.
	return;
}

// WP paginates a singular page with `page`, an archive with `paged`.
$academy_paged = max( 1, (int) ( get_query_var( 'paged' ) ? get_query_var( 'paged' ) : get_query_var( 'page' ) ) );
$academy_query = new WP_Query(
	[
		'post_type'           => 'academy_courses',
		'post__in'            => $academy_ids,
		'orderby'             => 'post__in',
		'posts_per_page'      => $academy_count,
		'paged'               => $academy_paged,
		'ignore_sticky_posts' => true,
	]
);

$academy_date_format = get_option( 'date_format' );

// Small line icons for the facts under the progress bar.
$academy_icons = [
	'lesson'     => '<path d="M4 5.5A1.5 1.5 0 0 1 5.5 4H11v16H5.5A1.5 1.5 0 0 1 4 18.5zM13 4h5.5A1.5 1.5 0 0 1 20 5.5v13a1.5 1.5 0 0 1-1.5 1.5H13z"/>',
	'quiz'       => '<circle cx="12" cy="12" r="8.5"/><path d="M9.6 9.5a2.5 2.5 0 0 1 4.8.9c0 1.7-2.4 2-2.4 3.4M12 16.8v.1"/>',
	'assignment' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4v2h6V4M9 11h6M9 15h4"/>',
	'enrolled'   => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16M9 3v4M15 3v4"/>',
];
$academy_icon = static function ( $name ) use ( $academy_icons ) {
	return '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $academy_icons[ $name ] . '</svg>';
};
?>
<div <?php echo $academy_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<ul class="academy-enrolled-progress" style="--academy-enrolled-columns: <?php echo (int) $academy_columns; ?>">
		<?php
		foreach ( $academy_query->posts as $academy_course ) :
			$academy_course_id = (int) $academy_course->ID;
			$academy_total     = (int) \Academy\Helper::get_total_number_of_course_topics( $academy_course_id );
			$academy_done      = min( $academy_total, (int) \Academy\Helper::get_total_number_of_completed_course_topics_by_course_and_student_id( $academy_course_id, $academy_user_id ) );
			$academy_finished  = in_array( $academy_course_id, $academy_completed, true );
			$academy_percent   = $academy_finished ? 100 : ( $academy_total ? (int) floor( $academy_done / $academy_total * 100 ) : 0 );
			$academy_counts    = \Academy\Helper::get_course_curriculums_number_of_counts( $academy_course_id );
			$academy_enrolment = \Academy\Helper::is_enrolled( $academy_course_id, $academy_user_id );
			$academy_image     = \Academy\Helper::get_the_course_thumbnail_url_by_id( $academy_course_id, 'medium_large' );

			if ( $academy_finished ) {
				$academy_state  = 'completed';
				$academy_label  = __( 'Completed', 'academy' );
				$academy_button = __( 'Review course', 'academy' );
			} elseif ( $academy_done > 0 ) {
				$academy_state  = 'in-progress';
				$academy_label  = __( 'In progress', 'academy' );
				$academy_button = __( 'Continue learning', 'academy' );
			} else {
				$academy_state  = 'not-started';
				$academy_label  = __( 'Not started', 'academy' );
				$academy_button = __( 'Start course', 'academy' );
			}

			$academy_facts = [];
			if ( ! empty( $attributes['showTopics'] ) ) {
				if ( ! empty( $academy_counts['total_lessons'] ) ) {
					/* translators: %s: number of lessons. */
					$academy_facts[] = [ 'lesson', sprintf( _n( '%s lesson', '%s lessons', $academy_counts['total_lessons'], 'academy' ), number_format_i18n( $academy_counts['total_lessons'] ) ) ];
				}
				if ( ! empty( $academy_counts['total_quizzes'] ) ) {
					/* translators: %s: number of quizzes. */
					$academy_facts[] = [ 'quiz', sprintf( _n( '%s quiz', '%s quizzes', $academy_counts['total_quizzes'], 'academy' ), number_format_i18n( $academy_counts['total_quizzes'] ) ) ];
				}
				if ( ! empty( $academy_counts['total_assignments'] ) ) {
					/* translators: %s: number of assignments. */
					$academy_facts[] = [ 'assignment', sprintf( _n( '%s assignment', '%s assignments', $academy_counts['total_assignments'], 'academy' ), number_format_i18n( $academy_counts['total_assignments'] ) ) ];
				}
			}
			?>
			<li class="academy-enrolled-progress__card is-<?php echo esc_attr( $academy_state ); ?>">
				<?php if ( ! empty( $attributes['showImage'] ) ) : ?>
					<a class="academy-enrolled-progress__media" href="<?php echo esc_url( get_permalink( $academy_course_id ) ); ?>" tabindex="-1" aria-hidden="true">
						<img src="<?php echo esc_url( $academy_image ); ?>" alt="" loading="lazy" />
					</a>
				<?php endif; ?>
				<div class="academy-enrolled-progress__body">
					<span class="academy-enrolled-progress__status"><?php echo esc_html( $academy_label ); ?></span>
					<h3 class="academy-enrolled-progress__title">
						<a href="<?php echo esc_url( get_permalink( $academy_course_id ) ); ?>"><?php echo esc_html( get_the_title( $academy_course_id ) ); ?></a>
					</h3>

					<div class="academy-enrolled-progress__progress">
						<div class="academy-enrolled-progress__numbers">
							<span>
								<?php
								echo esc_html(
									sprintf(
										/* translators: 1: topics finished, 2: topics in the course. */
										_n( '%1$s of %2$s topic done', '%1$s of %2$s topics done', $academy_total, 'academy' ),
										number_format_i18n( $academy_finished ? $academy_total : $academy_done ),
										number_format_i18n( $academy_total )
									)
								);
								?>
							</span>
							<strong><?php echo esc_html( $academy_percent . '%' ); ?></strong>
						</div>
						<div class="academy-enrolled-progress__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo (int) $academy_percent; ?>" aria-label="<?php esc_attr_e( 'Course progress', 'academy' ); ?>">
							<span style="width: <?php echo (int) $academy_percent; ?>%"></span>
						</div>
					</div>

					<?php if ( $academy_facts || ( ! empty( $attributes['showEnrolled'] ) && ! empty( $academy_enrolment->post_date ) ) ) : ?>
						<ul class="academy-enrolled-progress__facts">
							<?php foreach ( $academy_facts as $academy_fact ) : ?>
								<li><?php echo $academy_icon( $academy_fact[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed markup. ?><?php echo esc_html( $academy_fact[1] ); ?></li>
							<?php endforeach; ?>
							<?php if ( ! empty( $attributes['showEnrolled'] ) && ! empty( $academy_enrolment->post_date ) ) : ?>
								<li>
									<?php
									echo $academy_icon( 'enrolled' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed markup.
									/* translators: %s: date the student enrolled. */
									echo esc_html( sprintf( __( 'Enrolled %s', 'academy' ), mysql2date( $academy_date_format, $academy_enrolment->post_date ) ) );
									?>
								</li>
							<?php endif; ?>
						</ul>
					<?php endif; ?>

					<?php if ( ! empty( $attributes['showButton'] ) ) : ?>
						<a class="academy-enrolled-progress__button" href="<?php echo esc_url( \Academy\Helper::get_start_course_permalink( $academy_course_id ) ); ?>"><?php echo esc_html( $academy_button ); ?></a>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
	if ( $academy_pagination && $academy_query->max_num_pages > 1 ) {
		$academy_links = paginate_links(
			[
				'current' => $academy_paged,
				'total'   => $academy_query->max_num_pages,
				'type'    => 'list',
			]
		);
		if ( $academy_links ) {
			echo '<nav class="academy-enrolled-progress__pagination">' . wp_kses_post( $academy_links ) . '</nav>';
		}
	}
	?>
</div>
<?php
wp_reset_postdata();
