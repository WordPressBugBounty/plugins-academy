<?php
namespace Academy\Customizer\Section;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Customizer\Control\HorizontalRule;
use Academy\Customizer\Control\Separator;
use Academy\Customizer\Control\Tab;
use Academy\Customizer\SectionBase;
use Academy\Interfaces\CustomizerSectionInterface;

class SingleCourse extends SectionBase implements CustomizerSectionInterface {

	public function __construct( $wp_customize ) {
		$this->register_section( $wp_customize );
		$this->dispatch_settings( $wp_customize );
	}

	public function register_section( $wp_customize ) {
		$wp_customize->add_section(
			'academy_single_course',
			array(
				'title'    => __( 'Course Single', 'academy' ),
				'priority' => 10,
				'panel'    => 'academylms',
			)
		);
	}

	public function dispatch_settings( $wp_customize ) {

		// Single Course General Options
		$wp_customize->add_setting('single_course_general_option_heading', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'single_course_general_option_heading',
				array(
					'label'         => esc_html__( 'General Options', 'academy' ),
					'settings'      => 'single_course_general_option_heading',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Single Course Wrapper BG Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_wrapper_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'single_course_wrapper_bg_color' ),
			array(
				'selector'            => '.academy-single-course .academy-container',
				'container_inclusive' => false,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_wrapper_bg_color' ),
				array(
					'label'    => __( 'Course Wrapper Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_wrapper_bg_color' ),
				)
			)
		);

		// Single Course Wrapper Padding
		$single_course_wrapper_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_wrapper_padding' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_wrapper_padding' ),
			array(
				'label'    => __( 'Course Wrapper Padding', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_wrapper_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Single Course Wrapper Margin
		$single_course_wrapper_margin = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_wrapper_margin' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_wrapper_margin' ),
			array(
				'label'    => __( 'Course Wrapper Margin', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_wrapper_margin->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Hr
		$wp_customize->add_setting('single_course_wrapper_margin_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'single_course_wrapper_margin_separator',
				array(
					'settings'      => 'single_course_wrapper_margin_separator',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Course Category Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_category_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'single_course_category_text_color' ),
			array(
				'selector'            => '.academy-single-course .academy-single-course__categroy',
				'container_inclusive' => false,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_category_text_color' ),
				array(
					'label'    => __( 'Category Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_category_text_color' ),
				)
			)
		);

		// Course Title Text color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_title_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'single_course_title_color' ),
			array(
				'selector'            => '.academy-single-course .academy-single-course__title',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_title_color' ),
				array(
					'label'    => __( 'Course Title Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_title_color' ),
				)
			)
		);

		// Single Course Instructor Area
		$wp_customize->add_setting('single_course_instructor_option_heading', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'single_course_instructor_option_heading',
				array(
					'label'         => esc_html__( 'Instructor Area', 'academy' ),
					'settings'      => 'single_course_instructor_option_heading',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Course Instructor Title color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_instructor_title_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'single_course_instructor_title_color' ),
			array(
				'selector'            => '.academy-single-course__content-item--instructors .course-single-instructor',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_instructor_title_color' ),
				array(
					'label'    => __( 'Instructor Title Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_instructor_title_color' ),
				)
			)
		);

		// Course Instructor Name color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_instructor_name_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_instructor_name_color' ),
				array(
					'label'    => __( 'Instructor Name Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_instructor_name_color' ),
				)
			)
		);

		// Hr
		$wp_customize->add_setting('single_course_instructor_name_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'single_course_instructor_name_separator',
				array(
					'settings'      => 'single_course_instructor_name_separator',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Course Review Title color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_review_title_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'single_course_review_title_color' ),
			array(
				'selector'            => '.academy-single-course .course-single-instructor .instructor-review',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_review_title_color' ),
				array(
					'label'    => __( 'Review Title Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_review_title_color' ),
				)
			)
		);

