<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<?php echo do_shortcode( '[academy_course_curriculum_topbar]' ); ?>
<div class="academy-course-curriculum-wrapper">
	<div class="academy-course-curriculum-contents" id="academy-course-curriculum-contents">
		<?php echo do_shortcode( '[academy_course_curriculum_content]' ); ?>
		<?php echo do_shortcode( '[academy_tabs render_title="Course Content,Q&A,Announcement" render_shortcode="academy_course_curriculums,academy_course_questions_answers,academy_course_announcements"]' ); ?>
	</div>
	<div class="academy-course-curriculums-container" id="academy-course-curriculums-container">
		<div class="academy-expand-button-wrapper">
			<button class="academy-btn--lesson-expand" type="button" id="academy-curriculum-expand">
				<span class="academy-icon academy-icon--arrow-left"></span>
				<span class="academy-btn--label"><?php echo esc_html( 'Course content' ); ?></span>
			</button>
		</div>
		<div class="academy-course-learn-page-curriculums" id="academy-course-curriculums">
			<div class="academy-lesson-sidebar-content__title">
				<h4><?php echo esc_html__( 'Course content', 'academy' ); ?></h4>
				<button class="academy-btn academy-btn--md academy-btn--preset-transparent academy-btn--close" id="academy-course-curriculums-close-btn" type="button"><span class="academy-icon academy-icon--close"></span></button>
			</div>
			<?php echo do_shortcode( '[academy_course_curriculums]' ); ?>
		</div>
	</div>
</div>

