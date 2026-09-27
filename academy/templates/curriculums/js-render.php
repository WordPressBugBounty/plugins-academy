<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

		$user_id = get_current_user_id();
		$course_id = get_the_ID();
		$enrolled  = \Academy\Helper::is_enrolled( $course_id, $user_id );
		$is_administrator = current_user_can( 'manage_options' );
		$is_instructor    = \Academy\Helper::is_instructor_of_this_course( $user_id, $course_id );
		$is_public_course = \Academy\Helper::is_public_course( $course_id );
		$lesson_content_width = \Academy\Helper::get_settings( 'lesson_content_width' );
		$lesson_content_width_unit = \Academy\Helper::get_settings( 'lesson_content_width_unit' );
		$is_hp_lesson = \Academy\Helper::get_settings( 'academy_is_hp_lesson_active', false ) ? false : true;
		$lesson_autoplay = \Academy\Helper::get_settings( 'lesson_self_hosted_video_autoplay', true );
		$is_topics_accessible = $is_administrator || $enrolled || $is_instructor || $is_public_course;
		$is_reviewed = \Academy\Helper::get_review_by_user( $user_id, $course_id ) ? true : false;
		// Learn page layout (admin-global, composable). Validate against allowed values.
		$learn_nav   = \Academy\Helper::get_settings( 'learn_page_nav_placement', 'right' );
		$learn_width = \Academy\Helper::get_settings( 'learn_page_layout_width', 'standard' );
		$learn_panel = \Academy\Helper::get_settings( 'learn_page_show_side_panel', false );
		$learn_nav   = in_array( $learn_nav, array( 'left', 'right', 'top', 'hidden', 'bottom' ), true ) ? $learn_nav : 'right';
		$learn_width = in_array( $learn_width, array( 'standard', 'wide', 'focused' ), true ) ? $learn_width : 'standard';
		$learn_panel = $learn_panel ? 'on' : 'off';
		$learn_content_header = \Academy\Helper::get_settings( 'learn_page_show_content_header', true ) ? 'on' : 'off';
		$learn_sidebar_open  = \Academy\Helper::get_settings( 'learn_page_sidebar_default_open', true ) ? '1' : '0';
		$learn_sidebar_pos   = \Academy\Helper::get_settings( 'learn_page_sidebar_position', 'left' );
		$learn_sidebar_pos   = in_array( $learn_sidebar_pos, array( 'left', 'right' ), true ) ? $learn_sidebar_pos : 'left';
		$learn_content_load  = \Academy\Helper::get_settings( 'learn_page_content_load', 'ajax' );
		$learn_content_load  = in_array( $learn_content_load, array( 'ajax', 'reload' ), true ) ? $learn_content_load : 'ajax';
		$learn_mark_complete = \Academy\Helper::get_settings( 'learn_page_show_mark_complete', true ) ? 'on' : 'off';
		$learn_theme_toggle   = \Academy\Helper::get_settings( 'learn_page_learner_theme_toggle', true ) ? 'on' : 'off';
		$learn_width_toggle   = \Academy\Helper::get_settings( 'learn_page_learner_width_toggle', true ) ? 'on' : 'off';
		// Learn page theme (light/dark). 'system' can't be resolved server-side; the
		// bootstrap script printed inside the wrapper below resolves it on first paint.
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
		<div 
			id="academyLessonsWrap" 
			class="academy-lessons" 
			data-course-title="<?php echo esc_attr( get_the_title() ); ?>" 
			data-course-id="<?php echo esc_attr( get_the_ID() ); ?>" 
			data-course-permalink="<?php echo esc_url( get_the_permalink() ); ?>"
			data-exit-permalink="<?php echo esc_url( apply_filters( 'academy/templates/learn_page_exit_permalink', get_the_permalink() ) ); ?>"
			data-enabled-course-qa="<?php echo esc_attr( get_post_meta( get_the_ID(), 'academy_is_enabled_course_qa', true ) ); ?>"
			data-enabled-course-announcements="<?php echo esc_attr( get_post_meta( get_the_ID(), 'academy_is_enabled_course_announcements', true ) ); ?>"
			data-enabled-course-lesson-comment="<?php echo esc_attr( \Academy\Helper::get_settings( 'is_enabled_academy_lessons_comment', true ) ); ?>"
			data-lesson-content-width="<?php echo esc_attr( $lesson_content_width ); ?>"
			data-lesson-content-width-unit="<?php echo esc_attr( $lesson_content_width_unit ); ?>"
			data-enabled-hp-lesson="<?php echo esc_attr( $is_hp_lesson ); ?>"
			data-enabled-lesson-auto-play="<?php echo esc_attr( $lesson_autoplay ); ?>"
			data-course-type="<?php echo esc_attr( \Academy\Helper::get_course_type( $course_id ) ); ?>"
			data-is-completed-course="<?php echo esc_attr( \Academy\Helper::is_completed_course( get_the_ID(), get_current_user_id() ) ); ?>"
			data-auto-load-next-lesson="<?php echo esc_attr( \Academy\Helper::is_auto_load_next_lesson() ); ?>"
			data-auto-complete-topic="<?php echo esc_attr( \Academy\Helper::is_auto_complete_topic() ); ?>"
			data-is-favorite="<?php echo esc_attr( \Academy\Helper::is_favorite_course( get_the_ID() ) ); ?>"
			data-topics-accessible="<?php echo esc_attr( $is_topics_accessible ); ?>"
			data-enabled-academy-lesson-video-skip="<?php echo esc_attr( \Academy\Helper::get_settings( 'is_disabled_lessons_video_skip', true ) ); ?>"
			data-enabled-header-footer="<?php echo esc_attr( \Academy\Helper::get_settings( 'is_enabled_lessons_theme_header_footer', false ) ); ?>"
			data-is-enabled-course-popup-review="<?php echo esc_attr( \Academy\Helper::get_settings( 'is_enabled_course_popup_review', false ) ); ?>"
			data-minimum-course-completion-on-review="<?php echo esc_attr( \Academy\Helper::get_settings( 'minimum_course_completion_on_review', 0 ) ); ?>"
			data-is-user-reviewed-on-course="<?php echo esc_attr( $is_reviewed ); ?>"
			data-academy-nav="<?php echo esc_attr( $learn_nav ); ?>"
			data-academy-width="<?php echo esc_attr( $learn_width ); ?>"
			data-academy-panel="<?php echo esc_attr( $learn_panel ); ?>"
			data-academy-content-header="<?php echo esc_attr( $learn_content_header ); ?>"
			data-academy-mark-complete="<?php echo esc_attr( $learn_mark_complete ); ?>"
			data-academy-theme-toggle="<?php echo esc_attr( $learn_theme_toggle ); ?>"
			data-academy-width-toggle="<?php echo esc_attr( $learn_width_toggle ); ?>"
			data-sidebar-default-open="<?php echo esc_attr( $learn_sidebar_open ); ?>"
			data-academy-sidebar-position="<?php echo esc_attr( $learn_sidebar_pos ); ?>"
			data-academy-content-load="<?php echo esc_attr( $learn_content_load ); ?>"
			data-academy-theme="<?php echo esc_attr( $learn_theme ); ?>"
		>
			<script>
				(function () {
					try {
						document.body.setAttribute( 'data-academy-theme', '<?php echo esc_js( $learn_theme ); ?>' );
						var wrap = document.getElementById( 'academyLessonsWrap' );
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
			<?php
				$preloader = apply_filters( 'academy/preloader', academy_get_preloader_html() );
				echo wp_kses_post( $preloader );
			?>
		</div>
