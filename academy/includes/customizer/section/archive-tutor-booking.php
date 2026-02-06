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

class ArchiveTutorBooking extends SectionBase implements CustomizerSectionInterface {

	public function __construct( $wp_customize ) {
		$this->register_section( $wp_customize );
		$this->dispatch_settings( $wp_customize );
	}

	public function register_section( $wp_customize ) {
		$wp_customize->add_section(
			'academy_archive_tutor_booking',
			array(
				'title'    => __( 'Booking Archive', 'academy' ),
				'priority' => 10,
				'panel'    => 'academylms',
			)
		);
	}

	public function dispatch_settings( $wp_customize ) {
		// Archive Style Heading
		$wp_customize->add_setting('archive_tutor_booking_header_options_heading', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'archive_tutor_booking_header_options_heading',
				array(
					'label'         => esc_html__( 'Header Options', 'academy' ),
					'settings'      => 'archive_tutor_booking_header_options_heading',
					'section'       => 'academy_archive_tutor_booking',
				)
			)
		);

		// Archive Header Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_header_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'archive_tutor_booking_header_bg_color' ),
			array(
				'selector'            => '.academy-Bookings .academy-Bookings__header',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'archive_tutor_booking_header_bg_color' ),
				array(
					'label'    => __( 'Background Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_header_bg_color' ),
				)
			)
		);

		// Archive Header Padding
		$archive_tutor_booking_header_pading = $wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_header_pading' ),
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
			$this->get_style_settings_id( 'archive_tutor_booking_header_pading' ),
			array(
				'label'    => __( 'Padding', 'academy' ),
				'section'  => 'academy_archive_tutor_booking',
				'settings' => array( $archive_tutor_booking_header_pading->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Archive Header Margin
		$archive_tutor_booking_header_margin = $wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_header_margin' ),
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
			$this->get_style_settings_id( 'archive_tutor_booking_header_margin' ),
			array(
				'label'    => __( 'Margin', 'academy' ),
				'section'  => 'academy_archive_tutor_booking',
				'settings' => array( $archive_tutor_booking_header_margin->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Horzontal Rule
		$wp_customize->add_setting('archive_tutor_booking_header_padding_hr', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'archive_tutor_booking_header_padding_hr', array(
					'settings'      => 'archive_tutor_booking_header_padding_hr',
					'section'       => 'academy_archive_tutor_booking',
				)
			)
		);

		// Archive Booking Count Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_header_Booking_count_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'archive_tutor_booking_header_Booking_count_color' ),
			array(
				'selector'            => '.academy-Bookings .academy-Bookings__header .academy-Bookings__header-result-count',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'archive_tutor_booking_header_Booking_count_color' ),
				array(
					'label'    => __( 'Booking Count Text Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_header_Booking_count_color' ),
				)
			)
		);

		// Horzontal Rule
		$wp_customize->add_setting('archive_tutor_booking_header_hr', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'archive_tutor_booking_header_hr', array(
					'settings'      => 'archive_tutor_booking_header_hr',
					'section'       => 'academy_archive_tutor_booking',
				)
			)
		);

		// Archive Header Sorting Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_header_sorting_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'archive_tutor_booking_header_sorting_bg_color' ),
			array(
				'selector'            => '.academy-Bookings .academy-Bookings__header .academy-Bookings__header-ordering',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'archive_tutor_booking_header_sorting_bg_color' ),
				array(
					'label'    => __( 'Sorting Background Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_header_sorting_bg_color' ),
				)
			)
		);

		// Archive Header Sorting Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_header_sorting_color' ),
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
				$this->get_style_settings_id( 'archive_tutor_booking_header_sorting_color' ),
				array(
					'label'    => __( 'Sorting Text Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_header_sorting_color' ),
				)
			)
		);

		// Archive Booking Card
		$wp_customize->add_setting('archive_tutor_booking_Booking_card_heading', array(
			'default'           => '',
		));

		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'archive_tutor_booking_Booking_card_heading',
				array(
					'label'         => esc_html__( 'Booking Card', 'academy' ),
					'settings'      => 'archive_tutor_booking_Booking_card_heading',
					'section'       => 'academy_archive_tutor_booking',
				)
			)
		);

		// Booking Card Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_card_bg_color' ),
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
				$this->get_style_settings_id( 'archive_tutor_booking_Booking_card_bg_color' ),
				array(
					'label'    => __( 'Background Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_Booking_card_bg_color' ),
				)
			)
		);

		// Booking Card Content Padding
		$archive_tutor_booking_Booking_card_content_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_card_content_padding' ),
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
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_card_content_padding' ),
			array(
				'label'    => __( 'Card Content Padding', 'academy' ),
				'section'  => 'academy_archive_tutor_booking',
				'settings' => array( $archive_tutor_booking_Booking_card_content_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// HR
		$wp_customize->add_setting('archive_tutor_booking_Booking_card_padding_hr', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'archive_tutor_booking_Booking_card_padding_hr', array(
					'settings'      => 'archive_tutor_booking_Booking_card_padding_hr',
					'section'       => 'academy_archive_tutor_booking',
				)
			)
		);

		// Booking Category Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_category_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_category_color' ),
			array(
				'selector'            => '.academy-Bookings .academy-Bookings__body .academy-Booking__meta--categroy',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'archive_tutor_booking_Booking_category_color' ),
				array(
					'label'    => __( 'Category Text Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_Booking_category_color' ),
				)
			)
		);

		// Booking Title Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_title_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_title_color' ),
			array(
				'selector'            => '.academy-Bookings .academy-Bookings__body .academy-Booking__title',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'archive_tutor_booking_Booking_title_color' ),
				array(
					'label'    => __( 'Title Text Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_Booking_title_color' ),
				)
			)
		);

		// Booking Author Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_author_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_author_color' ),
			array(
				'selector'            => '.academy-Bookings .academy-Bookings__body .academy-Booking__author',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'archive_tutor_booking_Booking_author_color' ),
				array(
					'label'    => __( 'Author Text Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_Booking_author_color' ),
				)
			)
		);

		// Horzontal Rule
		$wp_customize->add_setting('archive_tutor_booking_Booking_card_desc_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'archive_tutor_booking_Booking_card_desc_separator',
				array(
					'settings'      => 'archive_tutor_booking_Booking_card_desc_separator',
					'section'       => 'academy_archive_tutor_booking',
				)
			)
		);

		// Footer Separator Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_footer_separator_color' ),
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
				$this->get_style_settings_id( 'archive_tutor_booking_Booking_footer_separator_color' ),
				array(
					'label'    => __( 'Footer Separator Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_Booking_footer_separator_color' ),
				)
			)
		);

		// Booking Card Footer Padding
		$archive_tutor_booking_Booking_card_footer_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_card_footer_padding' ),
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
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_card_footer_padding' ),
			array(
				'label'    => __( 'Footer Content Padding', 'academy' ),
				'section'  => 'academy_archive_tutor_booking',
				'settings' => array( $archive_tutor_booking_Booking_card_footer_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Hr
		$wp_customize->add_setting('archive_tutor_booking_Booking_card_footer_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'archive_tutor_booking_Booking_card_footer_separator',
				array(
					'settings'      => 'archive_tutor_booking_Booking_card_footer_separator',
					'section'       => 'academy_archive_tutor_booking',
				)
			)
		);

		// Booking Rating Icon color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_rating_icon_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_rating_icon_color' ),
			array(
				'selector'            => '.academy-Bookings .academy-Booking__footer .academy-Booking__rating',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'archive_tutor_booking_Booking_rating_icon_color' ),
				array(
					'label'    => __( 'Rating Icon Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_Booking_rating_icon_color' ),
				)
			)
		);

		// Booking Rating color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_rating_color' ),
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
				$this->get_style_settings_id( 'archive_tutor_booking_Booking_rating_color' ),
				array(
					'label'    => __( 'Rating Text Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_Booking_rating_color' ),
				)
			)
		);

		// Booking Rating Count Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_rating_count_color' ),
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
				$this->get_style_settings_id( 'archive_tutor_booking_Booking_rating_count_color' ),
				array(
					'label'    => __( 'Rating Count Text Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_Booking_rating_count_color' ),
				)
			)
		);

		// Booking Price color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_price_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_price_color' ),
			array(
				'selector'            => '.academy-Bookings .academy-Booking__footer .academy-Booking__price',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'archive_tutor_booking_Booking_price_color' ),
				array(
					'label'    => __( 'Price Text Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_Booking_price_color' ),
				)
			)
		);

		// Normal Price Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_normal_price_text_color' ),
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
				$this->get_style_settings_id( 'archive_tutor_booking_normal_price_text_color' ),
				array(
					'label'    => __( 'Normal Price Text Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_normal_price_text_color' ),
				)
			)
		);

		// Sale Price Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_sale_price_text_color' ),
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
				$this->get_style_settings_id( 'archive_tutor_booking_sale_price_text_color' ),
				array(
					'label'    => __( 'Sale Price Text Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_sale_price_text_color' ),
				)
			)
		);

		// Archive Pagination Style
		$wp_customize->add_setting('archive_tutor_booking_pagination_option_heading', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'archive_tutor_booking_pagination_option_heading',
				array(
					'label'         => esc_html__( 'Booking Pagination', 'academy' ),
					'settings'      => 'archive_tutor_booking_pagination_option_heading',
					'section'       => 'academy_archive_tutor_booking',
				)
			)
		);

		// Archive Pagination Padding
		$archive_tutor_booking_Booking_pagination_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_pagination_padding' ),
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
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_pagination_padding' ),
			array(
				'label'    => __( 'Button Padding', 'academy' ),
				'section'  => 'academy_archive_tutor_booking',
				'settings' => array( $archive_tutor_booking_Booking_pagination_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Archive Pagination Margin
		$archive_tutor_booking_Booking_pagination_margin = $wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_pagination_margin' ),
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
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_pagination_margin' ),
			array(
				'label'    => __( 'Button Margin', 'academy' ),
				'section'  => 'academy_archive_tutor_booking',
				'settings' => array( $archive_tutor_booking_Booking_pagination_margin->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Pagination Button Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_pagination_normal_button_bg_color' ),
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
				$this->get_style_settings_id( 'archive_tutor_booking_pagination_normal_button_bg_color' ),
				array(
					'label'    => __( 'Button Background Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_pagination_normal_button_bg_color' ),
				)
			)
		);

		// Normal Pagination Button color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_pagination_normal_button_color' ),
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
				$this->get_style_settings_id( 'archive_tutor_booking_pagination_normal_button_color' ),
				array(
					'label'    => __( 'Button Text Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_pagination_normal_button_color' ),
				)
			)
		);

		// HR
		$wp_customize->add_setting('archive_tutor_booking_Booking_pagination_padding_hr', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'archive_tutor_booking_Booking_pagination_padding_hr', array(
					'settings'      => 'archive_tutor_booking_Booking_pagination_padding_hr',
					'section'       => 'academy_archive_tutor_booking',
				)
			)
		);

		// Active Pagination Button Background color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_pagination_active_button_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'archive_tutor_booking_pagination_active_button_bg_color' ),
			array(
				'selector'            => '.academy-Bookings .academy-Bookings__pagination',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'archive_tutor_booking_pagination_active_button_bg_color' ),
				array(
					'label'    => __( 'Active Button Background Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_pagination_active_button_bg_color' ),
				)
			)
		);

		// Active Pagination color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_pagination_active_button_color' ),
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
				$this->get_style_settings_id( 'archive_tutor_booking_pagination_active_button_color' ),
				array(
					'label'    => __( 'Active Button Text Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_pagination_active_button_color' ),
				)
			)
		);

		// Hr
		$wp_customize->add_setting('archive_tutor_booking_Booking_active__pagi_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'archive_tutor_booking_Booking_active__pagi_separator',
				array(
					'settings'      => 'archive_tutor_booking_Booking_active__pagi_separator',
					'section'       => 'academy_archive_tutor_booking',
				)
			)
		);

		// Next/Prev Pagination Button Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_next_prev_pagination_button_bg_color' ),
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
				$this->get_style_settings_id( 'archive_tutor_booking_next_prev_pagination_button_bg_color' ),
				array(
					'label'    => __( 'Next/Prev Button Background Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_next_prev_pagination_button_bg_color' ),
				)
			)
		);

		// Next/Prev Pagination Button Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_next_prev_pagination_button_text_color' ),
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
				$this->get_style_settings_id( 'archive_tutor_booking_next_prev_pagination_button_text_color' ),
				array(
					'label'    => __( 'Next/Prev Button Text Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_next_prev_pagination_button_text_color' ),
				)
			)
		);

		// Archive Sidebar Style
		$wp_customize->add_setting('archive_tutor_booking_sidebar_options_heading', array(
			'default'           => '',
		));
		$wp_customize->add_control(
			new Separator(
				$wp_customize,
				'archive_tutor_booking_sidebar_options_heading',
				array(
					'label'         => esc_html__( 'Booking Sidebar', 'academy' ),
					'settings'      => 'archive_tutor_booking_sidebar_options_heading',
					'section'       => 'academy_archive_tutor_booking',
				)
			)
		);

		// Filter Section BG color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_sidebar_bg_color' ),
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			$this->get_style_settings_id( 'archive_tutor_booking_sidebar_bg_color' ),
			array(
				'selector'            => '.academy-Bookings .academy-Bookings__sidebar',
				'container_inclusive' => true,
				'render_callback'     => '__return_true',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				$this->get_style_settings_id( 'archive_tutor_booking_sidebar_bg_color' ),
				array(
					'label'    => __( 'Background Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_sidebar_bg_color' ),
				)
			)
		);

		// Hr
		$wp_customize->add_setting('archive_tutor_booking_Booking_sidebar_bg_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'archive_tutor_booking_Booking_sidebar_bg_separator',
				array(
					'settings'      => 'archive_tutor_booking_Booking_sidebar_bg_separator',
					'section'       => 'academy_archive_tutor_booking',
				)
			)
		);

		// Archive Sidebar Padding
		$archive_tutor_booking_Booking_sidebar_padding = $wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_sidebar_padding' ),
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
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_sidebar_padding' ),
			array(
				'label'    => __( 'Sidebar Padding', 'academy' ),
				'section'  => 'academy_archive_tutor_booking',
				'settings' => array( $archive_tutor_booking_Booking_sidebar_padding->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// HR
		$wp_customize->add_setting('archive_tutor_booking_Booking_sidebar_padding_hr', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'archive_tutor_booking_Booking_sidebar_padding_hr', array(
					'settings'      => 'archive_tutor_booking_Booking_sidebar_padding_hr',
					'section'       => 'academy_archive_tutor_booking',
				)
			)
		);

		// HR
		$wp_customize->add_setting('archive_tutor_booking_Booking_sidebar_filter_padding_hr', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'archive_tutor_booking_Booking_sidebar_filter_padding_hr', array(
					'settings'      => 'archive_tutor_booking_Booking_sidebar_filter_padding_hr',
					'section'       => 'academy_archive_tutor_booking',
				)
			)
		);

		// Sidebar SearchBox Background Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_sidebar_searchbox_bg_color' ),
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
				$this->get_style_settings_id( 'archive_tutor_booking_sidebar_searchbox_bg_color' ),
				array(
					'label'    => __( 'Searchbox Background Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_sidebar_searchbox_bg_color' ),
				)
			)
		);

		// SearchBox Placeholder Text Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_sidebar_searchbox_placeholder_text_color' ),
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
				$this->get_style_settings_id( 'archive_tutor_booking_sidebar_searchbox_placeholder_text_color' ),
				array(
					'label'    => __( 'SearchBox Placeholder Text Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_sidebar_searchbox_placeholder_text_color' ),
				)
			)
		);

		// Filter Searchbox Text color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_sidebar_searchbox_text_color' ),
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
				$this->get_style_settings_id( 'archive_tutor_booking_sidebar_searchbox_text_color' ),
				array(
					'label'    => __( 'SearchBox Text Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_sidebar_searchbox_text_color' ),
				)
			)
		);

		// Hr
		$wp_customize->add_setting('archive_tutor_booking_Booking_sidebar_search_separator', array(
			'default'           => '',
			'sanitize_callback' => 'esc_html',
		));

		$wp_customize->add_control(
			new HorizontalRule(
				$wp_customize,
				'archive_tutor_booking_Booking_sidebar_search_separator',
				array(
					'settings'      => 'archive_tutor_booking_Booking_sidebar_search_separator',
					'section'       => 'academy_archive_tutor_booking',
				)
			)
		);

		// Archive Sidebar Single Filter Margin
		$archive_tutor_booking_Booking_sidebar_filter_margin = $wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_sidebar_filter_margin' ),
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
			$this->get_style_settings_id( 'archive_tutor_booking_Booking_sidebar_filter_margin' ),
			array(
				'label'    => __( 'Filter Item Margin', 'academy' ),
				'section'  => 'academy_archive_tutor_booking',
				'settings' => array( $archive_tutor_booking_Booking_sidebar_filter_margin->id ),
				'type'     => 'academy_dimensions',
			)
		);

		// Filter Heading color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_sidebar_filter_heading_color' ),
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
				$this->get_style_settings_id( 'archive_tutor_booking_sidebar_filter_heading_color' ),
				array(
					'label'    => __( 'Filter Heading Text Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_sidebar_filter_heading_color' ),
				)
			)
		);

		// Filter Checkbox Bacground Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_sidebar_filter_checkbox_bg_color' ),
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
				$this->get_style_settings_id( 'archive_tutor_booking_sidebar_filter_checkbox_bg_color' ),
				array(
					'label'    => __( 'Filter Checkbox Background Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_sidebar_filter_checkbox_bg_color' ),
				)
			)
		);

		// Filter Checkbox Border Color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_sidebar_filter_checkbox_border_color' ),
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
				$this->get_style_settings_id( 'archive_tutor_booking_sidebar_filter_checkbox_border_color' ),
				array(
					'label'    => __( 'Filter Checkbox Border Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_sidebar_filter_checkbox_border_color' ),
				)
			)
		);

		// Filter Item color
		$wp_customize->add_setting(
			$this->get_style_settings_id( 'archive_tutor_booking_sidebar_filter_item_color' ),
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
				$this->get_style_settings_id( 'archive_tutor_booking_sidebar_filter_item_color' ),
				array(
					'label'    => __( 'Filter Item Text Color', 'academy' ),
					'section'  => 'academy_archive_tutor_booking',
					'settings' => $this->get_style_settings_id( 'archive_tutor_booking_sidebar_filter_item_color' ),
				)
			)
		);

	}

}
