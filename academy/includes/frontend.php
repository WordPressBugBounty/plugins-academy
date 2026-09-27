<?php
namespace Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Frontend {

	public static function init() {
		$self = new self();

		Frontend\Comments::init();
		Frontend\Template::init();

		$self->register_hooks();
	}

	private function register_hooks() {
		add_filter( 'the_content', [ $this, 'assign_shortcode_to_page_content' ], 9 );
		add_action( 'wp_footer', [ $this, 'add_react_modal_div' ] );
		add_action( 'init', [ $this, 'disable_admin_topbar_for_student_role' ] );
		add_action( 'init', [ $this, 'disable_admin_bar_for_curriculum_frame' ] );

		// Isolated full-page renderer for a curriculum item's shortcode/block
		// content (loaded in an iframe by the learn page). Running the normal
		// wp_head → content → wp_footer pipeline lets ANY shortcode's enqueued
		// assets load exactly as on a real page — impossible over AJAX.
		add_action( 'template_redirect', [ $this, 'render_curriculum_frame' ], 0 );

		// Reset password form template
		if ( ! is_user_logged_in() ) {
			add_action( 'template_redirect', [ $this, 'validate_reset_password_request' ] );
			add_filter( 'template_include', [ $this, 'load_reset_password_template' ] );
		}
		// course coming soon
		if ( \Academy\Helper::get_settings( 'is_enabled_course_coming_soon' ) ) {
			add_filter( 'academy/templates/single_course/enroll_form', array( $this, 'get_course_coming_soon_content' ), 15, 2 );
			add_filter( 'academy/assets/frontend_scripts_data', array( $this, 'add_course_coming_soon_time' ) );
			add_filter( 'academy/template/loop/footer_form', array( $this, 'get_course_type' ), 15, 2 );
			add_filter( 'academy/templates/loop/price', array( $this, 'get_course_type' ), 15, 2 );
		}
	}

	public function disable_admin_topbar_for_student_role() {
		$user = wp_get_current_user();
		if ( $user && in_array( 'academy_student', (array) $user->roles, true ) ) {
			add_filter( 'show_admin_bar', '__return_false' ); // phpcs:ignore WordPressVIPMinimum.UserExperience.AdminBarRemoval.RemovalDetected -- students get no admin bar
		}
	}

