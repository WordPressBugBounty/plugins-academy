<?php
namespace Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Customizer {

	public static function init() {
		$self = new self();
		$self->register_control();
		$self->enqueue_assets();
		$self->dispatch_hook();
	}

	public function register_control() {
		$register = new Customizer\Register();
		add_action( 'customize_register', array( $register, 'add_panel' ) );
		add_action( 'customize_register', array( $register, 'add_sections' ) );
	}

	public function enqueue_assets() {
		$assets = new Customizer\Assets();
		add_action( 'customize_controls_enqueue_scripts', array( $assets, 'enqueue_scripts' ) );
		add_action( 'customize_controls_print_scripts', array( $assets, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'styles_loaded' ) );
	}

	public function dispatch_hook() {
		add_action( 'customize_save_after', array( $this, 'clear_cache' ) );
	}

	public function get_common_dynamic_css() {
		$customizer_css = '';
		$archiveCourse  = apply_filters( 'academy/course_archive_styles', Customizer\Style\Archive::get_css() );
		$singleCourse   = apply_filters( 'academy/course_single_course_styles', Customizer\Style\SingleCourse::get_css() );
		$learnPage   = apply_filters( 'academy/course_learn_page_styles', Customizer\Style\LearnPage::get_css() );
		$customizer_css .= $archiveCourse;
		$customizer_css .= $singleCourse;
		$customizer_css .= $learnPage;
		return \Academy\Helper::minify_css( $customizer_css );
	}

	public function get_dashboard_dynamic_css() {
		$customizer_css = '';
		$frontendCourse   = apply_filters( 'academy/frontend_dashboard_styles', Customizer\Style\FrontendDashboard::get_css() );
		$customizer_css .= $frontendCourse;
		return \Academy\Helper::minify_css( $customizer_css );
	}

	public function styles_loaded() {
		global $wp_customize;

		// Dynamic Common CSS
		$common_dynamic_css = '';
		if ( isset( $wp_customize ) ) {
			$common_dynamic_css  = $this->get_common_dynamic_css();
		} else {
			$common_dynamic_css = get_theme_mod( 'academy_dynamic_common_css', false );
			if ( false === $common_dynamic_css ) {
				$common_dynamic_css = $this->get_common_dynamic_css();
				set_theme_mod( 'academy_dynamic_common_css', $common_dynamic_css );
			}
		}
		if ( ! empty( $common_dynamic_css ) ) {
			wp_add_inline_style( 'academy-common-styles', $common_dynamic_css );
		}

		// Dynamic Dashboard CSS
		$frontend_dashboard_dynamic_css = '';
		if ( isset( $wp_customize ) ) {
			$frontend_dashboard_dynamic_css  = $this->get_dashboard_dynamic_css();
		} else {
			$frontend_dashboard_dynamic_css = get_theme_mod( 'academy_dynamic_dashboard_css', false );
			if ( false === $frontend_dashboard_dynamic_css ) {
				$frontend_dashboard_dynamic_css = $this->get_dashboard_dynamic_css();
				set_theme_mod( 'academy_dynamic_dashboard_css', $frontend_dashboard_dynamic_css );
			}
		}
		if ( ! empty( $frontend_dashboard_dynamic_css ) ) {
			wp_add_inline_style( 'academy-frontend-dashboard-styles', $frontend_dashboard_dynamic_css );
		}
	}

	public function clear_cache() {
		remove_theme_mod( 'academy_dynamic_css' ); // remove after 1.5.2 all user migrate
		remove_theme_mod( 'academy_dynamic_common_css' );
		remove_theme_mod( 'academy_dynamic_dashboard_css' );
	}
}
