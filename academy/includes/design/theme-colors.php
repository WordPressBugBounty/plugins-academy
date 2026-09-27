<?php
namespace Academy\Design;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Carries Academy's brand colours into the active theme: Hello Academy's colour
 * options, or a block theme's palette. Used by Colours & Style when "Also use
 * these colours in my theme" is on, and by its live preview.
 */
class ThemeColors {

	/**
	 * Ready-made colour sets for Colours & Style.
	 *
	 * @return array[]
	 */
	public static function palettes() {
		return [
			[
				'key'     => 'violet',
				'label'   => __( 'Violet', 'academy' ),
				'primary' => '#5B47E0',
				'soft'    => '#EEEBFD',
			],
			[
				'key'     => 'teal',
				'label'   => __( 'Teal', 'academy' ),
				'primary' => '#20AD96',
				'soft'    => '#EEFBF9',
			],
			[
				'key'     => 'ocean',
				'label'   => __( 'Ocean', 'academy' ),
				'primary' => '#0B72E7',
				'soft'    => '#EEF6FF',
			],
			[
				'key'     => 'emerald',
				'label'   => __( 'Emerald', 'academy' ),
				'primary' => '#059669',
				'soft'    => '#ECFDF5',
			],
			[
				'key'     => 'sunset',
				'label'   => __( 'Sunset', 'academy' ),
				'primary' => '#EA580C',
				'soft'    => '#FFF7ED',
			],
			[
				'key'     => 'rose',
				'label'   => __( 'Rose', 'academy' ),
				'primary' => '#E11D48',
				'soft'    => '#FFF1F2',
			],
			[
				'key'     => 'graphite',
				'label'   => __( 'Graphite', 'academy' ),
				'primary' => '#1F2937',
				'soft'    => '#F3F4F6',
			],
		];
	}

	/**
	 * Show other colours in the active theme, for the rest of this request.
	 *
	 * @param array $colors primary, soft, dark.
	 * @return void
	 */
	public static function preview_theme( array $colors ) {
		add_filter(
			'option_hello_academy_options',
			static function ( $options ) use ( $colors ) {
				return array_merge( (array) $options, self::hello_academy_values( $colors ) );
			}
		);
		add_filter(
			'wp_theme_json_data_user',
			static function ( $theme_json ) use ( $colors ) {
				$palette = self::block_theme_palette( $colors );
				if ( ! $palette ) {
					return $theme_json;
				}

				return $theme_json->update_with(
					[
						'version'  => 3,
						'settings' => [ 'color' => [ 'palette' => [ 'theme' => $palette ] ] ],
					]
				);
			}
		);
	}

	/**
	 * Hello Academy's colour options for a set of colours.
	 *
	 * @param array $colors primary, soft, dark.
	 * @return array
	 */
	public static function hello_academy_values( array $colors ) {
		return [
			'color_primary'      => $colors['primary'],
			'color_primary_2'    => self::shade( $colors['primary'], -0.14 ),
			'color_primary_soft' => $colors['soft'],
		];
	}

	/**
	 * The active block theme's palette with its brand colours swapped in, or
	 * null when the theme has no colour Academy knows to be its brand colour.
	 *
	 * @param array $colors primary, soft, dark.
	 * @return array|null
	 */
	public static function block_theme_palette( array $colors ) {
		$theme   = \WP_Theme_JSON_Resolver::get_theme_data()->get_settings();
		$palette = isset( $theme['color']['palette']['theme'] ) ? $theme['color']['palette']['theme'] : [];
		if ( ! $palette ) {
			return null;
		}

		/**
		 * Filters which palette colours count as the brand colours, by slug.
		 *
		 * @param array  $map    Slug => colour (hex, with alpha where the theme uses one).
		 * @param array  $colors primary, soft, dark.
		 * @param string $theme  Active theme stylesheet.
		 */
		$map = apply_filters(
			'academy/design/theme_palette_map',
			[
				'primary'      => $colors['primary'],
				'primary-2'    => self::shade( $colors['primary'], -0.14 ),
				'primary-soft' => $colors['soft'],
				'Primarysoft'  => $colors['soft'],
				'Primary30'    => $colors['primary'] . '4D',
				'contrast'     => 'academy-fse' === get_stylesheet() ? $colors['primary'] . '1A' : null,
			],
			$colors,
			get_stylesheet()
		);

		$changed = false;
		foreach ( $palette as $index => $entry ) {
			if ( isset( $entry['slug'] ) && ! empty( $map[ $entry['slug'] ] ) ) {
				$palette[ $index ]['color'] = $map[ $entry['slug'] ];
				$changed                    = true;
			}
		}

		return $changed ? $palette : null;
	}

	/**
	 * A colour made lighter (positive) or darker (negative).
	 *
	 * @param string $hex    Colour.
	 * @param float  $amount -1 to 1.
	 * @return string
	 */
	public static function shade( $hex, $amount ) {
		$hex = ltrim( $hex, '#' );
		$out = '#';
		foreach ( str_split( substr( $hex, 0, 6 ), 2 ) as $pair ) {
			$channel = hexdec( $pair );
			$channel = $amount < 0 ? $channel * ( 1 + $amount ) : $channel + ( 255 - $channel ) * $amount;
			$out    .= str_pad( dechex( (int) round( max( 0, min( 255, $channel ) ) ) ), 2, '0', STR_PAD_LEFT );
		}

		return strtoupper( $out );
	}
}