		// Course Rating Icon color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_rating_icon_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_rating_icon_color' ),
				array(
					'label'    => __( 'Rating Icon Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_rating_icon_color' ),
				)
			)
		);

		// Course Rating color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_rating_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_rating_text_color' ),
				array(
					'label'    => __( 'Rating Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_rating_text_color' ),
				)
			)
		);

		// Course Instructor Padding
		$single_course_instructor_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_instructor_padding' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_instructor_padding' ),
			array(
				'label'    => __( 'Instructor Padding', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_instructor_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Single Course Content Style
		$wp_customize->add_setting('single_course__content_option_heading', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'single_course__content_style_heading',
				array(
					'label'         => esc_html__( 'Course Content', 'academy' ),
					'settings'      => 'single_course__content_option_heading',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Course Description Heading Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_description_heading_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'single_course_description_heading_text_color' ),
			array(
				'selector'            => '.academy-single-course .academy-single-course__content-item--description .academy-single-course__content-item--description-title',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_description_heading_text_color' ),
				array(
					'label'    => __( 'Description Heading Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_description_heading_text_color' ),
				)
			)
		);

		// Course Description color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_description_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_description_text_color' ),
				array(
					'label'    => __( 'Description Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_description_text_color' ),
				)
			)
		);

		// Single Course Benefits Style
		$wp_customize->add_setting('single_course_benefits_option_heading', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'single_course_benefits_option_heading',
				array(
					'label'         => esc_html__( 'Benefits', 'academy' ),
					'settings'      => 'single_course_benefits_option_heading',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Course Benefits Heading Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_benefits_heading_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'single_course_benefits_heading_text_color' ),
			array(
				'selector'            => '.academy-single-course .academy-single-course__content-item--benefits',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_benefits_heading_text_color' ),
				array(
					'label'    => __( 'Benefits Heading Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_benefits_heading_text_color' ),
				)
			)
		);

		// Course Benefits Description Icon Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_benefits_description_icon_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_benefits_description_icon_color' ),
				array(
					'label'    => __( 'Description Icon Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_benefits_description_icon_color' ),
				)
			)
		);

		// Course Benefits Description Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_benefits_description_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_benefits_description_text_color' ),
				array(
					'label'    => __( 'Description Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_benefits_description_text_color' ),
				)
			)
		);

		// Single Course Additional Info Style
		$wp_customize->add_setting('single_course_additional_info_option_heading', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'single_course_additional_info_option_heading',
				array(
					'label'         => esc_html__( 'Additional Info', 'academy' ),
					'settings'      => 'single_course_additional_info_option_heading',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Course Additional Info Tab Heading Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_additional_info_tab_heading_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'single_course_additional_info_tab_heading_text_color' ),
			array(
				'selector'            => '.academy-single-course .academy-single-course__content-item--additional-info',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_additional_info_tab_heading_text_color' ),
				array(
					'label'    => __( 'Tab Heading Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_additional_info_tab_heading_text_color' ),
				)
			)
		);

		// Course Additional Info Tab Heading Active Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_additional_info_tab_heading_active_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_additional_info_tab_heading_active_text_color' ),
				array(
					'label'    => __( 'Tab Heading Active Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_additional_info_tab_heading_active_text_color' ),
				)
			)
		);

		// Course Additional Info Tab Heading Active Border Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_additional_info_tab_heading_active_border_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_additional_info_tab_heading_active_border_color' ),
				array(
					'label'    => __( 'Tab Heading Active Border Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_additional_info_tab_heading_active_border_color' ),
				)
			)
		);

		// Course Additional Info Description Icon Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_additional_info_description_icon_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_additional_info_description_icon_color' ),
				array(
					'label'    => __( 'Description Icon Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_additional_info_description_icon_color' ),
				)
			)
		);

		// Course Additional Info Description Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_additional_info_description_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_additional_info_description_text_color' ),
				array(
					'label'    => __( 'Description Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_additional_info_description_text_color' ),
				)
			)
		);

		// Single Course Topic Style
		$wp_customize->add_setting('single_course_topic_option_heading', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'single_course__topic_style_heading',
				array(
					'label'         => esc_html__( 'Curriculam/Topic', 'academy' ),
					'settings'      => 'single_course_topic_option_heading',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Course Topic Heading Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_topic_heading_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'single_course_topic_heading_text_color' ),
			array(
				'selector'            => '.academy-single-course .academy-single-course__content-item--curriculum .academy-curriculum-title',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_topic_heading_text_color' ),
				array(
					'label'    => __( 'Heading Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_topic_heading_text_color' ),
				)
			)
		);

		// Single Course Topic Heading Margin
		$single_course_topic_heading_margin = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_topic_heading_margin' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_topic_heading_margin' ),
			array(
				'label'    => __( 'Heading Text Margin', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_topic_heading_margin->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Hr
		$wp_customize->add_setting('single_course_topic_header_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'single_course_topic_header_separator',
				array(
					'settings'      => 'single_course_topic_header_separator',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Course Topic Title Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_topic_title_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_topic_title_bg_color' ),
				array(
					'label'    => __( 'Accordian Title Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_topic_title_bg_color' ),
				)
			)
		);

		// Topic Accordian Title Padding
		$single_course_topic_title_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_topic_title_padding' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_topic_title_padding' ),
			array(
				'label'    => __( 'Accordian Title Padding', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_topic_title_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Course Topic Title Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_topic_title_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_topic_title_text_color' ),
				array(
					'label'    => __( 'Accordian Title Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_topic_title_text_color' ),
				)
			)
		);

		// Course Topic Title Icon Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_topic_title_icon_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_topic_title_icon_color' ),
				array(
					'label'    => __( 'Accordian Title Icon Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_topic_title_icon_color' ),
				)
			)
		);

		// Hr
		$wp_customize->add_setting('single_course_topic_title_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'single_course_topic_title_separator',
				array(
					'settings'      => 'single_course_topic_title_separator',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Course Topic Content Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_topic_content_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_topic_content_bg_color' ),
				array(
					'label'    => __( 'Accordian Content Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_topic_content_bg_color' ),
				)
			)
		);

		// Topic Content Padding
		$single_course_topic_content_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_topic_content_padding' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_topic_content_padding' ),
			array(
				'label'    => __( 'Accordian Content Padding', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_topic_content_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Course Topic Accordian Content Thumbnail Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_topic_content_thumbnail_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_topic_content_thumbnail_color' ),
				array(
					'label'    => __( 'Accordian Content Thumbnail Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_topic_content_thumbnail_color' ),
				)
			)
		);

		// Course Topic Description Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_topic_content_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_topic_content_text_color' ),
				array(
					'label'    => __( 'Accordian Content Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_topic_content_text_color' ),
				)
			)
		);

		// Course Topic Content Icon Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_topic_content_icon_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_topic_content_icon_color' ),
				array(
					'label'    => __( 'Accordian Content Icon Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_topic_content_icon_color' ),
				)
			)
		);

		// Course Topic Content Separator Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_topic_content_separator_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_topic_content_separator_color' ),
				array(
					'label'    => __( 'Accordian Content Separator Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_topic_content_separator_color' ),
				)
			)
		);

		// Single Course Feedback Style
		$wp_customize->add_setting('single_course_feedback_option_heading', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'single_course_feedback_option_heading',
				array(
					'label'         => esc_html__( 'Feedback', 'academy' ),
					'settings'      => 'single_course_feedback_option_heading',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Course Feedback Heading Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_feedback_heading_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'single_course_feedback_heading_color' ),
			array(
				'selector'            => '.academy-single-course .academy-single-course__content-item--feedback .feedback-title',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_feedback_heading_color' ),
				array(
					'label'    => __( 'Heading Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_feedback_heading_color' ),
				)
			)
		);

		// Single Course Feedback Heading Margin
		$single_course_feedback_heading_margin = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_feedback_heading_margin' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 20, 0, 20, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_feedback_heading_margin' ),
			array(
				'label'    => __( 'Heading Margin', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_feedback_heading_margin->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Hr
		$wp_customize->add_setting('single_course_feedback_title_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'single_course_feedback_title_separator',
				array(
					'settings'      => 'single_course_feedback_title_separator',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Course Feedback Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_feedback_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'single_course_feedback_bg_color' ),
			array(
				'selector'            => '.academy-single-course .academy-single-course__content-item--feedback .academy-student-course-feedback-ratings',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_feedback_bg_color' ),
				array(
					'label'    => __( 'Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_feedback_bg_color' ),
				)
			)
		);

		// Course Feedback Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_feedback_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_feedback_text_color' ),
				array(
					'label'    => __( 'Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_feedback_text_color' ),
				)
			)
		);

		// Single Course Feedback Padding
		$single_course_feedback_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_feedback_padding' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 40, 0, 40, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_feedback_padding' ),
			array(
				'label'    => __( 'Feedback Padding', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_feedback_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		$wp_customize->add_setting('single_course_feedback_bg_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'single_course_feedback_bg_separator',
				array(
					'settings'      => 'single_course_feedback_bg_separator',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Average Rating Number Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_avg_rating_number_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_avg_rating_number_color' ),
				array(
					'label'    => __( 'Average Rating Number Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_avg_rating_number_color' ),
				)
			)
		);

		// Feedback Rating Icon Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_feedback_rating_icon_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_feedback_rating_icon_color' ),
				array(
					'label'    => __( 'Rating Icon Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_feedback_rating_icon_color' ),
				)
			)
		);

		// Hr
		$wp_customize->add_setting('single_course_feedback_avg_rating_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'single_course_feedback_avg_rating_separator',
				array(
					'settings'      => 'single_course_feedback_avg_rating_separator',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Feedback Rating Progress bar Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_feedback_rating_progressbar_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_feedback_rating_progressbar_bg_color' ),
				array(
					'label'    => __( 'Progressbar Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_feedback_rating_progressbar_bg_color' ),
				)
			)
		);

		// Feedback Rating Progress bar Fill Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_feedback_rating_progressbar_fill_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_feedback_rating_progressbar_fill_color' ),
				array(
					'label'    => __( 'Progressbar Fill Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_feedback_rating_progressbar_fill_color' ),
				)
			)
		);

		// Feedback Rating Percentage Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_feedback_rating_percentage_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_feedback_rating_percentage_color' ),
				array(
					'label'    => __( 'Rating Percentage Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_feedback_rating_percentage_color' ),
				)
			)
		);

		// Course Review Style
		$wp_customize->add_setting('single_course_review_option_heading', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'single_course_review_option_heading',
				array(
					'label'         => esc_html__( 'Review', 'academy' ),
					'settings'      => 'single_course_review_option_heading',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Course Reviews Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_review_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'single_course_review_bg_color' ),
			array(
				'selector'            => '.academy-single-course .academy-single-course__content-item--reviews',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_review_bg_color' ),
				array(
					'label'    => __( 'Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_review_bg_color' ),
				)
			)
		);

		// Single Course Review Padding
		$single_course_review_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_review_padding' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 40, 0, 40, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_review_padding' ),
			array(
				'label'    => __( 'Padding', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_review_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Single Course Review Margin
		$single_course_review_margin = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_review_margin' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 20, 0, 20, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_review_margin' ),
			array(
				'label'    => __( 'Margin', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_review_margin->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Course Reviews Rating Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_review_rating_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_review_rating_color' ),
				array(
					'label'    => __( 'Rating Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_review_rating_color' ),
				)
			)
		);

		// Course Reviews Rating Icon Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_review_rating_icon_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_review_rating_icon_color' ),
				array(
					'label'    => __( 'Rating Icon Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_review_rating_icon_color' ),
				)
			)
		);

		// Course Reviews Author Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_review_author_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_review_author_color' ),
				array(
					'label'    => __( 'Author Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_review_author_color' ),
				)
			)
		);

		// Course Reviews Date Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_review_date_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_review_date_color' ),
				array(
					'label'    => __( 'Date Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_review_date_color' ),
				)
			)
		);

		// Course Reviews Description Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_review_description_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_review_description_color' ),
				array(
					'label'    => __( 'Description Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_review_description_color' ),
				)
			)
		);

		// Course Review Form Style
		$wp_customize->add_setting('single_course_review_form_option_heading', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'single_course_review_form_option_heading',
				array(
					'label'         => esc_html__( 'Review Form', 'academy' ),
					'settings'      => 'single_course_review_form_option_heading',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Course Review Form Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_review_form_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_review_form_bg_color' ),
				array(
					'label'    => __( 'Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_review_form_bg_color' ),
				)
			)
		);

		// Hr
		$wp_customize->add_setting('single_course_review_form_bg_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'single_course_review_form_bg_separator',
				array(
					'settings'      => 'single_course_review_form_bg_separator',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Single Course Review Form Padding
		$single_course_review_form_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_review_form_padding' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_review_form_padding' ),
			array(
				'label'    => __( 'Padding', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_review_form_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Single Course Review Form Margin
		$single_course_review_form_margin = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_review_form_margin' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_review_form_margin' ),
			array(
				'label'    => __( 'Margin', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_review_form_margin->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Hr
		$wp_customize->add_setting('single_course_review_form_padding_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'single_course_review_form_padding_separator',
				array(
					'settings'      => 'single_course_review_form_padding_separator',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Add Review Button Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_add_review_button_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_add_review_button_bg_color' ),
				array(
					'label'    => __( 'Add Review Button Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_add_review_button_bg_color' ),
				)
			)
		);

		// Add Review Button Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_add_review_button_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_add_review_button_text_color' ),
				array(
					'label'    => __( 'Add Review Button Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_add_review_button_text_color' ),
				)
			)
		);

		// Add Review Button Hover Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_add_review_button_hover_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_add_review_button_hover_bg_color' ),
				array(
					'label'    => __( 'Add Review Button Hover Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_add_review_button_hover_bg_color' ),
				)
			)
		);

		// Add Review Button Hover Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_add_review_button_hover_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_add_review_button_hover_text_color' ),
				array(
					'label'    => __( 'Add Review Button Hover Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_add_review_button_hover_text_color' ),
				)
			)
		);

		// Add Review Button Padding
		$single_course_add_review_button_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_add_review_button_padding' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_add_review_button_padding' ),
			array(
				'label'    => __( 'Add Review Button Padding', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_add_review_button_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Hr
		$wp_customize->add_setting('single_course_review_form_add_review_button_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'single_course_review_form_add_review_button_separator',
				array(
					'settings'      => 'single_course_review_form_add_review_button_separator',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Review Form Icon Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_add_review_form_icon_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_add_review_form_icon_color' ),
				array(
					'label'    => __( 'Icon Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_add_review_form_icon_color' ),
				)
			)
		);

		// Review Form Input Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_add_review_form_input_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_add_review_form_input_text_color' ),
				array(
					'label'    => __( 'Input Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_add_review_form_input_text_color' ),
				)
			)
		);

		// Review Form Input Placeholder Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_add_review_form_input_placeholder_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_add_review_form_input_placeholder_color' ),
				array(
					'label'    => __( 'Input Placeholder Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_add_review_form_input_placeholder_color' ),
				)
			)
		);

		// Hr
		$wp_customize->add_setting('single_course_review_form_input_text_button_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'single_course_review_form_input_text_button_separator',
				array(
					'settings'      => 'single_course_review_form_input_text_button_separator',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Submit Button Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_add_review_submit_button_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_add_review_submit_button_bg_color' ),
				array(
					'label'    => __( 'Submit Button Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_add_review_submit_button_bg_color' ),
				)
			)
		);

		// Submit Button Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_add_review_submit_button_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_add_review_submit_button_text_color' ),
				array(
					'label'    => __( 'Submit Button Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_add_review_submit_button_text_color' ),
				)
			)
		);

		// Submit Button Hover Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_add_review_submit_button_hover_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_add_review_submit_button_hover_bg_color' ),
				array(
					'label'    => __( 'Submit Button Hover Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_add_review_submit_button_hover_bg_color' ),
				)
			)
		);

		// Submit Button Hover Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_add_review_submit_button_hover_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_add_review_submit_button_hover_text_color' ),
				array(
					'label'    => __( 'Submit Button Hover Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_add_review_submit_button_hover_text_color' ),
				)
			)
		);

		// Submit Button Padding
		$single_course_add_review_submit_button_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_add_review_submit_button_padding' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_add_review_submit_button_padding' ),
			array(
				'label'    => __( 'Submit Button Padding', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_add_review_submit_button_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Single Course Enroll Widget Style
		$wp_customize->add_setting('single_course_enroll_widget_option_heading', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'single_course_enroll_widget_option_heading',
				array(
					'label'         => esc_html__( 'Enroll Widget', 'academy' ),
					'settings'      => 'single_course_enroll_widget_option_heading',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Enroll Widget Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'single_course_enroll_widget_bg_color' ),
			array(
				'selector'            => '.academy-single-course .academy-widget-enroll',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_bg_color' ),
				array(
					'label'    => __( 'Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_bg_color' ),
				)
			)
		);

		// Enroll Widget Padding
		$single_course_enroll_widget_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_padding' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_enroll_widget_padding' ),
			array(
				'label'    => __( 'Padding', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_enroll_widget_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Hr
		$wp_customize->add_setting('single_course_sidebar_padding_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'single_course_sidebar_padding_separator',
				array(
					'settings'      => 'single_course_sidebar_padding_separator',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Enroll Widget Heading Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_heading_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_heading_text_color' ),
				array(
					'label'    => __( 'Heading Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_heading_text_color' ),
				)
			)
		);

		// Enroll Widget Heading Margin
		$single_course_enroll_widget_heading_margin = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_heading_margin' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_enroll_widget_heading_margin' ),
			array(
				'label'    => __( 'Heading Margin', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_enroll_widget_heading_margin->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Hr
		$wp_customize->add_setting('single_course_enroll_widget_heading_text_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'single_course_enroll_widget_heading_text_separator',
				array(
					'settings'      => 'single_course_enroll_widget_heading_text_separator',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Enroll Widget Normal Price Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_normal_price_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_normal_price_text_color' ),
				array(
					'label'    => __( 'Normal Price Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_normal_price_text_color' ),
				)
			)
		);

		// Enroll Widget Sale Price Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_sale_price_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_sale_price_text_color' ),
				array(
					'label'    => __( 'Sale Price Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_sale_price_text_color' ),
				)
			)
		);

		// Hr
		$wp_customize->add_setting('single_course_enroll_widget_heading_price_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'single_course_enroll_widget_heading_price_separator',
				array(
					'settings'      => 'single_course_enroll_widget_heading_price_separator',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Enroll Widget Header Separator Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_header_separator_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_header_separator_color' ),
				array(
					'label'    => __( 'Heading Separator Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_header_separator_color' ),
				)
			)
		);

		// Hr
		$wp_customize->add_setting('single_course_enroll_widget_heading_separator_hr', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'single_course_enroll_widget_heading_separator_hr',
				array(
					'settings'      => 'single_course_enroll_widget_heading_separator_hr',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Enroll Widget Content Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_content_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_content_text_color' ),
				array(
					'label'    => __( 'Content Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_content_text_color' ),
				)
			)
		);

		// Enroll Widget Content Icon Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_content_icon_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_content_icon_color' ),
				array(
					'label'    => __( 'Content Icon Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_content_icon_color' ),
				)
			)
		);

		// Enroll Widget Content Item Margin
		$single_course_enroll_widget_content_item_margin = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_content_item_margin' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_enroll_widget_content_item_margin' ),
			array(
				'label'    => __( 'Content Item Margin', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_enroll_widget_content_item_margin->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Enroll Information Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_course_enroll_content_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_course_enroll_content_bg_color' ),
				array(
					'label'    => __( 'Enroll Info Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_course_enroll_content_bg_color' ),
				)
			)
		);

		// Enroll Information text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_course_enroll_content_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_course_enroll_content_text_color' ),
				array(
					'label'    => __( 'Enroll Info Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_course_enroll_content_text_color' ),
				)
			)
		);

		// Enroll Information Padding
		$single_course_enroll_widget_course_enroll_content_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_course_enroll_content_padding' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_enroll_widget_course_enroll_content_padding' ),
			array(
				'label'    => __( 'Enroll Info Padding', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_enroll_widget_course_enroll_content_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Enroll Information Margin
		$single_course_enroll_widget_course_enroll_content_margin = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_course_enroll_content_margin' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_enroll_widget_course_enroll_content_margin' ),
			array(
				'label'    => __( 'Enroll Info Margin', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_enroll_widget_course_enroll_content_margin->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Start Course Button Separator
		$wp_customize->add_setting('single_course_start_course_button_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'single_course_start_course_button_separator',
				array(
					'settings'      => 'single_course_start_course_button_separator',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Start Course Button Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_start_course_button_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_start_course_button_bg_color' ),
				array(
					'label'    => __( 'Start Course Button Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_start_course_button_bg_color' ),
				)
			)
		);

		// Start Course Button Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_start_course_button_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_start_course_button_text_color' ),
				array(
					'label'    => __( 'Start Course Button Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_start_course_button_text_color' ),
				)
			)
		);

		// Start Course Button Hover Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_start_course_button_hover_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_start_course_button_hover_bg_color' ),
				array(
					'label'    => __( 'Start Course Button Hover Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_start_course_button_hover_bg_color' ),
				)
			)
		);

		// Start Course Button Hover Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_start_course_button_hover_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_start_course_button_hover_text_color' ),
				array(
					'label'    => __( 'Start Course Button Hover Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_start_course_button_hover_text_color' ),
				)
			)
		);

		// Start Course Button Padding
		$single_course_enroll_widget_start_course_button_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_start_course_button_padding' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_enroll_widget_start_course_button_padding' ),
			array(
				'label'    => __( 'Start Course Button Padding', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_enroll_widget_start_course_button_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Start Course Button Margin
		$single_course_enroll_widget_start_course_button_margin = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_start_course_button_margin' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_enroll_widget_start_course_button_margin' ),
			array(
				'label'    => __( 'Start Course Button Margin', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_enroll_widget_start_course_button_margin->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Complete Course Button Separator
		$wp_customize->add_setting('single_course_enroll_widget_complete_course_button_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'single_course_enroll_widget_complete_course_button_separator',
				array(
					'settings'      => 'single_course_enroll_widget_complete_course_button_separator',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Complete Course Button Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_complete_course_button_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_complete_course_button_bg_color' ),
				array(
					'label'    => __( 'Complete Course Button Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_complete_course_button_bg_color' ),
				)
			)
		);

		// Complete Course Button Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_complete_course_button_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_complete_course_button_text_color' ),
				array(
					'label'    => __( 'Complete Course Button Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_complete_course_button_text_color' ),
				)
			)
		);

		// Complete Course Button Hover Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_complete_course_button_hover_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_complete_course_button_hover_bg_color' ),
				array(
					'label'    => __( 'Complete Course Button Hover Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_complete_course_button_hover_bg_color' ),
				)
			)
		);

		// Complete Course Button Hover Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_complete_course_button_hover_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_complete_course_button_hover_text_color' ),
				array(
					'label'    => __( 'Complete Course Button Hover Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_complete_course_button_hover_text_color' ),
				)
			)
		);

		// Complete Course Button Padding
		$single_course_enroll_widget_complete_course_button_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_complete_course_button_padding' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_enroll_widget_complete_course_button_padding' ),
			array(
				'label'    => __( 'Complete Course Button Padding', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_enroll_widget_complete_course_button_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Complete Course Button Margin
		$single_course_enroll_widget_complete_course_button_margin = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_complete_course_button_margin' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_enroll_widget_complete_course_button_margin' ),
			array(
				'label'    => __( 'Complete Course Button Margin', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_enroll_widget_complete_course_button_margin->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Cart Button Separator
		$wp_customize->add_setting('single_course_add_to_cart_button_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'single_course_add_to_cart_button_separator',
				array(
					'settings'      => 'single_course_add_to_cart_button_separator',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Cart Button Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_cart_button_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_cart_button_bg_color' ),
				array(
					'label'    => __( 'Cart Button Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_cart_button_bg_color' ),
				)
			)
		);

		// Cart Button Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_cart_button_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_cart_button_text_color' ),
				array(
					'label'    => __( 'Cart Button Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_cart_button_text_color' ),
				)
			)
		);

		// Cart Button Hover Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_cart_button_hover_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_cart_button_hover_bg_color' ),
				array(
					'label'    => __( 'Cart Button Hover Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_cart_button_hover_bg_color' ),
				)
			)
		);

		// Cart Button Hover Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_cart_button_hover_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_cart_button_hover_text_color' ),
				array(
					'label'    => __( 'Cart Button Hover Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_cart_button_hover_text_color' ),
				)
			)
		);

		// Cart Button Padding
		$single_course_enroll_widget_cart_button_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_cart_button_padding' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_enroll_widget_cart_button_padding' ),
			array(
				'label'    => __( 'Cart Button Padding', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_enroll_widget_cart_button_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Cart Button Margin
		$single_course_enroll_widget_cart_button_margin = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_cart_button_margin' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_enroll_widget_cart_button_margin' ),
			array(
				'label'    => __( 'Cart Button Margin', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_enroll_widget_cart_button_margin->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Enroll Now Button Separator
		$wp_customize->add_setting('single_course_enroll_widget_enroll_now_button_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'single_course_enroll_widget_enroll_now_button_separator',
				array(
					'settings'      => 'single_course_enroll_widget_enroll_now_button_separator',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Enroll Now Button Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_enroll_now_button_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_enroll_now_button_bg_color' ),
				array(
					'label'    => __( 'Enroll Now Button Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_enroll_now_button_bg_color' ),
				)
			)
		);

		// Enroll Now Button Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_enroll_now_button_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_enroll_now_button_text_color' ),
				array(
					'label'    => __( 'Enroll Now Button Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_enroll_now_button_text_color' ),
				)
			)
		);

		// Enroll Now Button Hover Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_enroll_now_button_hover_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_enroll_now_button_hover_bg_color' ),
				array(
					'label'    => __( 'Enroll Now Button Hover Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_enroll_now_button_hover_bg_color' ),
				)
			)
		);

		// Enroll Now Button Hover Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_enroll_now_button_hover_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_enroll_now_button_hover_text_color' ),
				array(
					'label'    => __( 'Enroll Now Button Hover Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_enroll_now_button_hover_text_color' ),
				)
			)
		);

		// Enroll Now Button Padding
		$single_course_enroll_widget_enroll_now_button_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_enroll_now_button_padding' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_enroll_widget_enroll_now_button_padding' ),
			array(
				'label'    => __( 'Enroll Now Button Padding', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_enroll_widget_enroll_now_button_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Enroll Now Button Margin
		$single_course_enroll_widget_enroll_now_button_margin = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_enroll_now_button_margin' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_enroll_widget_enroll_now_button_margin' ),
			array(
				'label'    => __( 'Enroll Now Button Margin', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_enroll_widget_enroll_now_button_margin->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// WishList Button Separator
		$wp_customize->add_setting('single_course_enroll_widget_wishlist_button_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'single_course_enroll_widget_wishlist_button_separator',
				array(
					'settings'      => 'single_course_enroll_widget_wishlist_button_separator',
					'section'       => 'academy_single_course',
				)
			)
		);

		// WishList Button Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_wishlist_button_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_wishlist_button_bg_color' ),
				array(
					'label'    => __( 'WishList Button Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_wishlist_button_bg_color' ),
				)
			)
		);

		// WishList Button Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_wishlist_button_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_wishlist_button_text_color' ),
				array(
					'label'    => __( 'WishList Button Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_wishlist_button_text_color' ),
				)
			)
		);

		// WishList Button Hover Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_wishlist_button_hover_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_wishlist_button_hover_bg_color' ),
				array(
					'label'    => __( 'WishList Button Hover Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_wishlist_button_hover_bg_color' ),
				)
			)
		);

		// WishList Button Hover Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_wishlist_button_hover_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_wishlist_button_hover_text_color' ),
				array(
					'label'    => __( 'WishList Button Hover Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_wishlist_button_hover_text_color' ),
				)
			)
		);

		// Wishlist Button Padding
		$single_course_enroll_widget_wishlist_button_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_wishlist_button_padding' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_enroll_widget_wishlist_button_padding' ),
			array(
				'label'    => __( 'Wishlist Button Padding', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_enroll_widget_wishlist_button_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Wishlist Button Margin
		$single_course_enroll_widget_wishlist_button_margin = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_wishlist_button_margin' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_enroll_widget_wishlist_button_margin' ),
			array(
				'label'    => __( 'Wishlist Button Margin', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_enroll_widget_wishlist_button_margin->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Share Button Separator
		$wp_customize->add_setting('single_course_enroll_widget_share_button_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'single_course_enroll_widget_share_button_separator',
				array(
					'settings'      => 'single_course_enroll_widget_share_button_separator',
					'section'       => 'academy_single_course',
				)
			)
		);

		// Share Button Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_share_button_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_share_button_bg_color' ),
				array(
					'label'    => __( 'Share Button Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_share_button_bg_color' ),
				)
			)
		);

		// Share Button Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_share_button_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_share_button_text_color' ),
				array(
					'label'    => __( 'Share Button Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_share_button_text_color' ),
				)
			)
		);

		// Share Button Hover Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_share_button_hover_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_share_button_hover_bg_color' ),
				array(
					'label'    => __( 'Share Button Hover Background Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_share_button_hover_bg_color' ),
				)
			)
		);

		// Share Button Hover Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_share_button_hover_text_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'single_course_enroll_widget_share_button_hover_text_color' ),
				array(
					'label'    => __( 'Share Button Hover Text Color', 'academy' ),
					'section'  => 'academy_single_course',
					'settings' => $this->get_style_settings_id( 'single_course_enroll_widget_share_button_hover_text_color' ),
				)
			)
		);

		// Share Button Padding
		$single_course_enroll_widget_share_button_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_share_button_padding' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_enroll_widget_share_button_padding' ),
			array(
				'label'    => __( 'Share Button Padding', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_enroll_widget_share_button_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Share Button Margin
		$single_course_enroll_widget_share_button_margin = $wp_customize->add_setting(
			$this->get_style_settings_id( 'single_course_enroll_widget_share_button_margin' ),
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => array(
					'desktop'   => [ 0, 0, 0, 0 ],
					'tablet'    => [ 0, 0, 0, 0 ],
					'mobile'    => [ 0, 0, 0, 0 ],
					'unit'      => 'px',
					'isLinked'  => true,
				),
				'sanitize_callback' => '\Academy\Customizer\Sanitize::dimensions',
			)
		);

		$wp_customize->add_control(
			$this->get_style_settings_id( 'single_course_enroll_widget_share_button_margin' ),
			array(
				'label'    => __( 'Share Button Margin', 'academy' ),
				'section'  => 'academy_single_course',
				'settings' => array( $single_course_enroll_widget_share_button_margin->id ),
				'type'     => 'academy_dimensions',
			)
		);

	}


}
