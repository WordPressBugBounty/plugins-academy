<?php
namespace Academy\Customizer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class Register {

	public function add_panel( $wp_customize ) {
		$panel_title = apply_filters( 'academy/customizer/panel_title', __( 'Academy LMS', 'academy' ) );
		$wp_customize->add_panel(
			'academylms',
			array(
				'priority'       => 200,
				'capability'     => 'manage_options',
				'theme_supports' => '',
				'title'          => $panel_title,
				'description'    => sprintf(
					/* translators: %s: link to the Design screen. */
					__( 'Colours, course cards and course pages are set on %s.', 'academy' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=academy-design' ) ) . '">' . esc_html__( 'Academy LMS → Customize', 'academy' ) . '</a>'
				),
			)
		);
	}
	public function add_sections( $wp_customize ) {
		if ( ! \Academy\Design\Settings::mode()['applies'] ) {
			new Section\ArchiveCourse( $wp_customize );
			new Section\SingleCourse( $wp_customize );
		}
		new Section\ArchiveTutorBooking( $wp_customize );
		new Section\SingleBooking( $wp_customize );
		new Section\LearnPage( $wp_customize );
		new Section\FrontendDashboard( $wp_customize );
	}
}
