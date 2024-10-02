<?php
namespace Academy\Customizer\Section;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Customizer\Control\Separator;
use Academy\Customizer\Control\HorizontalRule;
use Academy\Customizer\SectionBase;
use Academy\Interfaces\CustomizerSectionInterface;
use Academy\Customizer\Control\Tab;

class ArchiveCourse extends SectionBase implements CustomizerSectionInterface {

	public function __construct( $wp_customize ) {
		$this->register_section( $wp_customize );
		$this->dispatch_settings( $wp_customize );
	}

	public function register_section( $wp_customize ) {
		$wp_customize->add_section(
			'academy_archive_course',
			array(
				'title'    => __( 'Course Archive', 'academy' ),
				'priority' => 10,
				'panel'    => 'academylms',
			)
		);
	}

	public function dispatch_settings( $wp_customize ) {
		// Archive Style Heading
		$wp_customize->add_setting('course_archive_header_options_heading', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'course_archive_header_options_heading',
				array(
					'label'         => esc_html__( 'Header Options', 'academy' ),
					'settings'      => 'course_archive_header_options_heading',
					'section'       => 'academy_archive_course',
				)
			)
		);

		// Archive Header Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_header_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'course_archive_header_bg_color' ),
			array(
				'selector'            => '.academy-courses .academy-courses__header',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'course_archive_header_bg_color' ),
				array(
					'label'    => __( 'Background Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_header_bg_color' ),
				)
			)
		);

		// Archive Header Padding
		$course_archive_header_pading = $wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_header_pading' ),
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
			$this->get_style_settings_id( 'course_archive_header_pading' ),
			array(
				'label'    => __( 'Padding', 'academy' ),
				'section'  => 'academy_archive_course',
				'settings' => array( $course_archive_header_pading->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Archive Header Margin
		$course_archive_header_margin = $wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_header_margin' ),
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
			$this->get_style_settings_id( 'course_archive_header_margin' ),
			array(
				'label'    => __( 'Margin', 'academy' ),
				'section'  => 'academy_archive_course',
				'settings' => array( $course_archive_header_margin->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Horzontal Rule
		$wp_customize->add_setting('course_archive_header_padding_hr', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'course_archive_header_padding_hr', array(
					'settings'      => 'course_archive_header_padding_hr',
					'section'       => 'academy_archive_course',
				)
			)
		);

		// Archive Course Count Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_header_course_count_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'course_archive_header_course_count_color' ),
			array(
				'selector'            => '.academy-courses .academy-courses__header .academy-courses__header-result-count',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'course_archive_header_course_count_color' ),
				array(
					'label'    => __( 'Course Count Text Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_header_course_count_color' ),
				)
			)
		);

		// Horzontal Rule
		$wp_customize->add_setting('course_archive_header_hr', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'course_archive_header_hr', array(
					'settings'      => 'course_archive_header_hr',
					'section'       => 'academy_archive_course',
				)
			)
		);

		// Archive Header Sorting Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_header_sorting_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'course_archive_header_sorting_bg_color' ),
			array(
				'selector'            => '.academy-courses .academy-courses__header .academy-courses__header-ordering',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'course_archive_header_sorting_bg_color' ),
				array(
					'label'    => __( 'Sorting Background Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_header_sorting_bg_color' ),
				)
			)
		);

		// Archive Header Sorting Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_header_sorting_color' ),
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
				$this->get_style_settings_id( 'course_archive_header_sorting_color' ),
				array(
					'label'    => __( 'Sorting Text Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_header_sorting_color' ),
				)
			)
		);

		// Archive Course Card
		$wp_customize->add_setting('course_archive_course_card_heading', array(
			'default'           => '',
		));

		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'course_archive_course_card_heading',
				array(
					'label'         => esc_html__( 'Course Card', 'academy' ),
					'settings'      => 'course_archive_course_card_heading',
					'section'       => 'academy_archive_course',
				)
			)
		);

		// Course Card Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_course_card_bg_color' ),
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
				$this->get_style_settings_id( 'course_archive_course_card_bg_color' ),
				array(
					'label'    => __( 'Background Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_course_card_bg_color' ),
				)
			)
		);

		// Course Card Content Padding
		$course_archive_course_card_content_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_course_card_content_padding' ),
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
			$this->get_style_settings_id( 'course_archive_course_card_content_padding' ),
			array(
				'label'    => __( 'Card Content Padding', 'academy' ),
				'section'  => 'academy_archive_course',
				'settings' => array( $course_archive_course_card_content_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// HR
		$wp_customize->add_setting('course_archive_course_card_padding_hr', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'course_archive_course_card_padding_hr', array(
					'settings'      => 'course_archive_course_card_padding_hr',
					'section'       => 'academy_archive_course',
				)
			)
		);

		// Wishlist Background color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_course_wishlist_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'course_archive_course_wishlist_bg_color' ),
			array(
				'selector'            => '.academy-courses .academy-course .academy-course-header-meta .academy-add-to-wishlist-btn',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'course_archive_course_wishlist_bg_color' ),
				array(
					'label'    => __( 'Wishlist Background Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_course_wishlist_bg_color' ),
				)
			)
		);

		// Wishlist Icon Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_course_wishlist_icon_color' ),
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
				$this->get_style_settings_id( 'course_archive_course_wishlist_icon_color' ),
				array(
					'label'    => __( 'Wishlist Icon Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_course_wishlist_icon_color' ),
				)
			)
		);

		// Wishlist Icon Padding
		$course_archive_course_wishlist_icon_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_course_wishlist_icon_padding' ),
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
			$this->get_style_settings_id( 'course_archive_course_wishlist_icon_padding' ),
			array(
				'label'    => __( 'Wishlist Icon Padding', 'academy' ),
				'section'  => 'academy_archive_course',
				'settings' => array( $course_archive_course_wishlist_icon_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// HR
		$wp_customize->add_setting('course_archive_course_card_wishlist_hr', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'course_archive_course_card_wishlist_hr', array(
					'settings'      => 'course_archive_course_card_wishlist_hr',
					'section'       => 'academy_archive_course',
				)
			)
		);

		// Course Category Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_course_category_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'course_archive_course_category_color' ),
			array(
				'selector'            => '.academy-courses .academy-courses__body .academy-course__meta--categroy',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'course_archive_course_category_color' ),
				array(
					'label'    => __( 'Category Text Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_course_category_color' ),
				)
			)
		);

		// Course Title Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_course_title_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'course_archive_course_title_color' ),
			array(
				'selector'            => '.academy-courses .academy-courses__body .academy-course__title',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'course_archive_course_title_color' ),
				array(
					'label'    => __( 'Title Text Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_course_title_color' ),
				)
			)
		);

		// Course Author Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_course_author_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'course_archive_course_author_color' ),
			array(
				'selector'            => '.academy-courses .academy-courses__body .academy-course__author',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'course_archive_course_author_color' ),
				array(
					'label'    => __( 'Author Text Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_course_author_color' ),
				)
			)
		);

		// Horzontal Rule
		$wp_customize->add_setting('course_archive_course_card_desc_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'course_archive_course_card_desc_separator',
				array(
					'settings'      => 'course_archive_course_card_desc_separator',
					'section'       => 'academy_archive_course',
				)
			)
		);

		// Footer Separator Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_course_footer_separator_color' ),
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
				$this->get_style_settings_id( 'course_archive_course_footer_separator_color' ),
				array(
					'label'    => __( 'Footer Separator Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_course_footer_separator_color' ),
				)
			)
		);

		// Course Card Footer Padding
		$course_archive_course_card_footer_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_course_card_footer_padding' ),
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
			$this->get_style_settings_id( 'course_archive_course_card_footer_padding' ),
			array(
				'label'    => __( 'Footer Content Padding', 'academy' ),
				'section'  => 'academy_archive_course',
				'settings' => array( $course_archive_course_card_footer_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Hr
		$wp_customize->add_setting('course_archive_course_card_footer_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'course_archive_course_card_footer_separator',
				array(
					'settings'      => 'course_archive_course_card_footer_separator',
					'section'       => 'academy_archive_course',
				)
			)
		);

		// Course Rating Icon color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_course_rating_icon_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'course_archive_course_rating_icon_color' ),
			array(
				'selector'            => '.academy-courses .academy-course__footer .academy-course__rating',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'course_archive_course_rating_icon_color' ),
				array(
					'label'    => __( 'Rating Icon Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_course_rating_icon_color' ),
				)
			)
		);

		// Course Rating color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_course_rating_color' ),
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
				$this->get_style_settings_id( 'course_archive_course_rating_color' ),
				array(
					'label'    => __( 'Rating Text Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_course_rating_color' ),
				)
			)
		);

		// Course Rating Count Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_course_rating_count_color' ),
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
				$this->get_style_settings_id( 'course_archive_course_rating_count_color' ),
				array(
					'label'    => __( 'Rating Count Text Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_course_rating_count_color' ),
				)
			)
		);

		// Course Price color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_course_price_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'course_archive_course_price_color' ),
			array(
				'selector'            => '.academy-courses .academy-course__footer .academy-course__price',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'course_archive_course_price_color' ),
				array(
					'label'    => __( 'Price Text Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_course_price_color' ),
				)
			)
		);

		// Normal Price Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_normal_price_text_color' ),
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
				$this->get_style_settings_id( 'course_archive_normal_price_text_color' ),
				array(
					'label'    => __( 'Normal Price Text Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_normal_price_text_color' ),
				)
			)
		);

		// Sale Price Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_sale_price_text_color' ),
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
				$this->get_style_settings_id( 'course_archive_sale_price_text_color' ),
				array(
					'label'    => __( 'Sale Price Text Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_sale_price_text_color' ),
				)
			)
		);

		// Archive Pagination Style
		$wp_customize->add_setting('course_archive_pagination_option_heading', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'course_archive_pagination_option_heading',
				array(
					'label'         => esc_html__( 'Course Pagination', 'academy' ),
					'settings'      => 'course_archive_pagination_option_heading',
					'section'       => 'academy_archive_course',
				)
			)
		);

		// Archive Pagination Padding
		$course_archive_course_pagination_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_course_pagination_padding' ),
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
			$this->get_style_settings_id( 'course_archive_course_pagination_padding' ),
			array(
				'label'    => __( 'Button Padding', 'academy' ),
				'section'  => 'academy_archive_course',
				'settings' => array( $course_archive_course_pagination_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Archive Pagination Margin
		$course_archive_course_pagination_margin = $wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_course_pagination_margin' ),
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
			$this->get_style_settings_id( 'course_archive_course_pagination_margin' ),
			array(
				'label'    => __( 'Button Margin', 'academy' ),
				'section'  => 'academy_archive_course',
				'settings' => array( $course_archive_course_pagination_margin->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Pagination Button Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_pagination_normal_button_bg_color' ),
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
				$this->get_style_settings_id( 'course_archive_pagination_normal_button_bg_color' ),
				array(
					'label'    => __( 'Button Background Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_pagination_normal_button_bg_color' ),
				)
			)
		);

		// Normal Pagination Button color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_pagination_normal_button_color' ),
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
				$this->get_style_settings_id( 'course_archive_pagination_normal_button_color' ),
				array(
					'label'    => __( 'Button Text Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_pagination_normal_button_color' ),
				)
			)
		);

		// HR
		$wp_customize->add_setting('course_archive_course_pagination_padding_hr', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'course_archive_course_pagination_padding_hr', array(
					'settings'      => 'course_archive_course_pagination_padding_hr',
					'section'       => 'academy_archive_course',
				)
			)
		);

		// Active Pagination Button Background color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_pagination_active_button_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'course_archive_pagination_active_button_bg_color' ),
			array(
				'selector'            => '.academy-courses .academy-courses__pagination',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'course_archive_pagination_active_button_bg_color' ),
				array(
					'label'    => __( 'Active Button Background Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_pagination_active_button_bg_color' ),
				)
			)
		);

		// Active Pagination color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_pagination_active_button_color' ),
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
				$this->get_style_settings_id( 'course_archive_pagination_active_button_color' ),
				array(
					'label'    => __( 'Active Button Text Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_pagination_active_button_color' ),
				)
			)
		);

		// Hr
		$wp_customize->add_setting('course_archive_course_active__pagi_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'course_archive_course_active__pagi_separator',
				array(
					'settings'      => 'course_archive_course_active__pagi_separator',
					'section'       => 'academy_archive_course',
				)
			)
		);

		// Next/Prev Pagination Button Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_next_prev_pagination_button_bg_color' ),
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
				$this->get_style_settings_id( 'course_archive_next_prev_pagination_button_bg_color' ),
				array(
					'label'    => __( 'Next/Prev Button Background Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_next_prev_pagination_button_bg_color' ),
				)
			)
		);

		// Next/Prev Pagination Button Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_next_prev_pagination_button_text_color' ),
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
				$this->get_style_settings_id( 'course_archive_next_prev_pagination_button_text_color' ),
				array(
					'label'    => __( 'Next/Prev Button Text Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_next_prev_pagination_button_text_color' ),
				)
			)
		);

		// Archive Sidebar Style
		$wp_customize->add_setting('course_archive_sidebar_options_heading', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'course_archive_sidebar_options_heading',
				array(
					'label'         => esc_html__( 'Course Sidebar', 'academy' ),
					'settings'      => 'course_archive_sidebar_options_heading',
					'section'       => 'academy_archive_course',
				)
			)
		);

		// Filter Section BG color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_sidebar_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'course_archive_sidebar_bg_color' ),
			array(
				'selector'            => '.academy-courses .academy-courses__sidebar',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'course_archive_sidebar_bg_color' ),
				array(
					'label'    => __( 'Background Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_sidebar_bg_color' ),
				)
			)
		);

		// Hr
		$wp_customize->add_setting('course_archive_course_sidebar_bg_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'course_archive_course_sidebar_bg_separator',
				array(
					'settings'      => 'course_archive_course_sidebar_bg_separator',
					'section'       => 'academy_archive_course',
				)
			)
		);

		// Archive Sidebar Padding
		$course_archive_course_sidebar_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_course_sidebar_padding' ),
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
			$this->get_style_settings_id( 'course_archive_course_sidebar_padding' ),
			array(
				'label'    => __( 'Sidebar Padding', 'academy' ),
				'section'  => 'academy_archive_course',
				'settings' => array( $course_archive_course_sidebar_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// HR
		$wp_customize->add_setting('course_archive_course_sidebar_padding_hr', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'course_archive_course_sidebar_padding_hr', array(
					'settings'      => 'course_archive_course_sidebar_padding_hr',
					'section'       => 'academy_archive_course',
				)
			)
		);

		// HR
		$wp_customize->add_setting('course_archive_course_sidebar_filter_padding_hr', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'course_archive_course_sidebar_filter_padding_hr', array(
					'settings'      => 'course_archive_course_sidebar_filter_padding_hr',
					'section'       => 'academy_archive_course',
				)
			)
		);

		// Sidebar SearchBox Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_sidebar_searchbox_bg_color' ),
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
				$this->get_style_settings_id( 'course_archive_sidebar_searchbox_bg_color' ),
				array(
					'label'    => __( 'Searchbox Background Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_sidebar_searchbox_bg_color' ),
				)
			)
		);

		// SearchBox Placeholder Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_sidebar_searchbox_placeholder_text_color' ),
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
				$this->get_style_settings_id( 'course_archive_sidebar_searchbox_placeholder_text_color' ),
				array(
					'label'    => __( 'SearchBox Placeholder Text Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_sidebar_searchbox_placeholder_text_color' ),
				)
			)
		);

		// Filter Searchbox Text color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_sidebar_searchbox_text_color' ),
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
				$this->get_style_settings_id( 'course_archive_sidebar_searchbox_text_color' ),
				array(
					'label'    => __( 'SearchBox Text Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_sidebar_searchbox_text_color' ),
				)
			)
		);

		// Hr
		$wp_customize->add_setting('course_archive_course_sidebar_search_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'course_archive_course_sidebar_search_separator',
				array(
					'settings'      => 'course_archive_course_sidebar_search_separator',
					'section'       => 'academy_archive_course',
				)
			)
		);

		// Archive Sidebar Single Filter Margin
		$course_archive_course_sidebar_filter_margin = $wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_course_sidebar_filter_margin' ),
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
			$this->get_style_settings_id( 'course_archive_course_sidebar_filter_margin' ),
			array(
				'label'    => __( 'Filter Item Margin', 'academy' ),
				'section'  => 'academy_archive_course',
				'settings' => array( $course_archive_course_sidebar_filter_margin->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Filter Heading color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_sidebar_filter_heading_color' ),
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
				$this->get_style_settings_id( 'course_archive_sidebar_filter_heading_color' ),
				array(
					'label'    => __( 'Filter Heading Text Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_sidebar_filter_heading_color' ),
				)
			)
		);

		// Filter Checkbox Bacground Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_sidebar_filter_checkbox_bg_color' ),
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
				$this->get_style_settings_id( 'course_archive_sidebar_filter_checkbox_bg_color' ),
				array(
					'label'    => __( 'Filter Checkbox Background Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_sidebar_filter_checkbox_bg_color' ),
				)
			)
		);

		// Filter Checkbox Border Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_sidebar_filter_checkbox_border_color' ),
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
				$this->get_style_settings_id( 'course_archive_sidebar_filter_checkbox_border_color' ),
				array(
					'label'    => __( 'Filter Checkbox Border Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_sidebar_filter_checkbox_border_color' ),
				)
			)
		);

		// Filter Item color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'course_archive_sidebar_filter_item_color' ),
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
				$this->get_style_settings_id( 'course_archive_sidebar_filter_item_color' ),
				array(
					'label'    => __( 'Filter Item Text Color', 'academy' ),
					'section'  => 'academy_archive_course',
					'settings' => $this->get_style_settings_id( 'course_archive_sidebar_filter_item_color' ),
				)
			)
		);

	}

}
