<?php
namespace Academy\FrontendDashboard;

use Academy\Admin\Settings\Base as BaseSettings;
use Academy\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Student dashboard options, kept in the main Academy settings and edited on
 * Customize → Student Dashboard.
 */
class Settings {

	/**
	 * Option key in the Academy settings for each option.
	 */
	const KEYS = [
		'engine'           => 'dashboard_engine',
		'layout'           => 'dashboard_layout',
		'width'            => 'dashboard_width',
		'theme'            => 'dashboard_theme',
		'learnerTheme'     => 'dashboard_learner_theme',
		'sidebarWidth'     => 'dashboard_sidebar_width',
		'sidebarCollapsed' => 'dashboard_sidebar_collapsed',
		'menu'             => 'dashboard_menu',
		'cards'            => 'dashboard_cards',
		'welcome'          => 'dashboard_welcome',
		'welcomeText'      => 'dashboard_welcome_text',
		'continue'         => 'dashboard_continue_learning',
		'continueCount'    => 'dashboard_continue_count',
		'courseColumns'    => 'dashboard_course_columns',
		'colors'           => 'dashboard_colors',
		'pages'            => 'dashboard_pages',
	];

	/**
	 * Icons a page added to the menu can use.
	 */
	const PAGE_ICONS = [
		'file',
		'lesson',
		'course',
		'calender',
		'certificate',
		'analytics',
		'qa',
		'mail',
		'star',
		'heart',
		'skill',
		'report',
		'wallet-fill',
		'meeting',
		'group-profile',
		'website',
	];

	/**
	 * Most pages that can be added to the menu.
	 */
	const MAX_PAGES = 20;

	/**
	 * Colours that can be set for the dashboard. Empty follows the brand.
	 */
	const COLORS = [ 'background', 'sidebar', 'active', 'topbar', 'card' ];

	/**
	 * Menu items that always stay: the home page and signing out.
	 */
	const REQUIRED_MENU = [ 'index', 'logout' ];

	/**
	 * Home page cards, by the view they belong to.
	 *
	 * @return array Key => [ label, view ].
	 */
	public static function card_catalog() {
		return [
			'enrolled_course'    => [ __( 'Enrolled courses', 'academy' ), 'learning' ],
			'completed_course'   => [ __( 'Completed courses', 'academy' ), 'learning' ],
			'total_students'     => [ __( 'Total students', 'academy' ), 'teaching' ],
			'total_courses'      => [ __( 'Total courses', 'academy' ), 'teaching' ],
			'total_lessons'      => [ __( 'Total lessons', 'academy' ), 'teaching' ],
			'total_questions'    => [ __( 'Total questions', 'academy' ), 'teaching' ],
			'total_quizzes'      => [ __( 'Total quizzes', 'academy' ), 'teaching' ],
			'total_assignments'  => [ __( 'Total assignments', 'academy' ), 'teaching' ],
			'children'           => [ __( 'Children', 'academy' ), 'family' ],
			'children_courses'   => [ __( 'Their courses', 'academy' ), 'family' ],
			'children_completed' => [ __( 'Completed', 'academy' ), 'family' ],
		];
	}

	/**
	 * Menu items as registered now (add-ons included), for the Customize screen.
	 *
	 * @return array Key => [ label, area ].
	 */
	public static function menu_catalog() {
		// The saved labels and order are left out, so the screen shows the
		// original names beside the renamed ones.
		$catalog = [];
		Dashboard::$raw_menu = true;
		foreach ( (array) Helper::get_frontend_dashboard_menu_items() as $key => $item ) {
			if ( empty( $item['public'] ) ) {
				continue;
			}
			$catalog[ $key ] = [
				'label'    => wp_strip_all_tags( (string) ( $item['label'] ?? $key ) ),
				'area'     => (string) ( $item['area'] ?? 'all' ),
				'priority' => (int) ( $item['priority'] ?? 50 ),
			];
		}
		Dashboard::$raw_menu = false;
		uasort(
			$catalog,
			static function ( $a, $b ) {
				return $a['priority'] <=> $b['priority'];
			}
		);

		return $catalog;
	}

