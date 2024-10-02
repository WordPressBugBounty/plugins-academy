<?php
namespace Academy\Customizer\Section;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Customizer\Control\Separator;
use Academy\Customizer\SectionBase;
use Academy\Interfaces\CustomizerSectionInterface;

class FrontendDashboard extends SectionBase implements CustomizerSectionInterface {

	public function __construct( $wp_customize ) {
		$this->register_section( $wp_customize );
		$this->dispatch_settings( $wp_customize );
		$this->dispatch_style_settings( $wp_customize );
	}

	public function register_section( $wp_customize ) {
		$wp_customize->add_section(
			'academy_frontend_dashboard',
			array(
				'title'    => __( 'Frontend Dashboard', 'academy' ),
				'priority' => 10,
				'panel'    => 'academylms',
			)
		);
	}

	public function dispatch_settings( $wp_customize ) {

	}
	public function dispatch_style_settings( $wp_customize ) {
		/**
		 * Sidebar Menu Style
		 */
		$wp_customize->add_setting('frontend_dashboard_menu_heading', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'frontend_dashboard_menu_heading',
				array(
					'label'         => esc_html__( 'Sidebar Menu Style Options', 'academy' ),
					'settings'      => 'frontend_dashboard_menu_heading',
					'section'       => 'academy_frontend_dashboard',
				)
			)
		);

