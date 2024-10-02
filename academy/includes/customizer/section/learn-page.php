<?php
namespace Academy\Customizer\Section;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Customizer\Control\Separator;
use Academy\Customizer\SectionBase;
use Academy\Interfaces\CustomizerSectionInterface;

class LearnPage extends SectionBase implements CustomizerSectionInterface {
	const SECTION = 'academy_learn_page';
	public function __construct( $wp_customize ) {
		$this->register_section( $wp_customize );
		$this->dispatch_settings( $wp_customize );
	}

	public function register_section( $wp_customize ) {
		$wp_customize->add_section(
			self::SECTION,
			array(
				'title'    => __( 'Learn Page', 'academy' ),
				'priority' => 10,
				'panel'    => 'academylms',
			)
		);
	}

	public function dispatch_settings( $wp_customize ) {
		/**
		 * General Style
		 */
		$wp_customize->add_setting('learn_page_general_style', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'learn_page_general_style',
				array(
					'label'         => esc_html__( 'General Style', 'academy' ),
					'settings'      => 'learn_page_general_style',
					'section'       => self::SECTION,
				)
			)
		);

		// Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_background' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_background' ),
			array(
				'selector'            => '.academy-lessons',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_background' ),
				array(
					'label'    => __( 'Background Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_background' ),
				)
			)
		);

		// Heading Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_heading_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_heading_color' ),
			array(
				'selector'            => '.academy-lessons h1,.academy-lessons h2,.academy-lessons h3,.academy-lessons h4,.academy-lessons h5,.academy-lessons h6',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_heading_color' ),
				array(
					'label'    => __( 'Heading Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_heading_color' ),
				)
			)
		);
		// Paragraph Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_paragraph_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_paragraph_color' ),
			array(
				'selector'            => '.academy-lessons p',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_paragraph_color' ),
				array(
					'label'    => __( 'Text Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_paragraph_color' ),
				)
			)
		);

		// Link Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_link_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_link_color' ),
			array(
				'selector'            => '.academy-lessons a',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_link_color' ),
				array(
					'label'    => __( 'Link Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_link_color' ),
				)
			)
		);

		$this->add_top_bar_styling( $wp_customize );
		$this->add_sidebar_styling( $wp_customize );
		$this->add_sidebar_topics_styling( $wp_customize );
		$this->add_sidebar_topic_styling( $wp_customize );
		$this->add_sidebar_topic_active_styling( $wp_customize );
		$this->add_qa_form_styling( $wp_customize );
		$this->add_announcement_item_styling( $wp_customize );
	}
	public function add_top_bar_styling( $wp_customize ) {
		// Topbar Section Style
		$wp_customize->add_setting('learn_page_top_bar_style', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'learn_page_top_bar_style',
				array(
					'label'         => esc_html__( 'Top bar Style', 'academy' ),
					'settings'      => 'learn_page_top_bar_style',
					'section'       => self::SECTION,
				)
			)
		);

		// Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_top_bar_background' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_top_bar_background' ),
			array(
				'selector'            => '.academy-lessons .academy-lesson-topbar',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_top_bar_background' ),
				array(
					'label'    => __( 'Background Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_top_bar_background' ),
				)
			)
		);

		// Share Button Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_share_button_background_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_share_button_background_color' ),
			array(
				'selector'            => '.academy-lessons button.academy-btn--share',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_share_button_background_color' ),
				array(
					'label'    => __( 'Share Button Background Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_share_button_background_color' ),
				)
			)
		);

		// Share Button Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_share_button_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_share_button_text_color' ),
			array(
				'selector'            => '.academy-lessons button.academy-btn--share span.academy-btn--label ',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_share_button_text_color' ),
				array(
					'label'    => __( 'Share Button Text Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_share_button_text_color' ),
				)
			)
		);

		// Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_top_bar_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_top_bar_color' ),
			array(
				'selector'            => '.academy-lessons .academy-lesson-topbar p',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_top_bar_color' ),
				array(
					'label'    => __( 'Text Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_top_bar_color' ),
				)
			)
		);

		// Link Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_top_bar_link_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_top_bar_link_color' ),
			array(
				'selector'            => '.academy-lessons .academy-lesson-topbar a',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_top_bar_link_color' ),
				array(
					'label'    => __( 'Link Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_top_bar_link_color' ),
				)
			)
		);
	}
	public function add_sidebar_styling( $wp_customize ) {
		// Sidebar Section Style
		$wp_customize->add_setting('learn_page_sidebar_style', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'learn_page_sidebar_style',
				array(
					'label'         => esc_html__( 'Sidebar Style', 'academy' ),
					'settings'      => 'learn_page_sidebar_style',
					'section'       => self::SECTION,
				)
			)
		);

		// Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_sidebar_background' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_sidebar_background' ),
			array(
				'selector'            => '.academy-lessons .academy-lesson-sidebar-content',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_sidebar_background' ),
				array(
					'label'    => __( 'Background Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_sidebar_background' ),
				)
			)
		);

		// border Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_sidebar_border_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_sidebar_border_color' ),
			array(
				'selector'            => '.academy-lessons .academy-lesson-sidebar-content',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_sidebar_border_color' ),
				array(
					'label'    => __( 'Border Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_sidebar_border_color' ),
				)
			)
		);
		// Heading Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_sidebar_heading_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_sidebar_heading_color' ),
			array(
				'selector'            => '.academy-lessons .academy-lesson-sidebar-content',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_sidebar_heading_color' ),
				array(
					'label'    => __( 'Heading Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_sidebar_heading_color' ),
				)
			)
		);
	}
	public function add_sidebar_topics_styling( $wp_customize ) {
		// Topics Section Style
		$wp_customize->add_setting('learn_page_topics_heading_style', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'learn_page_topics_heading_style',
				array(
					'label'         => esc_html__( 'Topics Heading Style', 'academy' ),
					'settings'      => 'learn_page_topics_heading_style',
					'section'       => self::SECTION,
				)
			)
		);

		// Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_topics_heading_background' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_topics_heading_background' ),
			array(
				'selector'            => '.academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_topics_heading_background' ),
				array(
					'label'    => __( 'Background Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_topics_heading_background' ),
				)
			)
		);

		// border Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_topics_heading_border_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_topics_heading_border_color' ),
			array(
				'selector'            => '.academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-title',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_topics_heading_border_color' ),
				array(
					'label'    => __( 'Border Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_topics_heading_border_color' ),
				)
			)
		);

		// Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_topics_heading_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_topics_heading_color' ),
			array(
				'selector'            => '.academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-title',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_topics_heading_color' ),
				array(
					'label'    => __( 'Text Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_topics_heading_color' ),
				)
			)
		);

	}
	public function add_sidebar_topic_styling( $wp_customize ) {
		// Topic Section Style
		$wp_customize->add_setting('learn_page_topic_item_style', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'learn_page_topic_item_style',
				array(
					'label'         => esc_html__( 'Topic item Style', 'academy' ),
					'settings'      => 'learn_page_topic_item_style',
					'section'       => self::SECTION,
				)
			)
		);

		// Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_topic_item_background' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_topic_item_background' ),
			array(
				'selector'            => '.academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-lesson-items .academy-topics-lesson-item',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_topic_item_background' ),
				array(
					'label'    => __( 'Background Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_topic_item_background' ),
				)
			)
		);

		// border Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_topic_item_border_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_topic_item_border_color' ),
			array(
				'selector'            => '.academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-lesson-items .academy-topics-lesson-item',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_topic_item_border_color' ),
				array(
					'label'    => __( 'Border Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_topic_item_border_color' ),
				)
			)
		);

		// Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_topic_item_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_topic_item_color' ),
			array(
				'selector'            => '.academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-lesson-items .academy-topics-lesson-item a',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_topic_item_color' ),
				array(
					'label'    => __( 'Text Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_topic_item_color' ),
				)
			)
		);

		// Icon Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_topic_item_icon_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_topic_item_icon_color' ),
			array(
				'selector'            => '.academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-lesson-items .academy-topics-lesson-item a',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_topic_item_icon_color' ),
				array(
					'label'    => __( 'Icon Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_topic_item_icon_color' ),
				)
			)
		);

	}
	public function add_sidebar_topic_active_styling( $wp_customize ) {
		// Topic Section Style
		$wp_customize->add_setting('learn_page_topic_active_style', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'learn_page_topic_active_style',
				array(
					'label'         => esc_html__( 'Topic Active Style', 'academy' ),
					'settings'      => 'learn_page_topic_active_style',
					'section'       => self::SECTION,
				)
			)
		);

		// Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_topic_active_background' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_topic_active_background' ),
			array(
				'selector'            => '.academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-lesson-items .academy-topics-lesson-item',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_topic_active_background' ),
				array(
					'label'    => __( 'Background Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_topic_active_background' ),
				)
			)
		);

		// border Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_topic_active_border_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_topic_active_border_color' ),
			array(
				'selector'            => '.academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-lesson-items .academy-topics-lesson-item',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_topic_active_border_color' ),
				array(
					'label'    => __( 'Border Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_topic_active_border_color' ),
				)
			)
		);

		// Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_topic_active_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_topic_active_color' ),
			array(
				'selector'            => '.academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-lesson-items .academy-topics-lesson-item a',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_topic_active_color' ),
				array(
					'label'    => __( 'Text Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_topic_active_color' ),
				)
			)
		);

		// Icon Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_topic_active_icon_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_topic_active_icon_color' ),
			array(
				'selector'            => '.academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-lesson-items .academy-topics-lesson-item a',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_topic_active_icon_color' ),
				array(
					'label'    => __( 'Icon Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_topic_active_icon_color' ),
				)
			)
		);

	}
	public function add_qa_form_styling( $wp_customize ) {
		// Topic Section Style
		$wp_customize->add_setting('learn_page_qa_form_style', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'learn_page_qa_form_style',
				array(
					'label'         => esc_html__( 'QA Form', 'academy' ),
					'settings'      => 'learn_page_qa_form_style',
					'section'       => self::SECTION,
				)
			)
		);

		// Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_qa_form_background' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_qa_form_background' ),
			array(
				'selector'            => '.academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-lesson-items .academy-topics-lesson-item',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_qa_form_background' ),
				array(
					'label'    => __( 'Background Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_qa_form_background' ),
				)
			)
		);

		// border Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_qa_form_border_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_qa_form_border_color' ),
			array(
				'selector'            => '.academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-lesson-items .academy-topics-lesson-item',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_qa_form_border_color' ),
				array(
					'label'    => __( 'Border Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_qa_form_border_color' ),
				)
			)
		);

		// Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_qa_form_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_qa_form_color' ),
			array(
				'selector'            => '.academy-lessons .academy-lesson-browseqa-wrap .academy-question-lists .academy-qa__body .academy-qa-title',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_qa_form_color' ),
				array(
					'label'    => __( 'Text Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_qa_form_color' ),
				)
			)
		);

		// Field Background
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_qa_form_field_background' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_qa_form_field_background' ),
			array(
				'selector'            => '.academy-lessons .academy-lesson-browseqa-wrap .academy-question-form form input',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_qa_form_field_background' ),
				array(
					'label'    => __( 'Field Background', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_qa_form_field_background' ),
				)
			)
		);

		// Field Border Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_qa_form_field_border_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_qa_form_field_border_color' ),
			array(
				'selector'            => '.academy-lessons .academy-lesson-browseqa-wrap .academy-question-form form input',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_qa_form_field_border_color' ),
				array(
					'label'    => __( 'Border Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_qa_form_field_border_color' ),
				)
			)
		);

	}

	public function add_announcement_item_styling( $wp_customize ) {
		// Announcement Section Style
		$wp_customize->add_setting('learn_page_announcement_item_style', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'learn_page_announcement_item_style',
				array(
					'label'         => esc_html__( 'Announcement Item', 'academy' ),
					'settings'      => 'learn_page_announcement_item_style',
					'section'       => self::SECTION,
				)
			)
		);

		// Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_announcement_item_background' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_announcement_item_background' ),
			array(
				'selector'            => '.academy-lessons .academy-announcements-wrap .academy-announcement-item',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_announcement_item_background' ),
				array(
					'label'    => __( 'Background Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_announcement_item_background' ),
				)
			)
		);

		// border Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_announcement_item_border_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_announcement_item_border_color' ),
			array(
				'selector'            => '.academy-lessons .academy-announcements-wrap .academy-announcement-item',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_announcement_item_border_color' ),
				array(
					'label'    => __( 'Border Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_announcement_item_border_color' ),
				)
			)
		);

		// Heading Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_announcement_item_heading_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_announcement_item_heading_color' ),
			array(
				'selector'            => '.academy-lessons .academy-announcements-wrap .academy-announcement-item h3',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_announcement_item_heading_color' ),
				array(
					'label'    => __( 'Heading Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_announcement_item_heading_color' ),
				)
			)
		);
		// Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'learn_page_announcement_item_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'learn_page_announcement_item_color' ),
			array(
				'selector'            => '.academy-lessons .academy-announcements-wrap .academy-announcement-item p',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'learn_page_announcement_item_color' ),
				array(
					'label'    => __( 'Text Color', 'academy' ),
					'section'  => self::SECTION,
					'settings' => $this->get_style_settings_id( 'learn_page_announcement_item_color' ),
				)
			)
		);
	}
}
