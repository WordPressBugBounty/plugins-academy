<?php
namespace Academy\Customizer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

	use Academy;

class Assets {

	public function enqueue_scripts() {
		if ( ! is_customize_preview() ) {
			return;
		}

		$last_course_permalink = get_the_permalink( \Academy\Helper::get_last_course_id() );
		$latest_booking_id = current(
			get_posts(
				array(
					'post_type'      => 'academy_booking',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'orderby'        => 'ID',
					'order'          => 'DESC',
					'fields'         => 'ids',
				)
			)
		);
		$last_booking_permalink = $latest_booking_id ? get_permalink( $latest_booking_id ) : Academy\Helper::get_page_permalink( 'tutor_booking_page' );

		$dependencies = include ACADEMY_ASSETS_DIR_PATH . sprintf( 'build/customizer.%s.asset.php', ACADEMY_VERSION );
		wp_enqueue_script( 'academy-customizer-scripts', ACADEMY_ASSETS_URI . sprintf( 'build/customizer.%s.js', ACADEMY_VERSION ), array_merge( array( 'customize-controls' ), $dependencies['dependencies'] ), $dependencies['version'], true );
		wp_localize_script(
			'academy-customizer-scripts',
			'academyCustomizerSettings',
			array(
				'archiveCoursePageUrl' => esc_url( Academy\Helper::get_page_permalink( 'course_page' ) ),
				'archiveBookingPageUrl' => esc_url( Academy\Helper::get_page_permalink( 'tutor_booking_page' ) ),
				'archiveBookingSinglePageUrl' => esc_url( $last_booking_permalink ),
				'frontendDashboardPageUrl' => esc_url( Academy\Helper::get_page_permalink( 'frontend_dashboard_page' ) ),
				'singleCoursePageUrl' => esc_url( $last_course_permalink ),
			)
		);
		wp_set_script_translations( 'academy-customizer-scripts', 'academy', ACADEMY_ROOT_DIR_PATH . 'languages/' );
	}
	public function enqueue_styles() {
		if ( ! is_customize_preview() ) {
			return;
		}
		wp_enqueue_style( 'academy-customizer-styles', ACADEMY_ASSETS_URI . 'build/customizer.css', array(), filemtime( ACADEMY_ASSETS_DIR_PATH . 'build/customizer.css' ) );
	}
}
