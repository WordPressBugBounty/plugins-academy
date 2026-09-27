<?php
namespace Academy\Design;

use Academy\Admin\Settings\Base as BaseSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Academy's colours, in one place.
 *
 * The palette is the one Academy has always had — light and dark versions of
 * primary, secondary, text, gray, border and surface, kept in the main
 * settings — now edited on Customize → Colours & Style. Optionally it is also
 * handed to the theme, where the theme lets Academy set its colours.
 */
class Palette {

	/**
	 * Option: whether the palette is also applied to the theme.
	 */
	const SYNC_KEY = 'palette_sync';

	/**
	 * Option: the theme's own colours from before they were synced, to put back.
	 */
	const BACKUP_OPTION = 'academy_palette_theme_backup';

	/**
	 * Option: whether the Design screen's old colours were moved in.
	 */
	const MIGRATED_KEY = 'design_palette';

	/**
	 * Option: whether untouched colours were moved to the refreshed palette.
	 */
	const REFRESHED_KEY = 'palette_refreshed';

	/**
	 * The previous default colours, and what each one is now.
	 */
	const PREVIOUS_DEFAULTS = [
		'secondary_color'      => [ [ '#eae8fa' ], '#f2f0fd' ],
		'text_color'           => [ [ '#111' ], '#131d2b' ],
		'border_color'         => [ [ '#e5e4e6' ], '#e5e7eb' ],
		'gray_color'           => [ [ '#f6f7f9' ], '#f6f7f8' ],
		'dark_primary_color'   => [ [ '#9b8bf4', '#8b7bf2' ], '#7b68ee' ],
		'dark_secondary_color' => [ [ '#2a2740' ], '#252140' ],
		'dark_text_color'      => [ [ '#e8e6f0' ], '#e6e9ef' ],
		'dark_border_color'    => [ [ '#3a3750' ], '#333d4b' ],
		'dark_gray_color'      => [ [ '#232132' ], '#1f2632' ],
		'dark_surface_color'   => [ [ '#1b192a' ], '#161c26' ],
	];

