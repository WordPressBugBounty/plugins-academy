<?php
namespace Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Academy\Classes\ScriptsBase;

class Assets extends ScriptsBase {

	public static function init() {
		$self = new self();
		add_action( 'admin_enqueue_scripts', [ $self, 'backend_scripts' ], defined( 'ACADEMY_BACKEND_SCRIPTS_PRIORITY' ) ? ACADEMY_BACKEND_SCRIPTS_PRIORITY : 10 );
		add_action( 'wp_enqueue_scripts', [ $self, 'frontend_scripts' ], defined( 'ACADEMY_FRONTEND_SCRIPTS_PRIORITY' ) ? ACADEMY_FRONTEND_SCRIPTS_PRIORITY : 10 );
	}

	/**
	 * Enqueue Files on Start Plugin
	 *
	 * @param string $hook
	 * @function backend_scripts
	 */
	public function backend_scripts( $hook ) {
		if ( \Academy\Helper::plugin_page_hook_suffix( $hook ) ) {
			// Remove Notices
			remove_all_actions( 'admin_notices' );
			// dequeue third party plugin assets
			add_action(
				'wp_print_scripts',
				function () {
					$isSkip = apply_filters( 'academy/skip_no_conflict_backend_scripts', Helper::is_dev_mode_enable() );

					if ( $isSkip ) {
						return;
					}

					global $wp_scripts;
					if ( ! $wp_scripts ) {
						return;
					}

					$pluginUrl = plugins_url();
					foreach ( $wp_scripts->queue as $script ) {
						$src = $wp_scripts->registered[ $script ]->src;
						if ( strpos( $src, $pluginUrl ) !== false && ! strpos( $src, ACADEMY_PLUGIN_SLUG ) !== false ) {
							wp_dequeue_script( $wp_scripts->registered[ $script ]->handle );
						}
					}
				},
				1
			);

			$this->load_web_font_and_icon();
			wp_enqueue_style( 'academy-admin-style', ACADEMY_ASSETS_URI . 'build/backend.css', array( 'wp-components' ), filemtime( ACADEMY_ASSETS_DIR_PATH . 'build/backend.css' ), 'all' );

			if ( ! did_action( 'wp_enqueue_media' ) ) {
				wp_enqueue_media();
			}

			// js
			$dependencies = include ACADEMY_ASSETS_DIR_PATH . sprintf( 'build/backend.%s.asset.php', ACADEMY_VERSION );
			wp_enqueue_script(
				'academy-admin-scripts',
				ACADEMY_ASSETS_URI . sprintf( 'build/backend.%s.js', ACADEMY_VERSION ),
				$dependencies['dependencies'],
				$dependencies['version'],
				true
			);
			wp_localize_script( 'academy-admin-scripts', 'AcademyGlobal', $this->get_backend_scripts_data() );
			wp_set_script_translations( 'academy-admin-scripts', 'academy', ACADEMY_ROOT_DIR_PATH . 'languages' );
		}//end if
		$this->add_backend_inline_style();
	}



	/**
	 * Enqueue Files on Start Plugin
	 *
	 * @function frontend_scripts
	 */
	public function frontend_scripts() {
		// Shortcode
		if ( apply_filters( 'academy/is_load_common_scripts', $this->is_academy_common_pages() ) ) {
			$this->frontend_common_assets();
		}

		if ( apply_filters( 'academy/is_load_frontend_dashboard_scripts', $this->is_frontend_dashboard_page() ) ) {
			if ( is_user_logged_in() ) {
				$this->frontend_dashboard_assets();
			} else {
				// if not logged in then also load common assets for login form
				$this->frontend_common_assets();
			}
		}

		// Pages
		if ( apply_filters( 'academy/is_load_course_lessons_scripts', $this->is_course_lesson_page() ) ) {
			$this->frontend_lessons_assets();
		}
		if ( apply_filters( 'academy/is_load_course_lessons_php_render_scripts', $this->is_course_php_render_lesson_page() ) ) {
			$this->frontend_lessons_php_render_assets();
		}
	}

	public function frontend_common_assets() {
		$this->load_web_font_and_icon();
		$dependencies = include ACADEMY_ASSETS_DIR_PATH . sprintf( 'build/frontendCommon.%s.asset.php', ACADEMY_VERSION );
		// CSS
		wp_enqueue_style( 'academy-plyr', ACADEMY_ASSETS_URI . 'lib/css/plyr.css', array(), filemtime( ACADEMY_ASSETS_DIR_PATH . 'lib/css/plyr.css' ), 'all' );
		wp_enqueue_style( 'academy-common-styles', ACADEMY_ASSETS_URI . 'build/frontendCommon.css', array(), filemtime( ACADEMY_ASSETS_DIR_PATH . 'build/frontendCommon.css' ), 'all' );
		// js
		wp_enqueue_script( 'academy-sticksy', ACADEMY_ASSETS_URI . 'lib/js/sticksy.min.js', array( 'jquery' ), $dependencies['version'], false );
		wp_enqueue_script( 'academy-plyr', ACADEMY_ASSETS_URI . 'lib/js/plyr.js', array( 'jquery' ), $dependencies['version'], false );
		wp_enqueue_script( 'academy-SocialShare', ACADEMY_ASSETS_URI . 'lib/js/SocialShare.min.js', array( 'jquery' ), $dependencies['version'], false );

		// JS
		if ( apply_filters( 'academy/is_load_common_js_scripts', true ) ) {
			wp_enqueue_script( 'academy-SocialShare', ACADEMY_ASSETS_URI . 'lib/js/SocialShare.min.js', array( 'jquery' ), $dependencies['version'], false );
			wp_enqueue_script(
				'academy-common-scripts',
				ACADEMY_ASSETS_URI . sprintf( 'build/frontendCommon.%s.js', ACADEMY_VERSION ),
				$dependencies['dependencies'],
				$dependencies['version'],
				true
			);
			wp_localize_script( 'academy-common-scripts', 'AcademyGlobal', $this->get_frontend_scripts_data() );
			wp_set_script_translations( 'academy-common-scripts', 'academy', ACADEMY_ROOT_DIR_PATH . 'languages' );
		}
	}

