<?php

namespace Academy\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme compatibility: keep a theme's global button/form styles from
 * overriding Academy's own components.
 *
 * Some themes style bare elements with selectors that match or beat
 * Academy's single-class component rules and load after Academy's CSS, so
 * the theme wins:
 * - Hello Elementor's reset.css gives every `button`/`[type=button]`/
 *   `[type=submit]` a pink #c36 border/text and a pink hover background via
 *   `[type=button]:hover` (0,2,0).
 * - Blocksy's main.min.css paints `[type="submit"]` (0,1,0) with its button
 *   colours; it loads after Academy's CSS, so it beats `.academy-btn--bg-*`.
 *
 * Fix: on Academy pages only, load the theme's offending stylesheets inside
 * a CSS cascade layer (`@import … layer()` on the same handle, so handles,
 * dependencies and order are unchanged). Layered rules always lose to
 * unlayered ones — every Academy stylesheet — whatever their specificity,
 * so the theme stays the baseline and Academy wins wherever it declares a
 * property. Themes whose reset also leaves a visible look on properties
 * Academy doesn't declare (Hello's pink) get a second, higher "neutral"
 * layer that clears those inside Academy markup only.
 *
 * Other pages of the site are untouched (verified pixel-identical), and
 * `academy/integration/theme_compat/layer_theme_css` (bool, $template) can
 * switch it off.
 */
class ThemeCompat {

	const LAYER_THEME   = 'academy-theme-base';
	const LAYER_NEUTRAL = 'academy-theme-neutral';

	/**
	 * Parent theme (get_template()) => stylesheet handles to layer, and
	 * whether to add the neutral button layer.
	 */
	const THEMES = [
		'hello-elementor' => [
			'handles' => [ 'hello-elementor', 'hello-elementor-theme-style' ],
			'neutral' => true,
		],
		'blocksy'         => [
			// Only the base bundle; the dynamic colour variables and header/
			// page-title CSS are separate handles and stay as they are.
			'handles' => [ 'ct-main-styles' ],
			'neutral' => false,
		],
	];

	/**
	 * Academy frontend stylesheets; one of them being enqueued marks an
	 * Academy page (course/archive, learn page, dashboard, registration,
	 * shortcode/block pages).
	 */
	const ACADEMY_HANDLES = [
		'academy-common-styles',
		'academy-course-lessons-styles',
		'academy-course-php-render-lessons-styles',
		'academy-frontend-dashboard-styles',
		'academy-dashboard-style',
		'academy-learn-page-style',
	];

	public static function init() {
		if ( ! isset( self::THEMES[ get_template() ] ) ) {
			return;
		}
		// After the theme (10) and Academy (10, or ACADEMY_FRONTEND_SCRIPTS_PRIORITY;
		// the learn page adjusts theme assets at 100) have enqueued.
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'layer_theme_css' ], 110 );
	}

	/**
	 * Swap the theme's stylesheets for an inline `@import … layer()` on the
	 * first handle; the rest become inline-only (empty) handles.
	 *
	 * @return void
	 */
	public static function layer_theme_css() {
		$template = get_template();
		if ( ! apply_filters( 'academy/integration/theme_compat/layer_theme_css', self::is_academy_page(), $template ) ) {
			return;
		}

		$config  = self::THEMES[ $template ];
		$styles  = wp_styles();
		$imports = [];
		$host    = '';

		foreach ( $config['handles'] as $handle ) {
			if ( ! wp_style_is( $handle, 'enqueued' ) || empty( $styles->registered[ $handle ]->src ) ) {
				continue;
			}
			$style = $styles->registered[ $handle ];
			$url   = $style->src;
			// Mirror WP's `rtl => 'replace'` handling for the theme's RTL files.
			if ( is_rtl() && ! empty( $style->extra['rtl'] ) ) {
				$url = 'replace' === $style->extra['rtl']
					? str_replace( '.css', '-rtl.css', $url )
					: $style->extra['rtl'];
			}
			if ( $style->ver ) {
				$url = add_query_arg( 'ver', $style->ver, $url );
			}
			$imports[] = sprintf( '@import url("%s") layer(%s);', esc_url_raw( $url ), self::LAYER_THEME );

			// Inline-only handle: WP prints just the inline <style> for it.
			$style->src = false;
			unset( $style->extra['rtl'] );
			if ( ! $host ) {
				$host = $handle;
			}
		}//end foreach

		if ( ! $host ) {
			return;
		}

		// `@layer` order statement, then the imports (allowed before any
		// other rule), then the neutral layer.
		$css = sprintf( '@layer %1$s, %2$s;', self::LAYER_THEME, self::LAYER_NEUTRAL )
			. implode( '', $imports )
			. ( $config['neutral'] ? self::neutral_layer_css() : '' );

		wp_add_inline_style( $host, $css );
	}

	/**
	 * Whether Academy's frontend CSS is on this page.
	 *
	 * @return bool
	 */
	private static function is_academy_page() {
		foreach ( self::ACADEMY_HANDLES as $handle ) {
			if ( wp_style_is( $handle, 'enqueued' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Inside Academy markup, drop the theme's button colours (text, border,
	 * hover/focus background) for whatever Academy doesn't declare itself.
	 * Only colours: sizing/alignment still come from the theme layer, which
	 * Academy's buttons were laid out against. Sits above the theme layer
	 * but, being layered, below every Academy rule. Scoped to descendants of
	 * an element with an `academy*` class (never `body`, so the site header/
	 * footer keep the theme's buttons).
	 *
	 * @return string
	 */
	private static function neutral_layer_css() {
		$buttons = ':where(:not(body)[class*="academy"]) :is(button,[type="button"],[type="submit"],[type="reset"])';

		return sprintf(
			'@layer %1$s{%2$s{background-color:transparent;border-color:transparent;color:inherit}%2$s:is(:hover,:focus){background-color:transparent;color:inherit}}',
			self::LAYER_NEUTRAL,
			$buttons
		);
	}
}
