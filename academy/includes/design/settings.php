<?php
namespace Academy\Design;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The course design: what a course card and a course page are made of, in what
 * order, and the colours and sizes they use.
 *
 * One option drives both the block templates and the classic templates, so the
 * Design screen and the Site Editor never describe two different pages.
 */
class Settings {

	/**
	 * Option name.
	 */
	const OPTION = 'academy_course_design';

	/**
	 * Parts a course card can be built from, in their default order.
	 *
	 * @return array Key => label.
	 */
	public static function card_elements() {
		return [
			'image'       => __( 'Course image', 'academy' ),
			'badge'       => __( 'Featured badge', 'academy' ),
			'categories'  => __( 'Categories', 'academy' ),
			'title'       => __( 'Title', 'academy' ),
			'excerpt'     => __( 'Excerpt', 'academy' ),
			'instructors' => __( 'Instructor', 'academy' ),
			'meta'        => __( 'Course details', 'academy' ),
			'rating'      => __( 'Rating', 'academy' ),
			'price'       => __( 'Price', 'academy' ),
			'button'      => __( 'Button', 'academy' ),
			'wishlist'    => __( 'Wishlist button', 'academy' ),
		];
	}

	/**
	 * Sections a course page can be built from, in their default order.
	 *
	 * @return array Key => label.
	 */
	public static function page_sections() {
		return [
			'media'             => __( 'Intro video or image', 'academy' ),
			'categories'        => __( 'Categories', 'academy' ),
			'title'             => __( 'Course title', 'academy' ),
			'meta'              => __( 'Instructor, rating and details', 'academy' ),
			'description'       => __( 'Description', 'academy' ),
			'benefits'          => __( 'Benefits and requirements', 'academy' ),
			'curriculum'        => __( 'Curriculum', 'academy' ),
			'attachments'       => __( 'Attachments', 'academy' ),
			'instructorProfile' => __( 'Instructor profiles', 'academy' ),
			'ratingSummary'     => __( 'Rating summary', 'academy' ),
			'reviewForm'        => __( 'Review form', 'academy' ),
			'reviews'           => __( 'Reviews', 'academy' ),
		];
	}

	/**
	 * The design a site starts with: what Academy's templates already look like.
	 *
	 * @return array
	 */
	public static function defaults() {
		$listed = function ( array $labels, array $hidden = [] ) {
			$items = [];
			foreach ( array_keys( $labels ) as $key ) {
				$items[] = [
					'key'     => $key,
					'visible' => ! in_array( $key, $hidden, true ),
				];
			}

			return $items;
		};

		// Start from what the site already chose on Settings → Course Page.
		$per_row = (array) \Academy\Helper::get_settings( 'course_archive_courses_per_row', [] );
		$columns = [];
		foreach ( [
			'desktop' => 3,
			'tablet'  => 2,
			'mobile'  => 1,
		] as $device => $fallback ) {
			$columns[ $device ] = isset( $per_row[ $device ] ) && (int) $per_row[ $device ] > 0 ? min( 6, (int) $per_row[ $device ] ) : $fallback;
		}
		$hidden = [ 'wishlist' ];
		if ( ! \Academy\Helper::get_settings( 'is_show_course_excerpt', false ) ) {
			$hidden[] = 'excerpt';
		}

		return [
			'version' => 1,
			'catalog' => [
				'columns'    => $columns,
				'imageRatio' => '16/9',
				'elements'   => $listed( self::card_elements(), $hidden ),
			],
			'single'  => [
				'sidebar'  => 'right',
				'sections' => $listed( self::page_sections() ),
			],
			'style'   => [
				'accent'       => '',
				'surface'      => '',
				'line'         => '',
				'radius'       => 12,
				'buttonRadius' => 8,
				'titleSize'    => 18,
			],
		];
	}

	/**
	 * The saved design, filled in with the defaults.
	 *
	 * @return array
	 */
	public static function get() {
		$saved = get_option( self::OPTION );

		return self::sanitize( is_array( $saved ) ? $saved : [] );
	}

