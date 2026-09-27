<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_id = \Academy\Helper::get_the_current_course_id();

// Learn page options (validate against allowed values).
$learn_sidebar_pos  = \Academy\Helper::get_settings( 'learn_page_sidebar_position', 'left' );
$learn_sidebar_pos  = in_array( $learn_sidebar_pos, array( 'left', 'right' ), true ) ? $learn_sidebar_pos : 'left';
// The curriculum is a permanent docked sidebar (no overlay/close behavior).
$learn_sidebar_mode = 'push';

$is_qa_enabled  = get_post_meta( $course_id, 'academy_is_enabled_course_qa', true );
$is_ann_enabled = get_post_meta( $course_id, 'academy_is_enabled_course_announcements', true );

$percentage  = \Academy\Helper::get_percentage_of_completed_topics_by_student_and_course_id( get_current_user_id(), $course_id );
$dash_array  = 157.08;
$dash_offset = $dash_array - ( $percentage / 100 * $dash_array );

$total_topics     = \Academy\Helper::get_total_number_of_course_topics( $course_id );
$completed_topics = \Academy\Helper::get_total_number_of_completed_course_topics_by_course_and_student_id( $course_id );
$left_count       = max( $total_topics - $completed_topics, 0 );

$course_title = get_the_title( $course_id );

// Learn page theme (light/dark). 'system' can't be resolved server-side; the
// bootstrap script below resolves it on first paint (same mechanism as the
// React render path in templates/curriculums/js-render.php).
//
// That script mirrors the resolved theme onto <body> as well as the wrap:
// react-modal (and anything else portalling via ReactDOM.createPortal) appends
// its markup as a direct child of <body>, outside the wrap, so without it
// portaled content never inherits the dark-mode CSS variable overrides — those
// are only ever scoped to descendants of the wrap or of <body> itself. See
// includes/classes/global-css.php.
//
// Keep that script comment-free: an HTML minifier collapses it onto one line,
// and a `//` comment would then swallow the rest of it.
$learn_theme = \Academy\Helper::get_settings( 'learn_page_theme_mode', 'light' );
$learn_theme = in_array( $learn_theme, array( 'light', 'dark', 'system' ), true ) ? $learn_theme : 'light';
?>
<?php echo do_shortcode( '[academy_course_curriculum_topbar]' ); ?>
<div class="academy-course-curriculum-wrapper" id="academyPhpLessonsWrap" data-academy-theme="<?php echo esc_attr( $learn_theme ); ?>">
	<script>
		(function () {
			try {
				document.body.setAttribute( 'data-academy-theme', '<?php echo esc_js( $learn_theme ); ?>' );
				var wrap = document.getElementById( 'academyPhpLessonsWrap' );
				var saved = window.localStorage.getItem( 'academy_theme' );
				var resolved = saved;
				if ( 'dark' !== resolved && 'light' !== resolved && 'system' === '<?php echo esc_js( $learn_theme ); ?>' ) {
					var prefersDark = window.matchMedia && window.matchMedia( '(prefers-color-scheme: dark)' ).matches;
					resolved = prefersDark ? 'dark' : 'light';
				}
				if ( 'dark' === resolved || 'light' === resolved ) {
					if ( wrap ) {
						wrap.setAttribute( 'data-academy-user-theme', resolved );
					}
					document.body.setAttribute( 'data-academy-user-theme', resolved );
				}
			} catch ( e ) {}
		})();
	</script>
	<div class="academy-course-curriculum-contents" id="academy-course-curriculum-contents">
		<?php echo do_shortcode( '[academy_course_curriculum_content]' ); ?>
	</div>
</div>

<?php
// ---------- Course content (curriculum) drawer ----------
ob_start();
if ( $total_topics > 0 ) :
	?>
	<div class="academy-lesson-sidebar-progress">
		<div class="academy-lesson-sidebar-progress__track">
			<span class="academy-lesson-sidebar-progress__fill" style="width: <?php echo esc_attr( $percentage ); ?>%;"></span>
		</div>
		<div class="academy-lesson-sidebar-progress__cap">
			<b><?php echo esc_html( $percentage ); ?>% <?php esc_html_e( 'complete', 'academy' ); ?></b>
			<span><?php echo esc_html( $left_count ); ?> <?php esc_html_e( 'left', 'academy' ); ?></span>
		</div>
	</div>
	<?php
endif;
$sidebar_progress_html = ob_get_clean();

\Academy\Helper::get_template(
	'curriculums/partials/learn-drawer.php',
	array(
		'drawer'   => 'curriculum',
		'side'     => $learn_sidebar_pos,
		'mode'     => $learn_sidebar_mode,
		'title'    => __( 'Course content', 'academy' ),
		'subtitle' => $course_title,
		'body'     => '<div class="academy-curriculum-drawer-body">' . $sidebar_progress_html . do_shortcode( '[academy_course_curriculums]' ) . '</div>',
	)
);

// ---------- Progress drawer ----------
ob_start();
?>
<div class="academy-progress-panel">
	<div class="academy-progress-panel__ring">
		<div class="academy-progressbar">
			<svg width="40" height="40" viewBox="0 0 40 40">
				<circle cx="20" cy="20" stroke-width="15px" r="25" class="academy-progressbar__circle-background"></circle>
				<circle cx="20" cy="20" stroke-width="15px" r="25" class="academy-progressbar__circle-progress" transform="rotate(-90 20 20)" style="stroke-dasharray: <?php echo esc_attr( $dash_array ); ?>; stroke-dashoffset: <?php echo esc_attr( $dash_offset ); ?>;"></circle>
			</svg>
			<span class="academy-progressbar__text"><?php echo esc_html( $percentage ); ?>%</span>
		</div>
		<div class="academy-progress-panel__pct">
			<b><?php echo esc_html( $percentage ); ?>%</b>
			<span><?php esc_html_e( 'course completed', 'academy' ); ?></span>
		</div>
	</div>
</div>
<?php
$progress_body = ob_get_clean();
\Academy\Helper::get_template(
	'curriculums/partials/learn-drawer.php',
	array(
		'drawer'   => 'progress',
		'side'     => 'right',
		'title'    => __( 'Your Progress', 'academy' ),
		'subtitle' => $course_title,
		'body'     => $progress_body,
	)
);

// ---------- Q&A drawer ----------
if ( $is_qa_enabled && is_user_logged_in() ) {
	\Academy\Helper::get_template(
		'curriculums/partials/learn-drawer.php',
		array(
			'drawer'   => 'qa',
			'side'     => 'right',
			'title'    => __( 'Questions & Answers', 'academy' ),
			'subtitle' => $course_title,
			'body'     => do_shortcode( '[academy_course_questions_answers]' ),
		)
	);
}

// ---------- Announcements drawer ----------
if ( $is_ann_enabled ) {
	\Academy\Helper::get_template(
		'curriculums/partials/learn-drawer.php',
		array(
			'drawer'   => 'announcements',
			'side'     => 'right',
			'title'    => __( 'Announcements', 'academy' ),
			'subtitle' => $course_title,
			'body'     => do_shortcode( '[academy_course_announcements]' ),
		)
	);
}