	/**
	 * Must run on `init`, not inside render_curriculum_frame() on
	 * `template_redirect`. Core's own `_wp_admin_bar_init()` is hooked on
	 * `template_redirect` at the same priority 0 but registered first (core
	 * boots before this plugin), so by the time render_curriculum_frame() ran
	 * a `show_admin_bar` filter it was already too late: `_admin_bar_bump_cb`
	 * had already been hooked onto `wp_head`, which unconditionally prints the
	 * `html { margin-top: 32px !important; }` bump CSS regardless of any
	 * `show_admin_bar` filter added afterwards — leaving a 32px gap at the top
	 * of this chrome-free frame. Real access is still nonce+permission gated
	 * in render_curriculum_frame(); this is a cosmetic-only, unauthenticated
	 * toggle that only ever hides an admin bar, so it's safe pre-nonce.
	 */
	public function disable_admin_bar_for_curriculum_frame() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- cosmetic-only toggle, not a data-changing request.
		if ( isset( $_GET['academy_curriculum_frame'] ) ) {
			add_filter( 'show_admin_bar', '__return_false' ); // phpcs:ignore WordPressVIPMinimum.UserExperience.AdminBarRemoval.RemovalDetected -- chrome-free preview frame
		}
	}
	public function assign_shortcode_to_page_content( $content ) {
		// if content have any data then render that content
		if ( ! empty( $content ) ) {
			return $content;
		}

		// Dashboard Page
		$user_dashboard_page_ID = (int) Helper::get_settings( 'frontend_dashboard_page' );
		$student_reg_page_ID    = (int) Helper::get_settings( 'frontend_student_reg_page' );
		$instructor_reg_page_ID = (int) Helper::get_settings( 'frontend_instructor_reg_page' );
		$password_reset_page_ID = (int) Helper::get_settings( 'password_reset_page' );

		if ( get_the_ID() === $user_dashboard_page_ID ) {
			return '[academy_dashboard]';
		} elseif ( get_the_ID() === $student_reg_page_ID ) {
			return '[academy_student_registration_form]';
		} elseif ( get_the_ID() === $instructor_reg_page_ID ) {
			return '[academy_instructor_registration_form]';
		} elseif ( get_the_ID() === $password_reset_page_ID ) {
			return '[academy_password_reset_form]';
		}
		return $content;
	}
	public function add_react_modal_div() {
		echo '<div id="academyFrontendModalWrap"></div>';
	}

	/**
	 * Render a single curriculum item's shortcode/block content as a minimal,
	 * standalone HTML document — served to an iframe on the learn page.
	 *
	 * Because this is a real page load, `wp_head()` fires `wp_enqueue_scripts`
	 * (so plugins register their handles) BEFORE the content renders and
	 * enqueues them, and `wp_footer()` prints every asset. That makes ANY
	 * shortcode / block (TruePlayer, maps, players, embeds …) work exactly as
	 * it would on a normal page — which the AJAX/SPA path can never do.
	 *
	 * Access is gated the same way the learn page gates topic content.
	 */
	public function render_curriculum_frame() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['academy_curriculum_frame'] ) ) {
			return;
		}

		$item_id   = isset( $_GET['item_id'] ) ? absint( $_GET['item_id'] ) : 0;
		$course_id = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0;
		$field     = isset( $_GET['field'] ) ? sanitize_key( $_GET['field'] ) : 'content';
		$nonce     = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! wp_verify_nonce( $nonce, 'academy_nonce' ) ) {
			status_header( 403 );
			exit;
		}

		if ( ! \Academy\Helper::has_permission_to_access_lesson_curriculum( $course_id, $item_id, get_current_user_id() ) ) {
			status_header( 403 );
			exit;
		}

		// Admin bar is already disabled by disable_admin_bar_for_curriculum_frame()
		// on `init` — too late to do it here, see that method's docblock.

		$raw    = '';
		$lesson = \Academy\Lesson\LessonApi\Lesson::get_by_id( $item_id, false, null, 'publish' );
		$lesson = $lesson ? $lesson->get_data() : null;
		if ( $lesson ) {
			if ( 'video' === $field ) {
				$video = $lesson['meta']['video_source'] ?? array();
				$raw   = ( isset( $video['type'], $video['url'] ) && 'short_code' === $video['type'] )
					? (string) $video['url']
					: '';
			} else {
				$raw = (string) ( $lesson['lesson_content'] ?? '' );
			}
		}
		$raw = stripslashes( $raw );

		nocache_headers();
		status_header( 200 );
		header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );

		// The template runs the normal wp_head -> content -> wp_footer pipeline
		// so the shortcode's assets register (wp_head) before it renders and
		// print (wp_footer) — exactly as on a real page. Theme-overridable via
		// yourtheme/academy/curriculums/frame.php.
		\Academy\Helper::get_template(
			'curriculums/frame.php',
			array( 'content' => \Academy\Helper::get_content_html( $raw ) )
		);
		exit;
	}

	/**
	 * Validate reset link (NO rendering here)
	 */
	public function validate_reset_password_request() {

		if ( ! get_query_var( 'academy_retrieve_password' ) ) {
			return;
		}

		// The emailed reset link carries no nonce — check_password_reset_key() is its proof.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['reset_key'] ) || empty( $_GET['login'] ) ) {
			wp_die( esc_html__( 'Invalid or expired reset link.', 'academy' ) );
		}

		$reset_key = sanitize_text_field( wp_unslash( $_GET['reset_key'] ) );
		$login = sanitize_text_field( wp_unslash( $_GET['login'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$user = get_user_by( 'login', $login );

		if ( ! $user ) {
			wp_die( esc_html__( 'Invalid reset request.', 'academy' ) );
		}

		$validated_user = check_password_reset_key( $reset_key, $user->user_login );

		if ( is_wp_error( $validated_user ) ) {
			wp_die(
				esc_html__( 'This reset link is invalid or has already been used.', 'academy' )
			);
		}

		set_query_var( 'academy_valid_reset', true );
	}

	public function load_reset_password_template( $template ) {

		if (
			get_query_var( 'academy_retrieve_password' ) &&
			get_query_var( 'academy_valid_reset' )
		) {
			return \Academy\Helper::get_template( 'reset-password.php' );
		}

		return $template;
	}

	public function get_course_coming_soon_content( $html, $course_id ) {
		$end_at = get_post_meta(
			$course_id,
			'academy_course_coming_soon_end_date',
			true
		);

		if ( empty( $end_at ) ) {
			return $html;
		}

		$end_time     = self::get_time_stamp( $end_at );

		if ( $end_time < time() ) {
			return $html;
		}
		ob_start();

		\Academy\Helper::get_template( 'single-course/coming-soon.php', [ 'course_id' => $course_id ] );

		return ob_get_clean();
	}

	public function add_course_coming_soon_time( $data ) {
		$course_id = get_the_ID();

		$end_at = get_post_meta(
			$course_id,
			'academy_course_coming_soon_end_date',
			true
		);

		if ( empty( $end_at ) ) {
			return $data;
		}

		$end_time = self::get_time_stamp( $end_at );

		if ( $end_time > time() ) {
			$data['academy_course_coming_soon_time'] = (int) $end_time;
		}

		return $data;
	}

	public function get_course_type( $type, $course_id ) {
		$end_at = get_post_meta( $course_id, 'academy_course_coming_soon_end_date', true );
		$end_timestamp  = self::get_time_stamp( $end_at );
		if ( $end_timestamp > time() ) {
			$type = esc_html__( 'Coming Soon', 'academy' );
		}

		return $type;
	}

	public static function get_time_stamp( $end_time ) {
		$timezone = wp_timezone();

		$datetime = date_create_from_format(
			'Y-m-d\TH:i',
			$end_time,
			$timezone
		);

		if ( ! $datetime ) {
			return 0;
		}

		return $datetime->getTimestamp();
	}
}
