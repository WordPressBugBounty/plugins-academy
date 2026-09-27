<?php
namespace Academy\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Helper;

class GlobalCss {
	public static function init() {
		$self = new self();
		if ( is_admin() ) {
			add_action( 'admin_head', array( $self, 'get_global_css' ) );
			add_action( 'admin_print_styles', array( $self, 'get_global_css' ), 30 );
		} else {
			add_action( 'wp_head', array( $self, 'get_global_css' ) );
		}
	}
	public function get_global_css() {
		$primary_color = Helper::get_settings( 'primary_color', '#7b68ee' );
		$secondary_color = Helper::get_settings( 'secondary_color', '#f2f0fd' );
		$text_color = Helper::get_settings( 'text_color', '#131d2b' );
		$border_color = Helper::get_settings( 'border_color', '#e5e7eb' );
		$gray_color = Helper::get_settings( 'gray_color', '#f6f7f8' );
		$surface_color = Helper::get_settings( 'surface_color', '#ffffff' );

		// Dark mode palette — only ever applied inside `.academy-lessons[data-academy-theme="dark"]`
		// (site default) or `.academy-lessons[data-academy-user-theme="dark"]` (learner override on
		// the Learn page). Scoping to that selector keeps these values from ever leaking into
		// wp-admin or any other frontend page. See openspec/changes/learn-page-dark-mode/design.md.
		$dark_primary_color = Helper::get_settings( 'dark_primary_color', '#7b68ee' );
		$dark_secondary_color = Helper::get_settings( 'dark_secondary_color', '#252140' );
		$dark_text_color = Helper::get_settings( 'dark_text_color', '#e6e9ef' );
		$dark_border_color = Helper::get_settings( 'dark_border_color', '#333d4b' );
		$dark_gray_color = Helper::get_settings( 'dark_gray_color', '#1f2632' );
		$dark_surface_color = Helper::get_settings( 'dark_surface_color', '#161c26' );

		// Fixed (non-admin-configurable) dark counterparts for the auxiliary shell
		// tokens defined in assets/scss/common/_global.scss (--academy-text-color-black,
		// --academy-dark-gray-color, --academy-secondary-gray-color,
		// --academy-background-gray-color). These aren't Brand & Style fields — adding
		// six more color pickers for a "core surfaces only" pass was judged not worth
		// the settings-UI weight — but the shell (drawer headings, meta text, page
		// canvas) uses them, so they need *some* legible dark value.
		$dark_text_color_black = '#f5f7fa';
		$dark_secondary_gray_color = '#a9b0bc';
		$dark_gray_color_muted = '#7c8594';
		$dark_background_gray_color = '#0e131b';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "
            <style>
                :root {
                    --academy-primary-color: $primary_color;
                    --academy-secondary-color: $secondary_color;
                    --academy-text-color: $text_color;
                    --academy-border-color: $border_color;
                    --academy-gray-color: $gray_color;
                    --academy-surface-color: $surface_color;
                }
                .academy-lessons[data-academy-theme=\"dark\"]:not([data-academy-user-theme=\"light\"]),
                .academy-lessons[data-academy-user-theme=\"dark\"],
                .academy-course-curriculum-wrapper[data-academy-theme=\"dark\"]:not([data-academy-user-theme=\"light\"]),
                .academy-course-curriculum-wrapper[data-academy-user-theme=\"dark\"],
                body[data-academy-theme=\"dark\"]:not([data-academy-user-theme=\"light\"]),
                body[data-academy-user-theme=\"dark\"] {
                    --academy-primary-color: $dark_primary_color;
                    --academy-secondary-color: $dark_secondary_color;
                    --academy-text-color: $dark_text_color;
                    --academy-border-color: $dark_border_color;
                    --academy-gray-color: $dark_gray_color;
                    --academy-surface-color: $dark_surface_color;
                    --academy-text-color-black: $dark_text_color_black;
                    --academy-secondary-gray-color: $dark_secondary_gray_color;
                    --academy-dark-gray-color: $dark_gray_color_muted;
                    --academy-background-gray-color: $dark_background_gray_color;
                    /*
                     * The light-mode shadow color (near-black at low opacity)
                     * all but vanishes against an already near-black dark
                     * surface. Pure black at higher opacity keeps it visible —
                     * it still reads as a darkening vignette against the dark
                     * palette's surfaces, it just needs more
                     * contrast to do it than on a white background.
                     */
                    --academy-drawer-shadow-color: rgba(0, 0, 0, 0.55);
                    --academy-shadow-soft: 0 1px 2px 0 rgba(0, 0, 0, 0.4);
                    --academy-shadow-hard: 0 4px 8px 0 rgba(0, 0, 0, 0.45);
                    --academy-success-color: #34d399;
                    --academy-danger-color: #f87171;
                    --academy-warning-color: #fbbf24;
                    --academy-heading-color: var(--academy-text-color-black);
                    --academy-description-color: var(--academy-secondary-gray-color);
                    --academy-icon-inactive-color: var(--academy-dark-gray-color);
                    --academy-accent-color: var(--academy-primary-color);
                    --academy-primary-light-color: var(--academy-secondary-color);
                    --academy-divider-color: var(--academy-border-color);
                    --academy-placeholder-background: var(--academy-gray-color);
                    --academy-body-background-color: var(--academy-background-gray-color);
                }
                /*
                 * <body> itself was never given a background (only the CSS
                 * variables above, which the wrap/content elements consume),
                 * so any part of the viewport taller than the wrap fell back
                 * to the theme's own (usually white) body background instead
                 * of Academy's page canvas color — a transparent <html> makes
                 * the UA paint the viewport canvas from <body>'s background
                 * per spec, so setting it here closes that gap. Reads the
                 * variable rather than a light/dark literal so it stays in
                 * sync with whichever palette is active (the dark override
                 * above already redefines it when dark mode is on) — one
                 * rule instead of duplicating the light/dark selector split.
                 * Scoped to the course-single body class (present on both the
                 * React and PHP curriculum render paths) so no other
                 * frontend page's background is touched.
                 */
                body.single-academy_courses {
                    background-color: var(--academy-background-gray-color);
                }
            </style>
        ";

		// Backend (wp-admin) dark mode — gated to Academy's own admin screens only, so
		// the #wpcontent/#wpbody-content override below never bleeds into other
		// plugins' admin pages. Scoped to <html> (not <body> or #academywrap):
		// #academywrap is a DESCENDANT of #wpcontent/#wpbody-content, not an ancestor,
		// so a selector on #academywrap can't restyle its own parents — and <body>
		// doesn't exist yet when this runs (admin_head fires inside <head>), so the
		// bootstrap script below targets document.documentElement (<html>, available
		// immediately) to avoid a flash of light mode on every page load. See
		// openspec/changes/backend-dashboard-dark-mode/design.md decision 2.
		if ( is_admin() && Helper::is_academy_admin_page() ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo "
                <style>
                    html[data-academy-theme=\"dark\"],
                    html[data-academy-user-theme=\"dark\"] {
                        --academy-primary-color: $dark_primary_color;
                        --academy-secondary-color: $dark_secondary_color;
                        --academy-text-color: $dark_text_color;
                        --academy-border-color: $dark_border_color;
                        --academy-gray-color: $dark_gray_color;
                        --academy-surface-color: $dark_surface_color;
                        --academy-text-color-black: $dark_text_color_black;
                        --academy-secondary-gray-color: $dark_secondary_gray_color;
                        --academy-dark-gray-color: $dark_gray_color_muted;
                        --academy-background-gray-color: $dark_background_gray_color;
                        --academy-shadow-soft: 0 1px 2px 0 rgba(0, 0, 0, 0.4);
                        --academy-shadow-hard: 0 4px 8px 0 rgba(0, 0, 0, 0.45);
                        --academy-success-color: #34d399;
                        --academy-danger-color: #f87171;
                        --academy-warning-color: #fbbf24;
                        --academy-heading-color: var(--academy-text-color-black);
                        --academy-description-color: var(--academy-secondary-gray-color);
                        --academy-icon-inactive-color: var(--academy-dark-gray-color);
                        --academy-accent-color: var(--academy-primary-color);
                        --academy-primary-light-color: var(--academy-secondary-color);
                        --academy-divider-color: var(--academy-border-color);
                        --academy-placeholder-background: var(--academy-gray-color);
                        --academy-body-background-color: var(--academy-background-gray-color);
                    }
                    html[data-academy-theme=\"dark\"] #academywrap input[type=\"checkbox\"],
                    html[data-academy-user-theme=\"dark\"] #academywrap input[type=\"checkbox\"],
                    html[data-academy-theme=\"dark\"] #academywrap input[type=\"radio\"],
                    html[data-academy-user-theme=\"dark\"] #academywrap input[type=\"radio\"] {
                        background-color: var(--academy-gray-color);
                        border-color: var(--academy-dark-gray-color);
                    }
                    html[data-academy-theme=\"dark\"] #wpcontent,
                    html[data-academy-user-theme=\"dark\"] #wpcontent,
                    html[data-academy-theme=\"dark\"] #wpbody-content,
                    html[data-academy-user-theme=\"dark\"] #wpbody-content {
                        background: var(--academy-background-gray-color);
                        color: var(--academy-text-color);
                    }
                </style>
                <script>
                    (function () {
                        try {
                            var t = window.localStorage.getItem( 'academy_backend_theme' );
                            if ( 'dark' === t ) {
                                document.documentElement.setAttribute( 'data-academy-user-theme', 'dark' );
                            }
                        } catch ( e ) {}
                    })();
                </script>
            ";
		}//end if
	}
}