	/**
	 * Bumped whenever the default colours change, so the refresh runs again.
	 */
	const PALETTE_VERSION = 3;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_init', [ __CLASS__, 'migrate_design_colours' ] );
		add_action( 'admin_init', [ __CLASS__, 'refresh_default_colours' ] );
	}

	/**
	 * The palette's keys, light then dark.
	 *
	 * @return string[]
	 */
	public static function keys() {
		$light = [ 'primary_color', 'secondary_color', 'text_color', 'gray_color', 'border_color', 'surface_color' ];
		$dark  = array_map(
			static function ( $key ) {
				return 'dark_' . $key;
			},
			$light
		);

		return array_merge( $light, $dark );
	}

	/**
	 * Academy's default colours.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array_intersect_key( BaseSettings::get_default_data(), array_flip( self::keys() ) );
	}

	/**
	 * The palette in use.
	 *
	 * @return array
	 */
	public static function get() {
		$saved   = BaseSettings::get_saved_data();
		$palette = [];
		foreach ( self::defaults() as $key => $default ) {
			$value           = isset( $saved[ $key ] ) ? sanitize_hex_color( (string) $saved[ $key ] ) : '';
			$palette[ $key ] = $value ? $value : $default;
		}

		return $palette;
	}

	/**
	 * Clean a palette: known keys, hex colours, defaults for anything missing.
	 *
	 * @param array $palette Palette.
	 * @return array
	 */
	public static function sanitize( array $palette ) {
		$clean = self::get();
		foreach ( self::keys() as $key ) {
			$value = isset( $palette[ $key ] ) ? sanitize_hex_color( (string) $palette[ $key ] ) : '';
			if ( $value ) {
				$clean[ $key ] = $value;
			}
		}

		return $clean;
	}

	/**
	 * Save the palette, and carry it into the theme when that is switched on.
	 *
	 * @param array $palette Palette.
	 * @return array The saved palette.
	 */
	public static function save( array $palette ) {
		$palette = self::sanitize( $palette );
		BaseSettings::save_settings( $palette );
		self::refresh_globals( $palette );

		if ( self::syncs_theme() ) {
			self::apply_to_theme( $palette );
		}

		return $palette;
	}

	/**
	 * Put palette values into the settings already loaded for this request.
	 *
	 * @param array $palette Palette.
	 * @return void
	 */
	public static function refresh_globals( array $palette ) {
		if ( ! isset( $GLOBALS['academy_settings'] ) || ! is_object( $GLOBALS['academy_settings'] ) ) {
			return;
		}
		foreach ( $palette as $key => $value ) {
			$GLOBALS['academy_settings']->{$key} = $value;
		}
	}

	/**
	 * Whether the palette is handed to the theme.
	 *
	 * @return bool
	 */
	public static function syncs_theme() {
		return (bool) \Academy\Options::get( \Academy\Options::DESIGN_STATE, self::SYNC_KEY, false );
	}

	/**
	 * Whether the active theme lets Academy set its colours.
	 *
	 * @return bool
	 */
	public static function theme_can_sync() {
		if ( 'hello-academy' === get_stylesheet() ) {
			return true;
		}

		if ( 'astra' === get_template() ) {
			return true;
		}

		return wp_is_block_theme() && null !== ThemeColors::block_theme_palette( self::brand( self::get() ) );
	}

	/**
	 * Turn theme syncing on or off. Turning it off puts the theme's own colours
	 * back.
	 *
	 * @param bool $on Whether to sync.
	 * @return void
	 */
	public static function set_theme_sync( $on ) {
		$on = (bool) $on;
		if ( self::syncs_theme() === $on ) {
			return;
		}
		if ( $on ) {
			\Academy\Options::set( \Academy\Options::DESIGN_STATE, self::SYNC_KEY, true );
			self::apply_to_theme( self::get() );
			return;
		}

		\Academy\Options::delete( \Academy\Options::DESIGN_STATE, self::SYNC_KEY );
		self::restore_theme();
	}

	/**
	 * The three colours the theme needs, from the palette.
	 *
	 * @param array $palette Palette.
	 * @return array primary, soft, dark.
	 */
	public static function brand( array $palette ) {
		return [
			'primary' => strtoupper( $palette['primary_color'] ),
			'soft'    => strtoupper( $palette['secondary_color'] ),
			'dark'    => ThemeColors::shade( $palette['primary_color'], -0.72 ),
		];
	}

	/**
	 * Hand the palette to the active theme, keeping the theme's own colours the
	 * first time so they can be put back.
	 *
	 * @param array $palette Palette.
	 * @return void
	 */
	private static function apply_to_theme( array $palette ) {
		$colors = self::brand( $palette );
		$backup = get_option( self::BACKUP_OPTION );

		if ( 'hello-academy' === get_stylesheet() ) {
			if ( ! is_array( $backup ) || get_stylesheet() !== $backup['theme'] ) {
				update_option(
					self::BACKUP_OPTION,
					[
						'theme' => get_stylesheet(),
						'kind'  => 'options',
						'value' => get_option( 'hello_academy_options', null ),
					],
					false
				);
			}
			$current = get_option( 'hello_academy_options', [] );
			update_option( 'hello_academy_options', array_merge( is_array( $current ) ? $current : [], ThemeColors::hello_academy_values( $colors ) ) );
			return;
		}

		if ( 'astra' === get_template() ) {
			if ( ! is_array( $backup ) || get_template() !== $backup['theme'] ) {
				update_option(
					self::BACKUP_OPTION,
					[
						'theme'  => get_template(),
						'kind'   => 'astra_options',
						'value'  => get_option( 'astra-settings', [] ),
					],
					false
				);
			}
			$settings = get_option( 'astra-settings', [] );
			$settings = is_array( $settings ) ? $settings : [];
			$settings['theme-color'] = $colors['primary'];
			$settings['link-color'] = $colors['primary'];
			$settings['button-bg-color'] = $colors['primary'];
			$settings['button-color'] = '#ffffff';
			update_option( 'astra-settings', $settings );
			return;
		}//end if

		if ( ! wp_is_block_theme() ) {
			return;
		}
		$theme_palette = ThemeColors::block_theme_palette( $colors );
		$post_id       = $theme_palette ? \WP_Theme_JSON_Resolver::get_user_global_styles_post_id() : 0;
		$post          = $post_id ? get_post( $post_id ) : null;
		if ( ! $post ) {
			return;
		}
		if ( ! is_array( $backup ) || get_stylesheet() !== $backup['theme'] ) {
			update_option(
				self::BACKUP_OPTION,
				[
					'theme' => get_stylesheet(),
					'kind'  => 'global_styles',
					'id'    => (int) $post->ID,
					'value' => $post->post_content,
				],
				false
			);
		}
		$data = json_decode( $post->post_content, true );
		$data = is_array( $data ) ? $data : [
			'version'                     => 3,
			'isGlobalStylesUserThemeJSON' => true,
		];
		$data['settings']['color']['palette']['theme'] = $theme_palette;
		wp_update_post(
			[
				'ID'           => (int) $post->ID,
				'post_content' => wp_slash( wp_json_encode( $data ) ),
			]
		);
		if ( function_exists( 'wp_clean_theme_json_cache' ) ) {
			wp_clean_theme_json_cache();
		}
	}

	/**
	 * Put the theme's own colours back, if they were kept for this theme.
	 *
	 * @return void
	 */
	private static function restore_theme() {
		$backup = get_option( self::BACKUP_OPTION );
		delete_option( self::BACKUP_OPTION );
		if ( ! is_array( $backup ) ) {
			return;
		}
		$active_theme = 'astra_options' === $backup['kind'] ? get_template() : get_stylesheet();
		if ( $active_theme !== $backup['theme'] ) {
			return;
		}
		if ( 'options' === $backup['kind'] ) {
			if ( null === $backup['value'] ) {
				delete_option( 'hello_academy_options' );
			} else {
				update_option( 'hello_academy_options', $backup['value'] );
			}
			return;
		}
		if ( 'astra_options' === $backup['kind'] ) {
			update_option( 'astra-settings', is_array( $backup['value'] ) ? $backup['value'] : [] );
			return;
		}
		wp_update_post(
			[
				'ID'           => (int) $backup['id'],
				'post_content' => wp_slash( $backup['value'] ),
			]
		);
		if ( function_exists( 'wp_clean_theme_json_cache' ) ) {
			wp_clean_theme_json_cache();
		}
	}

	/**
	 * The Design screen used to keep an accent, surface and border colour of
	 * its own. Move any that were set into the palette, once, so there is only
	 * one place colours live.
	 *
	 * @return void
	 */
	public static function migrate_design_colours() {
		if ( \Academy\Options::get( \Academy\Options::MIGRATIONS, self::MIGRATED_KEY ) ) {
			return;
		}
		\Academy\Options::set( \Academy\Options::MIGRATIONS, self::MIGRATED_KEY, 1 );

		$design = get_option( Settings::OPTION );
		if ( ! is_array( $design ) || empty( $design['style'] ) ) {
			return;
		}
		$map     = [
			'accent'  => 'primary_color',
			'surface' => 'surface_color',
			'line'    => 'border_color',
		];
		$palette = [];
		foreach ( $map as $from => $to ) {
			if ( ! empty( $design['style'][ $from ] ) ) {
				$palette[ $to ]            = $design['style'][ $from ];
				$design['style'][ $from ] = '';
			}
		}
		if ( $palette ) {
			self::save( $palette );
			update_option( Settings::OPTION, $design );
		}
	}

	/**
	 * Move colours still on an old default to the refreshed palette, once.
	 * Colours someone picked themselves are left as they are.
	 *
	 * @return void
	 */
	public static function refresh_default_colours() {
		if ( (int) \Academy\Options::get( \Academy\Options::MIGRATIONS, self::REFRESHED_KEY ) >= self::PALETTE_VERSION ) {
			return;
		}
		\Academy\Options::set( \Academy\Options::MIGRATIONS, self::REFRESHED_KEY, self::PALETTE_VERSION );

		$saved   = BaseSettings::get_saved_data();
		$palette = [];
		foreach ( self::PREVIOUS_DEFAULTS as $key => $colours ) {
			if ( isset( $saved[ $key ] ) && in_array( strtolower( (string) $saved[ $key ] ), $colours[0], true ) ) {
				$palette[ $key ] = $colours[1];
			}
		}
		if ( $palette ) {
			self::save( $palette );
		}
	}
}
