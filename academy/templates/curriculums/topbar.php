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
	$is_favorited = \Academy\Helper::is_favorite_course( $course_id );
	$is_qa_on     = get_post_meta( $course_id, 'academy_is_enabled_course_qa', true );
	$is_ann_on    = get_post_meta( $course_id, 'academy_is_enabled_course_announcements', true );
	// Same gate the React learn page uses for its "Take Note" button: the
	// Notes addon being active.
	$is_note_on   = \Academy\Helper::get_addon_active_status( 'notes' );
?>

<div class="academy-lesson-topbar academy-lesson-topbar--sticky">
	<div class="academy-lesson-topbar__left">
		<a class="academy-lesson-breadcrumb" href="<?php echo esc_url( get_the_permalink( $course_id ) ); ?>" title="<?php esc_attr_e( 'Back to course details', 'academy' ); ?>">
			<span class="academy-lesson-breadcrumb__back"><span class="academy-icon academy-icon--arrow-left"></span></span>
			<span class="academy-lesson-breadcrumb__text">
				<small><?php esc_html_e( 'Back to course', 'academy' ); ?></small>
				<b><?php echo esc_html( get_the_title( $course_id ) ); ?></b>
			</span>
		</a>
		<button type="button" class="academy-lesson-curriculum-toggle" data-academy-drawer-open="curriculum" aria-label="<?php esc_attr_e( 'Course content', 'academy' ); ?>" title="<?php esc_attr_e( 'Course content', 'academy' ); ?>">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"></path></svg>
			<span class="academy-lesson-curriculum-toggle__label"><?php esc_html_e( 'Course content', 'academy' ); ?></span>
		</button>
	</div>
	<div class="academy-lesson-topbar__right">
		<button type="button" class="academy-progress-trigger" data-academy-drawer-open="progress" aria-label="<?php esc_attr_e( 'Your progress', 'academy' ); ?>">
			<div class="academy-progressbar">
				<svg width="40" height="40" viewBox="0 0 40 40">
					<circle cx="20" cy="20" stroke-width="15px" r="25" class="academy-progressbar__circle-background"></circle>
					<circle cx="20" cy="20" stroke-width="15px" r="25" class="academy-progressbar__circle-progress" transform="rotate(-90 20 20)" style="stroke-dasharray: <?php echo esc_attr( $dashArray ); ?>; stroke-dashoffset: <?php echo esc_attr( $dashOffset ); ?>;"></circle>
				</svg>
				<span class="academy-progressbar__text"><?php echo esc_html( $percentage ); ?>%</span>
			</div>
			<span class="academy-progress-trigger__label"><?php echo esc_html( $progress_ber_text ); ?></span>
		</button>
		<?php if ( $is_note_on ) : ?>
			<div id="academy-notebook-mount" data-course-id="<?php echo esc_attr( $course_id ); ?>"></div>
		<?php endif; ?>
		<?php if ( $is_ann_on ) : ?>
			<button type="button" class="academy-lesson-activity-btn" data-academy-drawer-open="announcements" aria-label="<?php esc_attr_e( 'Announcements', 'academy' ); ?>" title="<?php esc_attr_e( 'Announcements', 'academy' ); ?>">
				<span class="academy-icon academy-icon--announcement"></span>
			</button>
		<?php endif; ?>
		<?php if ( $is_qa_on && is_user_logged_in() ) : ?>
			<button type="button" class="academy-lesson-activity-btn" data-academy-drawer-open="qa" aria-label="<?php esc_attr_e( 'Questions & answers', 'academy' ); ?>" title="<?php esc_attr_e( 'Questions & answers', 'academy' ); ?>">
				<span class="academy-icon academy-icon--qa"></span>
			</button>
		<?php endif; ?>
		<div class="academy-lesson-menu" data-academy-menu>
			<button type="button" class="academy-lesson-activity-btn academy-lesson-menu__toggle" aria-label="<?php esc_attr_e( 'More options', 'academy' ); ?>" aria-haspopup="true" aria-expanded="false">
				<span class="academy-icon academy-icon--three-dots-menu"></span>
			</button>
			<div class="academy-lesson-menu__dropdown">
				<?php if ( is_user_logged_in() ) : ?>
				<button type="button" class="academy-lesson-menu__item academy-favorite-toggle" data-course-id="<?php echo esc_attr( $course_id ); ?>" data-favorited="<?php echo esc_attr( $is_favorited ? '1' : '0' ); ?>">
					<span class="academy-icon <?php echo esc_attr( $is_favorited ? 'academy-icon--star' : 'academy-icon--empty-star' ); ?>"></span>
					<span class="academy-favorite-toggle__label"><?php echo esc_html( $is_favorited ? __( 'Remove from favorites', 'academy' ) : __( 'Add to favorites', 'academy' ) ); ?></span>
				</button>
				<?php endif; ?>
				<div id="academy-lesson-share-btn" class="academy-lesson-menu__item academy-lesson-menu__item--share" data-course-permalink="<?php echo esc_url( get_the_permalink( $course_id ) ); ?>"></div>
				<a href="<?php echo esc_url( get_the_permalink( $course_id ) ); ?>" class="academy-lesson-menu__item academy-lesson-menu__item--close">
					<span class="academy-icon academy-icon--close"></span>
					<?php esc_html_e( 'Close course', 'academy' ); ?>
				</a>
			</div>
		</div>
	</div>
</div>
