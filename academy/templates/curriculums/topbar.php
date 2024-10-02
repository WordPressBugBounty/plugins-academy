<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! $course_id ) {
	return;
}
	// get course percentage
	$percentage = \Academy\Helper::get_percentage_of_completed_topics_by_student_and_course_id( get_current_user_id(), $course_id );
	// circumference of the circle
	$dashArray = 157.08;
	// calculate how much should cover in the circumference
	$dashOffset = $dashArray - ( $percentage / 100 * $dashArray );
	// Lesson topbar logo
	$topbar_logo = wp_get_attachment_image_src( \Academy\Helper::get_settings( 'lessons_topbar_logo', '' ), array( '80', '80' ) );
?>

<div class="academy-lesson-topbar">
	<div class="academy-lesson-topbar__left">
		<div class="academy-logo">
			<a href="<?php echo esc_url( site_url() ); ?>">
				<img src="<?php echo esc_url( is_array( $topbar_logo ) ? $topbar_logo[0] : ACADEMY_ASSETS_URI . 'images/logo.svg' ); ?>" alt="academy_logo" />
			</a>
		</div>
		<hr class="topbar-hr">
		<h3 class="academy-course-title">
			<a href="<?php echo esc_url( get_the_permalink( $course_id ) ); ?>">
				<?php echo esc_html( get_the_title( $course_id ) ); ?>
			</a>
		</h3>
	</div>
	<div class="academy-lesson-topbar__right">
		<div class="academy-course-progress" role="presentation">
			<div class="academy-progressbar">
				<svg width="40" height="40" viewBox="0 0 40 40">
					<circle cx="20" cy="20" stroke-width="15px" r="25" class="academy-progressbar__circle-background"></circle>
					<circle cx="20" cy="20" stroke-width="15px" r="25" class="academy-progressbar__circle-progress" transform="rotate(-90 20 20)" style="stroke-dasharray: <?php echo esc_attr( $dashArray ); ?>; stroke-dashoffset: <?php echo esc_attr( $dashOffset ); ?>;"></circle>
				</svg>
				<span class="academy-progressbar__text">
					<?php echo esc_html( $percentage ); ?>%
				</span>
			</div>
			<div class="academy-course-progress__label">
				<p><?php echo esc_html_e( 'Your Progress', 'academy' ); ?></p>
				<span class="academy-icon academy-icon--angle-down"></span>
			</div>
		</div>
		<div id="academy-lesson-share-btn" data-course-permalink="<?php echo esc_url( get_the_permalink( $course_id ) ); ?>"></div>
		<a href="<?php echo esc_url( get_the_permalink( $course_id ) ); ?>" class="academy-course-close">
			<span class="academy-icon academy-icon--close"></span>
		</a>
	</div>
</div>
