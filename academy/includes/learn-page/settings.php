<?php
namespace Academy\LearnPage;

use Academy\Admin\Settings\Base as BaseSettings;
use Academy\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Learn page options, kept in the main Academy settings and edited on
 * Customize → Learn Page.
 */
class Settings {

	/**
	 * Option key in the Academy settings for each option.
	 */
	const KEYS = [
		'engine'        => 'learn_page_engine',
		'sidebar'       => 'learn_page_sidebar_position',
		'sidebarOpen'   => 'learn_page_sidebar_default_open',
		'sidebarWidth'  => 'learn_page_sidebar_width',
		'width'         => 'learn_page_layout_width',
		'theme'         => 'learn_page_theme_mode',
		'contentHeader' => 'learn_page_show_content_header',
		'markComplete'  => 'learn_page_show_mark_complete',
		'learnerTheme'  => 'learn_page_learner_theme_toggle',
		'learnerWidth'  => 'learn_page_learner_width_toggle',
		'topbarItems'   => 'learn_page_topbar_items',
		'menuItems'     => 'learn_page_menu_items',
		'themeChrome'   => 'is_enabled_lessons_theme_header_footer',
		'colors'        => 'learn_page_colors',
	];

	/**
	 * Colours that can be set for the learn page, in light and dark mode. Empty
	 * follows the brand colours.
	 */
	const COLORS = [ 'topbar', 'sidebar', 'content', 'footer', 'current' ];

	const TOPBAR_ITEMS = [ 'progress', 'review', 'notes', 'announcements', 'qa' ];

	const MENU_ITEMS = [ 'favorite', 'share', 'exit' ];

	/**
	 * Defaults.
	 *
	 * @return array
	 */
	public static function defaults() {
		return [
			'engine'        => 'react',
			'sidebar'       => 'left',
			'sidebarOpen'   => true,
			'sidebarWidth'  => 340,
			'width'         => 'standard',
			'theme'         => 'light',
			'contentHeader' => true,
			'markComplete'  => true,
			'learnerTheme'  => true,
			'learnerWidth'  => true,
			'topbarItems'   => self::items( [], self::TOPBAR_ITEMS ),
			'menuItems'     => self::items( [], self::MENU_ITEMS ),
			'themeChrome'   => false,
			'colors'        => self::colors( [] ),
		];
	}

	/**
	 * Clean learn page colours.
	 *
	 * @param array $colors Colours as [ light => [...], dark => [...] ].
	 * @return array
	 */
	public static function colors( array $colors ) {
		$clean = [];
		foreach ( [ 'light', 'dark' ] as $mode ) {
			$values = isset( $colors[ $mode ] ) ? (array) $colors[ $mode ] : [];
			foreach ( self::COLORS as $key ) {
				$value                  = isset( $values[ $key ] ) ? sanitize_hex_color( (string) $values[ $key ] ) : '';
				$clean[ $mode ][ $key ] = $value ? $value : '';
			}
		}

		return $clean;
	}

	/**
	 * The options in use.
	 *
	 * @return array
	 */
	public static function get() {
		$raw = [];
		foreach ( self::KEYS as $key => $option ) {
			$value = Helper::get_settings( $option, null );
			if ( null !== $value ) {
				$raw[ $key ] = is_object( $value ) ? json_decode( wp_json_encode( $value ), true ) : $value;
			}
		}

		return self::sanitize( $raw, self::defaults() );
	}

