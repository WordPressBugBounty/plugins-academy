<?php
namespace Academy\Design;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The live preview behind the Design screen.
 *
 * The preview is the real site: the course catalog and a real course, rendered
 * by the front end with the design being edited — not a mock card. Unsaved
 * changes are kept in a short-lived draft for the person editing, and only
 * their own preview requests see it.
 */
class Preview {

	/**
	 * Query argument carrying the preview nonce.
	 */
	const QUERY = 'academy_design_preview';

	/**
	 * Nonce action.
	 */
	const NONCE = 'academy-design-preview';

	/**
	 * Query argument asking for the page with the theme's header and footer.
	 */
	const CHROME = 'academy_design_chrome';

	/**
	 * Transient prefix for a person's unsaved design.
	 */
	const DRAFT = 'academy_design_draft_';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		$self = new self();
		add_action( 'template_redirect', [ $self, 'use_draft_palette' ], 0 );
		// Early: which learn page answers a topic's URL is decided before
		// template_redirect.
		add_action( 'parse_request', [ $self, 'use_learn_draft' ], 0 );
		add_action( 'parse_request', [ $self, 'use_dashboard_draft' ], 0 );
		add_filter( 'option_' . Settings::OPTION, [ $self, 'use_draft' ] );
		add_filter( 'default_option_' . Settings::OPTION, [ $self, 'use_draft' ] );
		add_filter( 'show_admin_bar', [ $self, 'hide_admin_bar' ] ); // phpcs:ignore WordPressVIPMinimum.UserExperience.AdminBarRemoval.RemovalDetected -- chrome-free preview frame
		add_filter( 'pre_render_block', [ $self, 'skip_header_and_footer' ], 10, 2 );
		add_action( 'wp_head', [ $self, 'print_styles' ], 99 );
		add_action( 'wp_footer', [ $self, 'print_script' ], 99 );
	}

	/**
	 * Whether this preview is showing the course area on its own, without the
	 * theme's header and footer.
	 *
	 * @return bool
	 */
	public static function is_bare() {
		$chrome = isset( $_GET[ self::CHROME ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::CHROME ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only preview flag.

		return self::is_preview() && '1' !== $chrome;
	}

	/**
	 * Leave the header and footer template parts out of a bare preview. Block
	 * themes build their pages from these; classic themes are handled by
	 * Academy's own wrapper template.
	 *
	 * @param string|null $content Short-circuited content, if any.
	 * @param array       $block   Parsed block.
	 * @return string|null
	 */
	public function skip_header_and_footer( $content, $block ) {
		if ( ! self::is_bare() || 'core/template-part' !== ( $block['blockName'] ?? '' ) ) {
			return $content;
		}

		$slug = $block['attrs']['slug'] ?? '';
		$area = $block['attrs']['area'] ?? '';
		$tag  = $block['attrs']['tagName'] ?? '';

		$is_chrome = in_array( $slug, [ 'header', 'footer' ], true )
			|| in_array( $area, [ 'header', 'footer' ], true )
			|| in_array( $tag, [ 'header', 'footer' ], true );

		return $is_chrome ? '' : $content;
	}

	/**
	 * A little CSS for the preview: hide whatever header and footer a theme
	 * prints outside Academy's own templates, and give the course area room.
	 *
	 * @return void
	 */
	public function print_styles() {
		if ( ! self::is_bare() ) {
			return;
		}
		$hide = implode(
			',',
			[
				'body > header',
				'body > footer',
				'[role="banner"]',
				'[role="contentinfo"]',
				'#masthead',
				'#colophon',
				'.site-header',
				'.site-footer',
				'.ha-site-header',
				'.ha-site-footer',
				'.skip-link',
			]
		);
		printf(
			'<style id="academy-design-preview">%s{display:none !important}body{padding-top:0 !important;margin-top:0 !important}</style>' . "\n",
			$hide // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- a fixed list of selectors.
		);
	}

	/**
	 * Whether this request is a preview of an unsaved design.
	 *
	 * @return bool
	 */
	public static function is_preview() {
		static $preview = null;
		if ( null !== $preview ) {
			return $preview;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce = isset( $_GET[ self::QUERY ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::QUERY ] ) ) : '';
		if ( '' === $nonce || ! function_exists( 'wp_get_current_user' ) ) {
			return false;
		}

		$preview = (bool) wp_verify_nonce( $nonce, self::NONCE ) && current_user_can( 'manage_options' );

		return $preview;
	}

	/**
	 * Show the design being edited instead of the saved one.
	 *
	 * @param mixed $value Saved design.
	 * @return mixed
	 */
	public function use_draft( $value ) {
		if ( ! self::is_preview() ) {
			return $value;
		}
		$draft = get_transient( self::DRAFT . get_current_user_id() );

		return is_array( $draft ) ? $draft : $value;
	}

	/**
	 * Other plugins pin banners, chat bubbles and pop-ups to the window. They
	 * are not part of the design being edited, so the preview puts them away —
	 * only floating boxes sitting directly on the page body, never anything
	 * inside the course area.
	 *
	 * @return void
	 */
	public function print_script() {
		if ( ! self::is_preview() ) {
			return;
		}
		// Links keep the preview in every view, with or without the theme's
		// header and footer; putting other plugins' pop-ups away is only for
		// the bare view.
		self::keep_preview_script();
		self::freeze_links_script();
		if ( self::is_bare() ) {
			self::overlay_script();
		}
	}

	/**
	 * Carry the preview arguments onto the site's own links. The unsaved design
	 * is only shown on requests that hold the preview nonce, so following a link
	 * inside the preview (a dashboard menu item, say) would otherwise drop it and
	 * show the saved design instead of the one being edited.
	 *
	 * @return void
	 */
	private static function keep_preview_script() {
		?>
		<script id="academy-design-preview-links-js">
			( function () {
				var keys = [ <?php echo wp_json_encode( self::QUERY ); ?>, <?php echo wp_json_encode( self::CHROME ); ?> ];
				var current = new URLSearchParams( window.location.search );
				var carry = function () {
					Array.prototype.forEach.call( document.querySelectorAll( 'a[href]' ), function ( link ) {
						var raw = link.getAttribute( 'href' ) || '';
						if ( '#' === raw.charAt( 0 ) ) {
							return;
						}
						var url;
						try {
							url = new URL( link.getAttribute( 'href' ), window.location.href );
						} catch ( e ) {
							return;
						}
						if ( url.origin !== window.location.origin || ! /^https?:$/.test( url.protocol ) ) {
							return;
						}
						if ( /\/wp-admin\/|\/wp-login\.php/.test( url.pathname ) ) {
							return;
						}
						var changed = false;
						keys.forEach( function ( key ) {
							if ( current.has( key ) && ! url.searchParams.has( key ) ) {
								url.searchParams.set( key, current.get( key ) );
								changed = true;
							}
						} );
						if ( changed ) {
							link.setAttribute( 'href', url.toString() );
						}
					} );
				};
				carry();
				new window.MutationObserver( carry ).observe( document.body, { childList: true, subtree: true } );
			}() );
		</script>
		<?php
	}

	/**
	 * Inside the Customize screen's preview frame the page is for looking at:
	 * following a link or submitting a form (enrol, add to cart, search…)
	 * would leave the page being styled. Every link is inert there, forms don't
	 * submit, and script navigation (new windows, in-app routes) is stopped;
	 * buttons and hover styles still respond. The Preview component also sends
	 * the frame back if it lands anywhere else.
	 * The same URL opened in its own tab ("Open in a new tab") is not framed,
	 * so it stays fully browsable.
	 *
	 * @return void
	 */
	private static function freeze_links_script() {
		?>
		<style id="academy-design-preview-frozen-links">
			/* View-only: nothing on the page takes pointer events (links,
			   buttons, tabs, Enrol / Add to cart…). The body itself still does,
			   so the preview scrolls. */
			html.academy-design-preview-framed body * { pointer-events: none !important; }
			html.academy-design-preview-framed body { cursor: default !important; }
		</style>
		<script id="academy-design-preview-frozen-links-js">
			( function () {
				if ( window.self === window.top ) {
					return;
				}
				document.documentElement.classList.add( 'academy-design-preview-framed' );
				// Every link is inert: no default action and no other click
				// handler (some "Enroll" buttons are links whose own script
				// sets the location). Capture on window, so nothing runs first.
				var stop = function ( event ) {
					var link = event.target.closest && event.target.closest( 'a, [role="link"]' );
					if ( link ) {
						event.preventDefault();
						event.stopImmediatePropagation();
					}
				};
				[ 'click', 'auxclick', 'dblclick' ].forEach( function ( type ) {
					window.addEventListener( type, stop, true );
				} );
				// Keyboard too: Enter / Space would still press a focused button.
				window.addEventListener( 'keydown', function ( event ) {
					if ( 'Enter' === event.key || ' ' === event.key ) {
						event.preventDefault();
						event.stopImmediatePropagation();
					}
				}, true );
				window.addEventListener( 'submit', function ( event ) {
					event.preventDefault();
					event.stopImmediatePropagation();
				}, true );
				// Script-driven ways of leaving: new windows, and in-app route
				// changes (the dashboard is a single-page app).
				window.open = function () {
					return null;
				};
				window.history.pushState = function () {};
				window.history.replaceState = function () {};
			}() );
		</script>
		<?php
	}

	/**
	 * The script that puts away other plugins' floating boxes. Shared with the
	 * starter template preview.
	 *
	 * @return void
	 */
	public static function overlay_script() {
		?>
		<script id="academy-design-preview-js">
			( function () {
				var tidy = function () {
					Array.prototype.forEach.call( document.body.children, function ( node ) {
						if ( node.querySelector( '.academy-course-card, [class*="wp-block-academy-course-"]' ) ) {
							return;
						}
						var position = window.getComputedStyle( node ).position;
						if ( 'fixed' === position || 'sticky' === position ) {
							node.style.setProperty( 'display', 'none', 'important' );
						}
					} );
				};
				tidy();
				// Pop-ups tend to arrive late, and some arrive twice.
				new window.MutationObserver( tidy ).observe( document.body, { childList: true } );
				window.setTimeout( tidy, 1500 );
				window.setTimeout( tidy, 4000 );
			}() );
		</script>
		<?php
	}

	/**
	 * Keep the admin bar out of the preview.
	 *
	 * @param bool $show Whether to show the admin bar.
	 * @return bool
	 */
	public function hide_admin_bar( $show ) {
		return self::is_preview() ? false : $show;
	}

	/**
	 * Remember the design someone is editing, for a little while.
	 *
	 * @param array      $design  Design.
	 * @param array|null $palette Colours being edited, if any.
	 * @param bool|null  $sync    Whether the theme follows those colours.
	 * @param array|null $display Display options being edited, if any.
	 * @return void
	 */
	public static function save_draft( array $design, $palette = null, $sync = null, $display = null ) {
		set_transient( self::DRAFT . get_current_user_id(), $design, 30 * MINUTE_IN_SECONDS );
		if ( is_array( $palette ) ) {
			set_transient(
				self::DRAFT . 'palette_' . get_current_user_id(),
				[
					'palette' => Palette::sanitize( $palette ),
					'sync'    => (bool) $sync,
					'display' => is_array( $display ) ? $display : null,
				],
				30 * MINUTE_IN_SECONDS
			);
		}
	}

	/**
	 * Keep the learn page options someone is editing, for the preview.
	 *
	 * @param array|null $learn Clean learn page options, or null to leave the draft.
	 * @return void
	 */
	public static function save_learn_draft( $learn ) {
		if ( is_array( $learn ) ) {
			set_transient( self::DRAFT . 'learn_' . get_current_user_id(), $learn, 30 * MINUTE_IN_SECONDS );
		}
	}

	/**
	 * Keep the dashboard options someone is editing, for the preview.
	 *
	 * @param array|null $dashboard Clean dashboard options, or null to leave the draft.
	 * @return void
	 */
	public static function save_dashboard_draft( $dashboard ) {
		if ( is_array( $dashboard ) ) {
			set_transient( self::DRAFT . 'dashboard_' . get_current_user_id(), $dashboard, 30 * MINUTE_IN_SECONDS );
		}
	}

	/**
	 * Show the dashboard options being edited in the preview.
	 *
	 * @return void
	 */
	public function use_dashboard_draft() {
		if ( ! self::is_preview() ) {
			return;
		}
		$draft = get_transient( self::DRAFT . 'dashboard_' . get_current_user_id() );
		if ( is_array( $draft ) ) {
			Palette::refresh_globals( \Academy\FrontendDashboard\Settings::to_settings( \Academy\FrontendDashboard\Settings::sanitize( $draft ) ) );
		}
	}

	/**
	 * Show the learn page options being edited in the preview.
	 *
	 * @return void
	 */
	public function use_learn_draft() {
		if ( ! self::is_preview() ) {
			return;
		}
		$draft = get_transient( self::DRAFT . 'learn_' . get_current_user_id() );
		if ( is_array( $draft ) ) {
			Palette::refresh_globals( \Academy\LearnPage\Settings::to_settings( \Academy\LearnPage\Settings::sanitize( $draft ) ) );
		}
	}

	/**
	 * Show the colours being edited in the preview: Academy's palette, and the
	 * theme's colours when they are set to follow it.
	 *
	 * @return void
	 */
	public function use_draft_palette() {
		if ( ! self::is_preview() ) {
			return;
		}
		$draft = get_transient( self::DRAFT . 'palette_' . get_current_user_id() );
		if ( ! is_array( $draft ) || empty( $draft['palette'] ) ) {
			return;
		}
		Palette::refresh_globals( $draft['palette'] );
		if ( ! empty( $draft['display'] ) ) {
			Palette::refresh_globals(
				[
					'course_archive_sidebar_position'       => $draft['display']['sidebar'],
					'is_enabled_course_single_enroll_count' => $draft['display']['enrollCount'],
					'course_card_style'                     => $draft['display']['cardStyle'],
				]
			);
		}
		if ( ! empty( $draft['sync'] ) ) {
			ThemeColors::preview_theme( Palette::brand( $draft['palette'] ) );
		}
	}

	/**
	 * The pages the preview can show.
	 *
	 * @return array catalog and course URLs, ready to load in the preview.
	 */
	public static function urls() {
		$course = get_posts(
			[
				'post_type'      => 'academy_courses',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			]
		);

		$nonce   = wp_create_nonce( self::NONCE );
		$catalog = (string) get_post_type_archive_link( 'academy_courses' );
		$single  = $course ? (string) get_permalink( $course[0] ) : '';

		$learn     = self::learn_urls( $course ? (int) $course[0] : 0 );
		$dashboard = (int) \Academy\Helper::get_settings( 'frontend_dashboard_page' );
		$dashboard = $dashboard ? (string) get_permalink( $dashboard ) : '';

		return [
			'learnBlocks' => $learn['blocks'] ? add_query_arg( self::QUERY, $nonce, $learn['blocks'] ) : '',
			'learnReact'  => $learn['react'] ? add_query_arg( self::QUERY, $nonce, $learn['react'] ) : '',
			'dashboard'   => $dashboard ? add_query_arg( self::QUERY, $nonce, $dashboard ) : '',
			'catalog'   => $catalog ? add_query_arg( self::QUERY, $nonce, $catalog ) : '',
			'course'    => $single ? add_query_arg( self::QUERY, $nonce, $single ) : '',
			'chromeArg' => self::CHROME,
			'hasCourse' => ! empty( $course ),
		];
	}

	/**
	 * The first topic of a course, on each learn page.
	 *
	 * @param int $course_id Course ID.
	 * @return array blocks and react URLs.
	 */
	private static function learn_urls( $course_id ) {
		$urls = [
			'blocks' => '',
			'react'  => '',
		];
		if ( ! $course_id ) {
			return $urls;
		}
		foreach ( (array) \Academy\Helper::get_course_curriculum( $course_id, false ) as $section ) {
			foreach ( (array) ( $section['topics'] ?? [] ) as $topic ) {
				$topic = 'sub-curriculum' === ( $topic['type'] ?? '' ) ? current( (array) ( $topic['topics'] ?? [] ) ) : $topic;
				if ( is_array( $topic ) && ! empty( $topic['id'] ) ) {
					$urls['blocks'] = \Academy\LearnPage\Data::link( $topic, $course_id );
					$urls['react']  = add_query_arg( [ 'source' => 'curriculums' ], get_permalink( $course_id ) ) . '#/' . $topic['type'] . '/' . $topic['id'];
					return $urls;
				}
			}
		}

		return $urls;
	}
}
