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
			)
		);
	}
	public function add_sections( $wp_customize ) {
		new Section\ArchiveCourse( $wp_customize );
		new Section\SingleCourse( $wp_customize );
		new Section\LearnPage( $wp_customize );
		new Section\FrontendDashboard( $wp_customize );
	}
}