	/**
	 * Clean options, falling back to `$base` for anything missing or invalid.
	 *
	 * @param array      $values Options.
	 * @param array|null $base   Values to fall back to. The options in use by default.
	 * @return array
	 */
	public static function sanitize( array $values, $base = null ) {
		$base  = is_array( $base ) ? $base : self::get();
		$pick  = static function ( $key, array $allowed ) use ( $values, $base ) {
			return isset( $values[ $key ] ) && in_array( (string) $values[ $key ], $allowed, true ) ? (string) $values[ $key ] : $base[ $key ];
		};
		$flag  = static function ( $key ) use ( $values, $base ) {
			return isset( $values[ $key ] ) ? rest_sanitize_boolean( $values[ $key ] ) : (bool) $base[ $key ];
		};
		$width = isset( $values['sidebarWidth'] ) ? absint( $values['sidebarWidth'] ) : (int) $base['sidebarWidth'];

		return [
			'engine'        => $pick( 'engine', [ 'react', 'blocks' ] ),
			'sidebar'       => $pick( 'sidebar', [ 'left', 'right' ] ),
			'sidebarOpen'   => $flag( 'sidebarOpen' ),
			'sidebarWidth'  => min( 520, max( 260, $width ) ),
			'width'         => $pick( 'width', [ 'standard', 'wide', 'focused' ] ),
			'theme'         => $pick( 'theme', [ 'light', 'dark', 'system' ] ),
			'contentHeader' => $flag( 'contentHeader' ),
			'markComplete'  => $flag( 'markComplete' ),
			'learnerTheme'  => $flag( 'learnerTheme' ),
			'learnerWidth'  => $flag( 'learnerWidth' ),
			'topbarItems'   => isset( $values['topbarItems'] ) && is_array( $values['topbarItems'] ) ? self::items( $values['topbarItems'], self::TOPBAR_ITEMS ) : $base['topbarItems'],
			'menuItems'     => isset( $values['menuItems'] ) && is_array( $values['menuItems'] ) ? self::items( $values['menuItems'], self::MENU_ITEMS ) : $base['menuItems'],
			'themeChrome'   => $flag( 'themeChrome' ),
			'colors'        => isset( $values['colors'] ) && is_array( $values['colors'] ) ? self::colors( $values['colors'] ) : $base['colors'],
		];
	}

	/**
	 * An ordered list of switchable items: known keys only, each once, with any
	 * missing ones added at the end, switched on.
	 *
	 * @param array    $items Items as [ key, enabled ].
	 * @param string[] $known Known keys.
	 * @return array
	 */
	public static function items( array $items, array $known ) {
		$clean = [];
		foreach ( $items as $item ) {
			$item = (array) $item;
			$key  = isset( $item['key'] ) ? (string) $item['key'] : '';
			if ( in_array( $key, $known, true ) && ! isset( $clean[ $key ] ) ) {
				$clean[ $key ] = [
					'key'     => $key,
					'enabled' => isset( $item['enabled'] ) ? rest_sanitize_boolean( $item['enabled'] ) : true,
				];
			}
		}
		foreach ( $known as $key ) {
			if ( ! isset( $clean[ $key ] ) ) {
				$clean[ $key ] = [
					'key'     => $key,
					'enabled' => true,
				];
			}
		}

		return array_values( $clean );
	}

	/**
	 * Save options into the main settings.
	 *
	 * @param array $values Options.
	 * @return array The saved options.
	 */
	public static function save( array $values ) {
		$clean = self::sanitize( $values );
		BaseSettings::save_settings( self::to_settings( $clean ) );
		\Academy\Design\Palette::refresh_globals( self::to_settings( $clean ) );
		// Topic links change shape with the learn page in use.
		flush_rewrite_rules( false ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- one-shot flush after a permalink-affecting settings change

		return $clean;
	}

	/**
	 * Options as Academy settings keys.
	 *
	 * @param array $clean Clean options.
	 * @return array
	 */
	public static function to_settings( array $clean ) {
		$settings = [];
		foreach ( self::KEYS as $key => $option ) {
			$settings[ $option ] = $clean[ $key ];
		}

		return $settings;
	}

	/**
	 * Whether an item in an ordered list is switched on.
	 *
	 * @param array  $items Items.
	 * @param string $key   Item key.
	 * @return bool
	 */
	public static function enabled( array $items, $key ) {
		foreach ( $items as $item ) {
			if ( $item['key'] === $key ) {
				return ! empty( $item['enabled'] );
			}
		}

		return false;
	}
}