	/**
	 * Defaults.
	 *
	 * @return array
	 */
	public static function defaults() {
		$colors = [];
		foreach ( [ 'light', 'dark' ] as $mode ) {
			$colors[ $mode ] = array_fill_keys( self::COLORS, '' );
		}

		return [
			'engine'           => 'classic',
			'layout'           => 'theme',
			'width'            => 'auto',
			'theme'            => 'light',
			'learnerTheme'     => true,
			'sidebarWidth'     => 260,
			'sidebarCollapsed' => false,
			'menu'             => [],
			'cards'            => [],
			'welcome'          => true,
			'welcomeText'      => '',
			'continue'         => true,
			'continueCount'    => 3,
			'courseColumns'    => 3,
			'colors'           => $colors,
			'pages'            => [],
		];
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
	 * @param array|null $base   Values to fall back to; the options in use by default.
	 * @return array
	 */
	public static function sanitize( array $values, $base = null ) {
		$base = is_array( $base ) ? $base : self::get();
		$pick = static function ( $key, array $allowed ) use ( $values, $base ) {
			return isset( $values[ $key ] ) && in_array( (string) $values[ $key ], $allowed, true ) ? (string) $values[ $key ] : $base[ $key ];
		};
		$flag = static function ( $key ) use ( $values, $base ) {
			return isset( $values[ $key ] ) ? rest_sanitize_boolean( $values[ $key ] ) : (bool) $base[ $key ];
		};
		$int  = static function ( $key, $min, $max ) use ( $values, $base ) {
			$value = isset( $values[ $key ] ) ? absint( $values[ $key ] ) : (int) $base[ $key ];
			return min( $max, max( $min, $value ) );
		};

		return [
			'engine'           => $pick( 'engine', [ 'classic', 'blocks' ] ),
			'layout'           => $pick( 'layout', [ 'theme', 'academy', 'bare' ] ),
			'width'            => $pick( 'width', [ 'auto', 'boxed', 'full' ] ),
			'theme'            => $pick( 'theme', [ 'light', 'dark', 'system' ] ),
			'learnerTheme'     => $flag( 'learnerTheme' ),
			'sidebarWidth'     => $int( 'sidebarWidth', 200, 340 ),
			'sidebarCollapsed' => $flag( 'sidebarCollapsed' ),
			'menu'             => isset( $values['menu'] ) && is_array( $values['menu'] ) ? self::menu( $values['menu'] ) : $base['menu'],
			'cards'            => isset( $values['cards'] ) && is_array( $values['cards'] ) ? self::cards( $values['cards'] ) : $base['cards'],
			'welcome'          => $flag( 'welcome' ),
			'welcomeText'      => isset( $values['welcomeText'] ) ? sanitize_text_field( (string) $values['welcomeText'] ) : $base['welcomeText'],
			'continue'         => $flag( 'continue' ),
			'continueCount'    => $int( 'continueCount', 1, 6 ),
			'courseColumns'    => $int( 'courseColumns', 2, 4 ),
			'colors'           => isset( $values['colors'] ) && is_array( $values['colors'] ) ? self::colors( $values['colors'] ) : $base['colors'],
			'pages'            => isset( $values['pages'] ) && is_array( $values['pages'] ) ? self::pages( $values['pages'] ) : $base['pages'],
		];
	}

	/**
	 * Clean the pages added to the menu: [ key, label, icon, page, area ].
	 * Each key starts with "page-", so it never takes over a built-in page.
	 *
	 * @param array $items Items.
	 * @return array
	 */
	public static function pages( array $items ) {
		$clean = [];
		foreach ( $items as $item ) {
			$item  = (array) $item;
			$label = isset( $item['label'] ) ? sanitize_text_field( (string) $item['label'] ) : '';
			$key   = isset( $item['key'] ) ? sanitize_key( (string) $item['key'] ) : '';
			if ( 0 !== strpos( $key, 'page-' ) || strlen( $key ) < 6 ) {
				$slug = sanitize_title( $label );
				$key  = 'page-' . ( '' !== $slug ? $slug : wp_generate_password( 6, false ) );
			}
			$base = $key;
			for ( $i = 2; isset( $clean[ $key ] ); $i++ ) {
				$key = $base . '-' . $i;
			}
			$icon = isset( $item['icon'] ) ? sanitize_key( (string) $item['icon'] ) : '';
			$area = isset( $item['area'] ) ? (string) $item['area'] : 'all';

			$clean[ $key ] = [
				'key'   => $key,
				'label' => '' !== $label ? $label : __( 'Page', 'academy' ),
				'icon'  => in_array( $icon, self::PAGE_ICONS, true ) ? $icon : 'file',
				'page'  => isset( $item['page'] ) ? absint( $item['page'] ) : 0,
				'area'  => in_array( $area, [ 'all', 'learning', 'teaching', 'family' ], true ) ? $area : 'all',
			];
			if ( count( $clean ) >= self::MAX_PAGES ) {
				break;
			}
		}//end foreach

		return array_values( $clean );
	}

	/**
	 * Clean the menu list: [ key, enabled, label ] in the saved order.
	 *
	 * @param array $items Items.
	 * @return array
	 */
	public static function menu( array $items ) {
		$clean = [];
		foreach ( $items as $item ) {
			$item = (array) $item;
			$key  = isset( $item['key'] ) ? sanitize_key( (string) $item['key'] ) : '';
			if ( '' === $key || isset( $clean[ $key ] ) ) {
				continue;
			}
			$clean[ $key ] = [
				'key'     => $key,
				'enabled' => in_array( $key, self::REQUIRED_MENU, true ) || ! isset( $item['enabled'] ) || rest_sanitize_boolean( $item['enabled'] ),
				'label'   => isset( $item['label'] ) ? sanitize_text_field( (string) $item['label'] ) : '',
			];
		}

		return array_values( $clean );
	}

	/**
	 * Clean the card list: [ key, enabled ] in the saved order.
	 *
	 * @param array $items Items.
	 * @return array
	 */
	public static function cards( array $items ) {
		$known = self::card_catalog();
		$clean = [];
		foreach ( $items as $item ) {
			$item = (array) $item;
			$key  = isset( $item['key'] ) ? (string) $item['key'] : '';
			if ( isset( $known[ $key ] ) && ! isset( $clean[ $key ] ) ) {
				$clean[ $key ] = [
					'key'     => $key,
					'enabled' => ! isset( $item['enabled'] ) || rest_sanitize_boolean( $item['enabled'] ),
				];
			}
		}

		return array_values( $clean );
	}

	/**
	 * Clean dashboard colours.
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
	 * Save options into the main settings.
	 *
	 * @param array $values Options.
	 * @return array The saved options.
	 */
	public static function save( array $values ) {
		$before = wp_list_pluck( self::get()['pages'], 'key' );
		$clean  = self::sanitize( $values );
		// A page added to or taken off the menu has its own address.
		if ( wp_list_pluck( $clean['pages'], 'key' ) !== $before ) {
			Helper::flush_rewrite_rules();
		}
		BaseSettings::save_settings( self::to_settings( $clean ) );
		\Academy\Design\Palette::refresh_globals( self::to_settings( $clean ) );

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
}