		// Menu Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'frontend_dashboard_sidebar_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'frontend_dashboard_sidebar_bg_color' ),
			array(
				'selector'            => '.academy-courses .academy-courses__header',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'frontend_dashboard_sidebar_bg_color' ),
				array(
					'label'    => __( 'Background Color', 'academy' ),
					'section'  => 'academy_frontend_dashboard',
					'settings' => $this->get_style_settings_id( 'frontend_dashboard_sidebar_bg_color' ),
				)
			)
		);

		// Menu Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'frontend_dashboard_sidebar_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'frontend_dashboard_sidebar_color' ),
			array(
				'selector'            => '.academy-courses .academy-courses__header',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'frontend_dashboard_sidebar_color' ),
				array(
					'label'    => __( 'Text Color', 'academy' ),
					'section'  => 'academy_frontend_dashboard',
					'settings' => $this->get_style_settings_id( 'frontend_dashboard_sidebar_color' ),
				)
			)
		);

		// Menu Icon Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'frontend_dashboard_sidebar_icon_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'frontend_dashboard_sidebar_icon_color' ),
			array(
				'selector'            => '.academy-courses .academy-courses__header',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'frontend_dashboard_sidebar_icon_color' ),
				array(
					'label'    => __( 'Icon Color', 'academy' ),
					'section'  => 'academy_frontend_dashboard',
					'settings' => $this->get_style_settings_id( 'frontend_dashboard_sidebar_icon_color' ),
				)
			)
		);

		// Hover Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'frontend_dashboard_sidebar_menu_hover_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'frontend_dashboard_sidebar_menu_hover_color' ),
			array(
				'selector'            => '.academy-courses .academy-courses__header',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'frontend_dashboard_sidebar_menu_hover_color' ),
				array(
					'label'    => __( 'Hover Color', 'academy' ),
					'section'  => 'academy_frontend_dashboard',
					'settings' => $this->get_style_settings_id( 'frontend_dashboard_sidebar_menu_hover_color' ),
				)
			)
		);

		/**
		 * Topbar Style
		 */
		$wp_customize->add_setting('frontend_dashboard_topbar_heading', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'frontend_dashboard_topbar_heading',
				array(
					'label'         => esc_html__( 'Topbar Style Options', 'academy' ),
					'settings'      => 'frontend_dashboard_topbar_heading',
					'section'       => 'academy_frontend_dashboard',
				)
			)
		);
		// background
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'frontend_dashboard_topbar_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'frontend_dashboard_topbar_bg_color' ),
			array(
				'selector'            => '.academy-courses .academy-courses__header',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'frontend_dashboard_topbar_bg_color' ),
				array(
					'label'    => __( 'Background Color', 'academy' ),
					'section'  => 'academy_frontend_dashboard',
					'settings' => $this->get_style_settings_id( 'frontend_dashboard_topbar_bg_color' ),
				)
			)
		);
		// Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'frontend_dashboard_topbar_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'frontend_dashboard_topbar_color' ),
			array(
				'selector'            => '.academy-courses .academy-courses__header',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'frontend_dashboard_topbar_color' ),
				array(
					'label'    => __( 'Text Color', 'academy' ),
					'section'  => 'academy_frontend_dashboard',
					'settings' => $this->get_style_settings_id( 'frontend_dashboard_topbar_color' ),
				)
			)
		);

		// Border Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'frontend_dashboard_topbar_border_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'frontend_dashboard_topbar_border_color' ),
			array(
				'selector'            => '.academy-courses .academy-courses__header',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'frontend_dashboard_topbar_border_color' ),
				array(
					'label'    => __( 'Border Color', 'academy' ),
					'section'  => 'academy_frontend_dashboard',
					'settings' => $this->get_style_settings_id( 'frontend_dashboard_topbar_border_color' ),
				)
			)
		);

		/**
		 * Card Style
		 */
		$wp_customize->add_setting('frontend_dashboard_card_heading', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'frontend_dashboard_card_heading',
				array(
					'label'         => esc_html__( 'Card Style Options', 'academy' ),
					'settings'      => 'frontend_dashboard_card_heading',
					'section'       => 'academy_frontend_dashboard',
				)
			)
		);

		// background
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'frontend_dashboard_card_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'frontend_dashboard_card_bg_color' ),
			array(
				'selector'            => '.academy-courses .academy-courses__header',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'frontend_dashboard_card_bg_color' ),
				array(
					'label'    => __( 'Background Color', 'academy' ),
					'section'  => 'academy_frontend_dashboard',
					'settings' => $this->get_style_settings_id( 'frontend_dashboard_card_bg_color' ),
				)
			)
		);

		// Border Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'frontend_dashboard_card_border_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'frontend_dashboard_card_border_color' ),
			array(
				'selector'            => '.academy-courses .academy-courses__header',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'frontend_dashboard_card_border_color' ),
				array(
					'label'    => __( 'Border Color', 'academy' ),
					'section'  => 'academy_frontend_dashboard',
					'settings' => $this->get_style_settings_id( 'frontend_dashboard_card_border_color' ),
				)
			)
		);

		// Text
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'frontend_dashboard_card_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'frontend_dashboard_card_color' ),
			array(
				'selector'            => '.academy-courses .academy-courses__header',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'frontend_dashboard_card_color' ),
				array(
					'label'    => __( 'Text Color', 'academy' ),
					'section'  => 'academy_frontend_dashboard',
					'settings' => $this->get_style_settings_id( 'frontend_dashboard_card_color' ),
				)
			)
		);

		/**
		 * Table Style
		 */
		$wp_customize->add_setting('frontend_dashboard_table_heading', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'frontend_dashboard_table_heading',
				array(
					'label'         => esc_html__( 'Table Style Options', 'academy' ),
					'settings'      => 'frontend_dashboard_table_heading',
					'section'       => 'academy_frontend_dashboard',
				)
			)
		);

		// background
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'frontend_dashboard_table_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'frontend_dashboard_table_bg_color' ),
			array(
				'selector'            => '.academy-courses .academy-courses__header',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'frontend_dashboard_table_bg_color' ),
				array(
					'label'    => __( 'Background Color', 'academy' ),
					'section'  => 'academy_frontend_dashboard',
					'settings' => $this->get_style_settings_id( 'frontend_dashboard_table_bg_color' ),
				)
			)
		);

		// Border Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'frontend_dashboard_table_border_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'frontend_dashboard_table_border_color' ),
			array(
				'selector'            => '.academy-courses .academy-courses__header',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'frontend_dashboard_table_border_color' ),
				array(
					'label'    => __( 'Border Color', 'academy' ),
					'section'  => 'academy_frontend_dashboard',
					'settings' => $this->get_style_settings_id( 'frontend_dashboard_table_border_color' ),
				)
			)
		);

		// Text
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'frontend_dashboard_table_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'frontend_dashboard_table_color' ),
			array(
				'selector'            => '.academy-courses .academy-courses__header',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'frontend_dashboard_table_color' ),
				array(
					'label'    => __( 'Text Color', 'academy' ),
					'section'  => 'academy_frontend_dashboard',
					'settings' => $this->get_style_settings_id( 'frontend_dashboard_table_color' ),
				)
			)
		);

	}

}