	/**
	 * Save a design.
	 *
	 * @param array $design Design.
	 * @return array The saved design.
	 */
	public static function save( array $design ) {
		$design = self::sanitize( $design );
		update_option( self::OPTION, $design );

		// Classic templates and the [academy_courses] shortcode still read these
		// two from the main settings, so they follow the design.
		\Academy\Admin\Settings\Base::save_settings(
			[
				'course_archive_courses_per_row' => $design['catalog']['columns'],
				'is_show_course_excerpt'         => self::is_visible( $design['catalog']['elements'], 'excerpt' ),
			]
		);
		Palette::refresh_globals(
			[
				'course_archive_courses_per_row' => (object) $design['catalog']['columns'],
				'is_show_course_excerpt'         => self::is_visible( $design['catalog']['elements'], 'excerpt' ),
			]
		);

		/**
		 * Fires after the course design is saved.
		 *
		 * @param array $design The saved design.
		 */
		do_action( 'academy/design/saved', $design );

		return $design;
	}

	/**
	 * Fill in and clean up a design.
	 *
	 * @param array $design Design.
	 * @return array
	 */
	public static function sanitize( array $design ) {
		$defaults = self::defaults();

		$columns = isset( $design['catalog']['columns'] ) ? (array) $design['catalog']['columns'] : [];
		foreach ( [ 'desktop', 'tablet', 'mobile' ] as $device ) {
			$value              = isset( $columns[ $device ] ) ? (int) $columns[ $device ] : $defaults['catalog']['columns'][ $device ];
			$columns[ $device ] = max( 1, min( 6, $value ) );
		}

		$ratio = isset( $design['catalog']['imageRatio'] ) ? (string) $design['catalog']['imageRatio'] : '';
		if ( ! in_array( $ratio, [ '16/9', '4/3', '3/2', '1/1', 'auto' ], true ) ) {
			$ratio = $defaults['catalog']['imageRatio'];
		}

		$sidebar = isset( $design['single']['sidebar'] ) ? (string) $design['single']['sidebar'] : '';
		if ( ! in_array( $sidebar, [ 'right', 'left', 'none' ], true ) ) {
			$sidebar = $defaults['single']['sidebar'];
		}

		$style = isset( $design['style'] ) ? (array) $design['style'] : [];
		$clean = [];
		// Kept empty: colours live in Academy's palette now.
		foreach ( [ 'accent', 'surface', 'line' ] as $key ) {
			$clean[ $key ] = '';
		}
		$clean['radius']       = max( 0, min( 40, isset( $style['radius'] ) ? (int) $style['radius'] : $defaults['style']['radius'] ) );
		$clean['buttonRadius'] = max( 0, min( 40, isset( $style['buttonRadius'] ) ? (int) $style['buttonRadius'] : $defaults['style']['buttonRadius'] ) );
		$clean['titleSize']    = max( 12, min( 40, isset( $style['titleSize'] ) ? (int) $style['titleSize'] : $defaults['style']['titleSize'] ) );

		return [
			'version' => 1,
			'catalog' => [
				'columns'    => $columns,
				'imageRatio' => $ratio,
				'elements'   => self::sanitize_list( isset( $design['catalog']['elements'] ) ? $design['catalog']['elements'] : [], self::card_elements(), $defaults['catalog']['elements'] ),
			],
			'single'  => [
				'sidebar'  => $sidebar,
				'sections' => self::sanitize_list( isset( $design['single']['sections'] ) ? $design['single']['sections'] : [], self::page_sections(), $defaults['single']['sections'] ),
			],
			'style'   => $clean,
		];
	}