	public function frontend_dashboard_assets() {
		$this->load_web_font_and_icon();
		$dependencies = include ACADEMY_ASSETS_DIR_PATH . sprintf( 'build/frontendDashboard.%s.asset.php', ACADEMY_VERSION );
		wp_enqueue_style( 'academy-frontend-dashboard-styles', ACADEMY_ASSETS_URI . 'build/frontendDashboard.css', array( 'wp-components' ), filemtime( ACADEMY_ASSETS_DIR_PATH . 'build/frontendDashboard.css' ), 'all' );

		if ( ! did_action( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}

		$this->load_block_editor_scripts();

		wp_enqueue_script(
			'academy-frontend-dashboard-scripts',
			ACADEMY_ASSETS_URI . sprintf( 'build/frontendDashboard.%s.js', ACADEMY_VERSION ),
			$dependencies['dependencies'],
			$dependencies['version'],
			true
		);
		wp_localize_script( 'academy-frontend-dashboard-scripts', 'AcademyGlobal', $this->get_frontend_scripts_data() );
		wp_set_script_translations( 'academy-frontend-dashboard-scripts', 'academy', ACADEMY_ROOT_DIR_PATH . 'languages' );
	}

	public function frontend_lessons_assets() {
		if ( ! did_action( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}

		$this->load_web_font_and_icon();

		$dependencies = include ACADEMY_ASSETS_DIR_PATH . sprintf( 'build/frontendCurriculums.%s.asset.php', ACADEMY_VERSION );

		// CSS
		wp_enqueue_style( 'academy-plyr', ACADEMY_ASSETS_URI . 'lib/css/plyr.css', array(), filemtime( ACADEMY_ASSETS_DIR_PATH . 'lib/css/plyr.css' ), 'all' );
		// js
		wp_enqueue_script( 'academy-plyr', ACADEMY_ASSETS_URI . 'lib/js/plyr.js', array( 'jquery' ), $dependencies['version'], false );

		wp_enqueue_style( 'academy-course-lessons-styles', ACADEMY_ASSETS_URI . 'build/frontendCurriculums.css', array(), filemtime( ACADEMY_ASSETS_DIR_PATH . 'build/frontendCurriculums.css' ), 'all' );

		wp_enqueue_script(
			'academy-course-lessons-scripts',
			ACADEMY_ASSETS_URI . sprintf( 'build/frontendCurriculums.%s.js', ACADEMY_VERSION ),
			$dependencies['dependencies'],
			$dependencies['version'],
			true
		);
		wp_localize_script( 'academy-course-lessons-scripts', 'AcademyGlobal', $this->get_frontend_scripts_data() );
		wp_set_script_translations( 'academy-course-lessons-scripts', 'academy', ACADEMY_ROOT_DIR_PATH . 'languages' );
	}

	public function frontend_lessons_php_render_assets() {
		if ( ! did_action( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}

		$this->load_web_font_and_icon();

		$dependencies = include ACADEMY_ASSETS_DIR_PATH . sprintf( 'build/frontendPhpCurriculums.%s.asset.php', ACADEMY_VERSION );

		wp_enqueue_style( 'academy-plyr', ACADEMY_ASSETS_URI . 'lib/css/plyr.css', array(), filemtime( ACADEMY_ASSETS_DIR_PATH . 'lib/css/plyr.css' ), 'all' );
		wp_enqueue_script( 'academy-plyr', ACADEMY_ASSETS_URI . 'lib/js/plyr.js', array( 'jquery' ), $dependencies['version'], false );

		wp_enqueue_style( 'academy-course-php-render-lessons-styles', ACADEMY_ASSETS_URI . 'build/frontendPhpCurriculums.css', array(), filemtime( ACADEMY_ASSETS_DIR_PATH . 'build/frontendCurriculums.css' ), 'all' );

		wp_enqueue_script(
			'academy-course-php-render-lessons-scripts',
			ACADEMY_ASSETS_URI . sprintf( 'build/frontendPhpCurriculums.%s.js', ACADEMY_VERSION ),
			$dependencies['dependencies'],
			$dependencies['version'],
			true
		);
		wp_localize_script( 'academy-course-php-render-lessons-scripts', 'AcademyGlobal', $this->get_frontend_scripts_data() );
		wp_set_script_translations( 'academy-course-php-render-lessons-scripts', 'academy', ACADEMY_ROOT_DIR_PATH . 'languages' );
	}

	public function load_web_font_and_icon() {
		// load global styles
		if ( Helper::get_settings( 'is_enabled_academy_web_font' ) || is_admin() ) {
			// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			wp_enqueue_style( 'academy-web-font', $this->web_fonts_url( 'Inter:wght@400,500,600,700&display=swap' ), array() );
		}
		wp_enqueue_style( 'academy-icon', ACADEMY_ASSETS_URI . 'lib/css/academy-icon.css', array(), filemtime( ACADEMY_ASSETS_DIR_PATH . 'lib/css/academy-icon.css' ), 'all' );
	}
}