	/**
	 * Clean an ordered list of parts: keep known keys in the given order, then
	 * add any that are missing (a new part from an update, for one).
	 *
	 * @param mixed $items    Saved list.
	 * @param array $known    Known keys => labels.
	 * @param array $fallback Default list.
	 * @return array
	 */
	private static function sanitize_list( $items, array $known, array $fallback ) {
		if ( ! is_array( $items ) || ! $items ) {
			return $fallback;
		}

		$clean = [];
		$seen  = [];
		foreach ( $items as $item ) {
			// The key has to be one Academy knows, which is the check that
			// matters; sanitize_key() would lowercase camelCase names like
			// instructorProfile and drop them.
			$key = is_array( $item ) && isset( $item['key'] ) && is_string( $item['key'] ) ? $item['key'] : '';
			if ( ! isset( $known[ $key ] ) || isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$clean[]      = [
				'key'     => $key,
				'visible' => ! empty( $item['visible'] ),
			];
		}
		foreach ( $fallback as $item ) {
			if ( ! isset( $seen[ $item['key'] ] ) ) {
				$clean[] = $item;
			}
		}

		return $clean;
	}

	/**
	 * Whether the Design screen's course settings shape the course pages: they
	 * do on the block templates, not on the classic ones.
	 *
	 * @return array applies, canSwitch, switchUrl.
	 */
	public static function mode() {
		$blocks  = 'blocks' === \Academy\Blocks::template_style() && \Academy\Helper::use_block_templates();
		$capable = \Academy\Helper::is_fse_theme() || (bool) apply_filters( 'academy/templates/use_block_templates', false );

		return [
			'applies'   => $blocks,
			'canSwitch' => ! $blocks && $capable,
			'switchUrl' => add_query_arg( '_wpnonce', wp_create_nonce( 'academy_course_templates' ), admin_url( 'admin-post.php?action=academy_course_templates&style=blocks' ) ),
		];
	}

	/**
	 * The preset nearest to a classic card style, for sites switching to blocks.
	 *
	 * @param string $card_style Classic card style.
	 * @return string Preset key.
	 */
	public static function preset_for_card_style( $card_style ) {
		$map = [
			'default'      => 'classic',
			'layout_two'   => 'modern',
			'layout_three' => 'minimal',
			'layout_four'  => 'marketplace',
		];

		return isset( $map[ $card_style ] ) ? $map[ $card_style ] : 'classic';
	}

	/**
	 * Card styles for classic templates.
	 *
	 * @return string[]
	 */
	public static function card_styles() {
		return [ 'default', 'layout_two', 'layout_three', 'layout_four' ];
	}

	/**
	 * Display options kept in Academy's main settings, edited on Design.
	 *
	 * @return array sidebar, enrollCount, cardStyle.
	 */
	public static function display() {
		$sidebar = (string) \Academy\Helper::get_settings( 'course_archive_sidebar_position', 'right' );
		$style   = (string) \Academy\Helper::get_settings( 'course_card_style', 'default' );

		return [
			'sidebar'     => in_array( $sidebar, [ 'none', 'left', 'right' ], true ) ? $sidebar : 'right',
			'enrollCount' => (bool) \Academy\Helper::get_settings( 'is_enabled_course_single_enroll_count', true ),
			'cardStyle'   => in_array( $style, self::card_styles(), true ) ? $style : 'default',
		];
	}

	/**
	 * Clean display options.
	 *
	 * @param array $display Display options.
	 * @return array
	 */
	public static function sanitize_display( array $display ) {
		$current = self::display();
		$sidebar = isset( $display['sidebar'] ) ? (string) $display['sidebar'] : $current['sidebar'];
		$style   = isset( $display['cardStyle'] ) ? (string) $display['cardStyle'] : $current['cardStyle'];

		return [
			'sidebar'     => in_array( $sidebar, [ 'none', 'left', 'right' ], true ) ? $sidebar : $current['sidebar'],
			'enrollCount' => isset( $display['enrollCount'] ) ? rest_sanitize_boolean( $display['enrollCount'] ) : $current['enrollCount'],
			'cardStyle'   => in_array( $style, self::card_styles(), true ) ? $style : $current['cardStyle'],
		];
	}

	/**
	 * Save display options into the main settings.
	 *
	 * @param array $display Display options.
	 * @return array
	 */
	public static function save_display( array $display ) {
		$display = self::sanitize_display( $display );
		$values  = [
			'course_archive_sidebar_position'       => $display['sidebar'],
			'is_enabled_course_single_enroll_count' => $display['enrollCount'],
			'course_card_style'                     => $display['cardStyle'],
		];
		\Academy\Admin\Settings\Base::save_settings( $values );
		Palette::refresh_globals( $values );

		return $display;
	}

	/**
	 * Whether a part is switched on.
	 *
	 * @param array  $items Ordered list of parts.
	 * @param string $key   Part key.
	 * @return bool
	 */
	public static function is_visible( array $items, $key ) {
		foreach ( $items as $item ) {
			if ( $item['key'] === $key ) {
				return ! empty( $item['visible'] );
			}
		}

		return false;
	}
}
