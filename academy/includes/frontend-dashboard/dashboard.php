<?php
namespace Academy\FrontendDashboard;

use Academy\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Applies Customize → Student Dashboard to the dashboard page: the menu, the
 * home page, the page around it, and its colours in light and dark mode.
 */
class Dashboard {

	/**
	 * While true, the menu is handed back as registered, without the saved
	 * names, order or hidden items (for the Customize screen's list).
	 *
	 * @var bool
	 */
	public static $raw_menu = false;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'academy/frontend_dashboard_menu_items', [ __CLASS__, 'add_pages' ], 900 );
		add_filter( 'academy/frontend_dashboard_menu_items', [ __CLASS__, 'apply_menu' ], 999 );
		add_filter( 'academy/frontend_dashboard/home_cards', [ __CLASS__, 'apply_cards' ], 10, 2 );
		add_filter( 'academy/templates/canvas_container_class', [ __CLASS__, 'container_class' ], 20, 2 );
		add_filter( 'body_class', [ __CLASS__, 'body_class' ] );
		add_filter( 'template_include', [ __CLASS__, 'template' ], 100 );
		add_action( 'template_redirect', [ __CLASS__, 'handle_logout_endpoint' ], 5 );
		add_action( 'wp_head', [ __CLASS__, 'print_styles' ], 30 );
		add_action( 'wp_body_open', [ __CLASS__, 'print_theme_script' ], 1 );
		add_action( 'academy/templates/frontend_dashboard/before_analytics_cards', [ __CLASS__, 'welcome' ] );
		add_action( 'academy/templates/frontend_dashboard/after_analytics_cards', [ __CLASS__, 'continue_learning' ], 5 );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_block_assets' ] );
	}

	/**
	 * The block dashboard's style and script, before the head prints the
	 * import map (a module added while the page body is drawn misses it).
	 *
	 * @return void
	 */
	public static function enqueue_block_assets() {
		if ( self::is_page() && is_user_logged_in() && self::uses_blocks() ) {
			wp_enqueue_style( 'academy-dashboard-style' );
			wp_enqueue_script_module( 'academy-dashboard-view-script-module' );
		}
	}

	/**
	 * Whether the dashboard is built from blocks.
	 *
	 * @return bool
	 */
	/**
	 * The "logout" menu item has a dashboard address too (/dashboard/logout/),
	 * which used to render the dashboard with the user still logged in. Log out
	 * when the request carries a valid logout nonce; otherwise hand over to
	 * WordPress's own "Do you really want to log out?" confirmation, so a link
	 * from another site can't log people out.
	 *
	 * @return void
	 */
	public static function handle_logout_endpoint() {
		if ( 'logout' !== get_query_var( 'academy_dashboard_page' ) || ! self::is_page() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( Helper::get_page_permalink( 'frontend_dashboard_page' ) );
			exit;
		}
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( $nonce && wp_verify_nonce( $nonce, 'log-out' ) ) {
			wp_logout();
			wp_safe_redirect( Helper::get_page_permalink( 'frontend_dashboard_page' ) );
			exit;
		}
		wp_safe_redirect( add_query_arg( 'action', 'logout', wp_login_url() ) );
		exit;
	}

	public static function uses_blocks() {
		return 'blocks' === Settings::get()['engine'];
	}

	/**
	 * Whether this request is the dashboard page.
	 *
	 * @return bool
	 */
	public static function is_page() {
		$page = (int) Helper::get_settings( 'frontend_dashboard_page' );

		return $page && is_page( $page );
	}

	/**
	 * Whether the dashboard is shown without the theme's header and footer.
	 *
	 * @return bool
	 */
	public static function is_bare() {
		return self::is_page() && is_user_logged_in() && 'bare' === Settings::get()['layout'];
	}

	/**
	 * Academy's own page for the dashboard, instead of the theme's page
	 * template (which on block themes adds the page title and whatever the
	 * theme puts around a page). Visitors who are not logged in keep the
	 * theme's page, where the login form sits.
	 *
	 * @param string $template Template path.
	 * @return string
	 */
	public static function template( $template ) {
		if ( ! self::is_page() || ! is_user_logged_in() || 'theme' === Settings::get()['layout'] ) {
			return $template;
		}

		return Helper::plugin_path() . 'templates/academy-canvas.php';
	}

	/**
	 * Names, order and hidden items from Customize. A hidden item keeps its
	 * address, so links to it still work; it is only left out of the menu.
	 *
	 * @param array $items Menu items.
	 * @return array
	 */
	public static function apply_menu( $items ) {
		$saved = Settings::get()['menu'];
		if ( self::$raw_menu || ! $saved || ! is_array( $items ) ) {
			return $items;
		}
		foreach ( $saved as $index => $item ) {
			$key = $item['key'];
			if ( ! isset( $items[ $key ] ) ) {
				continue;
			}
			$items[ $key ]['priority'] = ( $index + 1 ) * 10;
			if ( '' !== $item['label'] ) {
				$items[ $key ]['label'] = $item['label'];
			}
			if ( empty( $item['enabled'] ) && ! in_array( $key, Settings::REQUIRED_MENU, true ) ) {
				$items[ $key ]['public'] = false;
			}
		}

		return $items;
	}

	/**
	 * Pages added to the menu on Customize → Dashboard. Each one shows a
	 * WordPress page inside the dashboard, so it can hold any block or
	 * shortcode. They are registered like any other item, so they can be
	 * reordered, renamed and switched off with the rest of the menu.
	 *
	 * @param array $items Menu items.
	 * @return array
	 */
	public static function add_pages( $items ) {
		if ( ! is_array( $items ) ) {
			return $items;
		}
		foreach ( Settings::get()['pages'] as $index => $page ) {
			if ( isset( $items[ $page['key'] ] ) ) {
				continue;
			}
			$items[ $page['key'] ] = [
				'label'    => $page['label'],
				'area'     => $page['area'],
				'icon'     => 'academy-icon academy-icon--' . $page['icon'],
				'public'   => true,
				'priority' => 90 + $index,
			];
			add_action( 'academy_frontend_dashboard_' . $page['key'] . '_endpoint', [ __CLASS__, 'render_page' ] );
		}

		return $items;
	}

	/**
	 * A page added to the menu, found by its menu key.
	 *
	 * @param string $key Menu key.
	 * @return array|null
	 */
	public static function find_page( $key ) {
		foreach ( Settings::get()['pages'] as $page ) {
			if ( $page['key'] === $key ) {
				return $page;
			}
		}

		return null;
	}

	/**
	 * Show a page added to the menu: its content, blocks and shortcodes
	 * included, inside the dashboard.
	 *
	 * @param string $key Menu key.
	 * @return void
	 */
	public static function render_page( $key ) {
		static $depth = 0;
		$page = self::find_page( (string) $key );
		$post = $page && $page['page'] ? get_post( $page['page'] ) : null;
		// The dashboard page itself would draw the dashboard again, and again.
		$usable = $post && 'page' === $post->post_type && in_array( $post->post_status, [ 'publish', 'private' ], true )
			&& (int) Helper::get_settings( 'frontend_dashboard_page' ) !== (int) $post->ID;

		if ( ! $usable || $depth > 0 ) {
			if ( current_user_can( 'manage_options' ) ) {
				echo '<p class="academy-dashboard-page__empty">' . esc_html__( 'Choose a page for this menu item on Academy → Customize → Dashboard.', 'academy' ) . '</p>';
			}
			return;
		}

		++$depth;
		// Blocks that read the current post (title, featured image…) see this page.
		$previous        = $GLOBALS['post'] ?? null;
		$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $post );
		$content = apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core filter.
		$GLOBALS['post'] = $previous; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		if ( $previous instanceof \WP_Post ) {
			setup_postdata( $previous );
		}
		--$depth;
		?>
		<div class="academy-dashboard-page entry-content">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the page's own content. ?>
		</div>
		<?php
	}

	/**
	 * Cards on the home page, in the saved order, without the hidden ones.
	 *
	 * @param array  $cards Cards, key => card.
	 * @param string $view  learning|teaching|family.
	 * @return array
	 */
	public static function apply_cards( $cards, $view = '' ) {
		$saved = Settings::get()['cards'];
		if ( ! $saved || ! is_array( $cards ) ) {
			return $cards;
		}
		$ordered = [];
		foreach ( $saved as $item ) {
			if ( isset( $cards[ $item['key'] ] ) ) {
				if ( $item['enabled'] ) {
					$ordered[ $item['key'] ] = $cards[ $item['key'] ];
				}
				unset( $cards[ $item['key'] ] );
			}
		}

		// Cards an add-on added since the list was saved go last.
		return $ordered + $cards;
	}

	/**
	 * Boxed or full width. "Auto" keeps Academy's behaviour: full width for
	 * instructors, boxed for students.
	 *
	 * @param string $class   Container class.
	 * @param int    $page_id Page ID.
	 * @return string
	 */
	public static function container_class( $class, $page_id ) {
		if ( (int) Helper::get_settings( 'frontend_dashboard_page' ) !== (int) $page_id ) {
			return $class;
		}
		$width = Settings::get()['width'];
		if ( 'full' === $width ) {
			return 'academy-container-fluid';
		}
		if ( 'boxed' === $width ) {
			return 'academy-container';
		}

		return $class;
	}

	/**
	 * The dashboard's own container, inside the page: full width when set.
	 *
	 * @return string
	 */
	public static function inner_container_class() {
		// The dashboard's layout rules hang on .academy-container, so full width
		// is a modifier on it rather than another container.
		return self::is_full_width() ? 'academy-container academy-dashboard-container--full' : 'academy-container';
	}

	/**
	 * Classes for the dashboard's outer wrapper.
	 *
	 * Block themes cap everything in the post content at their content width
	 * (often 600-700px), far too narrow for the dashboard. WordPress's own
	 * alignment classes lift that cap using the theme's sizes: `alignwide`
	 * takes the theme's wide width, `alignfull` the whole page. Classic
	 * themes don't apply those rules the same way, so they're left as before.
	 *
	 * @return string
	 */
	public static function wrapper_class() {
		$classes = array( 'academy-frontend-dashboard' );
		if ( self::is_full_width() ) {
			$classes[] = 'academy-frontend-dashboard--full';
		}
		if ( wp_is_block_theme() ) {
			$classes[] = self::is_full_width() ? 'alignfull' : 'alignwide';
		}

		/**
		 * Filters the dashboard wrapper's classes.
		 *
		 * @param string[] $classes Classes.
		 */
		$classes = (array) apply_filters( 'academy/frontend_dashboard/wrapper_classes', $classes );

		return implode( ' ', array_map( 'sanitize_html_class', $classes ) );
	}

	/**
	 * Whether the dashboard runs the full width of the page.
	 *
	 * @return bool
	 */
	public static function is_full_width() {
		// "Automatic" keeps the dashboard as it has always been.
		return 'full' === Settings::get()['width'];
	}

	/**
	 * Classes the dashboard's styles hang on.
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function body_class( $classes ) {
		if ( ! self::is_page() ) {
			return $classes;
		}
		$settings  = Settings::get();
		$classes[] = 'academy-dashboard-body';
		if ( 'bare' === $settings['layout'] && is_user_logged_in() ) {
			$classes[] = 'academy-dashboard-body--bare';
		}
		if ( $settings['sidebarCollapsed'] ) {
			$classes[] = 'academy-dashboard-body--collapsed';
		}

		return $classes;
	}

	/**
	 * Sidebar width and the colours set on Customize, for light and dark mode.
	 *
	 * @return void
	 */
	public static function print_styles() {
		if ( ! self::is_page() ) {
			return;
		}
		$settings = Settings::get();
		$css      = sprintf( 'body.academy-dashboard-body{--academy-dashboard-sidebar-width:%dpx;}', $settings['sidebarWidth'] );

		// Only hex colours get here (Settings::colors()).
		foreach ( [ 'light', 'dark' ] as $mode ) {
			$scope  = 'dark' === $mode ? 'body.academy-dashboard-body[data-academy-user-theme="dark"]' : 'body.academy-dashboard-body';
			$colors = $settings['colors'][ $mode ];
			$rules  = [
				// `.academy-canvas` only exists on the "academy"/"bare" page templates
				// (`template()` above); `--bare` covers the bare layout's own body
				// modifier. Neither is present on the default "theme" layout, where
				// the shortcode's own `.academy-frontend-dashboard` wrapper is the
				// only element behind the whole dashboard — include it too, or this
				// colour never has anything to paint on the most common layout.
				'background' => '%1$s .academy-canvas,%1$s.academy-dashboard-body--bare,%1$s .academy-frontend-dashboard{background:%2$s}',
				'sidebar'    => '%1$s .academy-dashboard-menu,%1$s .academy-frontend-dashboard__user{background:%2$s}',
				'active'     => '%1$s .academy-dashboard-menu__item-current>a{background:%2$s}',
				'topbar'     => '%1$s .academy-frontend-dashboard .academy-topbar{background:%2$s}',
				'card'       => '%1$s .academy-analytics-cards--card,%1$s .academy-dashboard-continue__course{background:%2$s}',
			];
			foreach ( $rules as $key => $rule ) {
				if ( '' !== $colors[ $key ] ) {
					$css .= sprintf( $rule, $scope, $colors[ $key ] );
				}
			}
		}//end foreach

		// Dark mode reaches the dashboard's own page background too.
		$css .= 'body.academy-dashboard-body[data-academy-user-theme="dark"] .academy-canvas,body.academy-dashboard-body--bare[data-academy-user-theme="dark"]{background:var(--academy-body-background-color);color:var(--academy-text-color)}';

		printf( "<style id=\"academy-dashboard-customize\">%s</style>\n", $css ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Light or dark, before the page paints: the student's own choice (shared
	 * with the learn page), or the site's default.
	 *
	 * @return void
	 */
	public static function print_theme_script() {
		if ( ! self::is_page() ) {
			return;
		}
		$theme = Settings::get()['theme'];
		?>
		<script>
			( function () {
				try {
					var saved = window.localStorage.getItem( 'academy_theme' );
					var theme = 'dark' === saved || 'light' === saved ? saved : <?php echo wp_json_encode( $theme ); ?>;
					if ( 'system' === theme ) {
						theme = window.matchMedia && window.matchMedia( '(prefers-color-scheme: dark)' ).matches ? 'dark' : 'light';
					}
					document.body.setAttribute( 'data-academy-user-theme', theme );
				} catch ( e ) {}
			} )();
		</script>
		<?php
	}

	/**
	 * The block dashboard's shared state, set by each part that uses it (the
	 * parts are drawn before the frame around them).
	 *
	 * @return void
	 */
	public static function block_state() {
		$collapsed = (bool) Settings::get()['sidebarCollapsed'];
		wp_interactivity_state(
			'academy/dashboard',
			[
				'collapsed' => $collapsed,
				'menuOpen'  => false,
				'userOpen'  => false,
			]
		);
	}

	/**
	 * A greeting above the home page's cards.
	 *
	 * @return void
	 */
	public static function welcome() {
		// The block dashboard has its own Welcome block.
		if ( ! Settings::get()['welcome'] || self::uses_blocks() ) {
			return;
		}
		echo '<div class="academy-dashboard-welcome">';
		self::render_welcome();
		echo '</div>';
	}

	/**
	 * The welcome line: `$text`, or the one set on Customize, with {name}
	 * filled in.
	 *
	 * @param string $text Text, or '' for the saved one.
	 * @return void
	 */
	public static function render_welcome( $text = '' ) {
		if ( ! is_user_logged_in() ) {
			return;
		}
		$user  = wp_get_current_user();
		$name  = $user->first_name ? $user->first_name : $user->display_name;
		$saved = Settings::get()['welcomeText'];
		$text  = '' !== $text ? $text : $saved;
		$text  = '' !== $text
			? str_replace( '{name}', $name, $text )
			/* translators: %s: the student's first name. */
			: sprintf( __( 'Welcome back, %s', 'academy' ), $name );
		?>
		<h2 class="academy-dashboard-welcome__title"><?php echo esc_html( $text ); ?></h2>
		<?php
	}

	/**
	 * "Continue learning": the student's courses in progress, most progressed
	 * first, each with a link back to where they are.
	 *
	 * @return void
	 */
	public static function continue_learning() {
		// The block dashboard has its own Continue Learning block.
		if ( ! Settings::get()['continue'] || self::uses_blocks() ) {
			return;
		}
		self::render_continue();
	}

	/**
	 * The courses a student is part-way through, with a link back in.
	 *
	 * @param array $args count (0 = the saved number), layout grid|list, image, title.
	 * @return void
	 */
	public static function render_continue( array $args = [] ) {
		$settings = Settings::get();
		$args     = wp_parse_args(
			$args,
			[
				'count'  => 0,
				'layout' => 'grid',
				'image'  => true,
				'title'  => '',
			]
		);
		if ( 'learning' !== Helper::current_dashboard_view() || ! is_user_logged_in() ) {
			return;
		}
		$user_id   = get_current_user_id();
		$completed = array_map( 'intval', (array) Helper::get_completed_courses_ids_by_user( $user_id ) );
		$courses   = [];
		// A course can be enrolled in more than once (a renewal, a re-purchase).
		foreach ( array_unique( array_map( 'intval', (array) Helper::get_enrolled_courses_ids_by_user( $user_id ) ) ) as $course_id ) {
			if ( in_array( $course_id, $completed, true ) || 'publish' !== get_post_status( $course_id ) ) {
				continue;
			}
			$courses[] = [
				'id'      => $course_id,
				'percent' => (int) Helper::get_percentage_of_completed_topics_by_student_and_course_id( $user_id, $course_id ),
			];
		}
		if ( ! $courses ) {
			return;
		}
		usort(
			$courses,
			static function ( $a, $b ) {
				return $b['percent'] <=> $a['percent'];
			}
		);
		$courses = array_slice( $courses, 0, $args['count'] ? (int) $args['count'] : $settings['continueCount'] );
		$title   = '' !== $args['title'] ? $args['title'] : __( 'Continue learning', 'academy' );
		?>
		<section class="academy-dashboard-continue academy-dashboard-continue--<?php echo esc_attr( 'list' === $args['layout'] ? 'list' : 'grid' ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
			<?php if ( '' !== trim( $title ) ) : ?>
				<h3 class="academy-dashboard-continue__title"><?php echo esc_html( $title ); ?></h3>
			<?php endif; ?>
			<div class="academy-dashboard-continue__list">
				<?php foreach ( $courses as $course ) : ?>
					<a class="academy-dashboard-continue__course" href="<?php echo esc_url( Helper::get_start_course_permalink( $course['id'] ) ); ?>">
						<?php if ( $args['image'] ) : ?>
						<span class="academy-dashboard-continue__thumb<?php echo has_post_thumbnail( $course['id'] ) ? '' : ' is-empty'; ?>">
							<?php
							if ( has_post_thumbnail( $course['id'] ) ) {
								echo get_the_post_thumbnail( $course['id'], 'medium' );
							} else {
								echo '<span class="academy-icon academy-icon--course" aria-hidden="true"></span>';
							}
							?>
						</span>
						<?php endif; ?>
						<span class="academy-dashboard-continue__body">
							<span class="academy-dashboard-continue__name"><?php echo esc_html( get_the_title( $course['id'] ) ); ?></span>
							<span class="academy-dashboard-continue__bar" aria-hidden="true"><span style="<?php echo esc_attr( 'width:' . $course['percent'] . '%' ); ?>"></span></span>
							<span class="academy-dashboard-continue__meta">
								<?php
								/* translators: %d: percent of the course completed. */
								echo esc_html( sprintf( __( '%d%% complete', 'academy' ), $course['percent'] ) );
								?>
								<span class="academy-dashboard-continue__cta"><?php echo $course['percent'] ? esc_html__( 'Continue', 'academy' ) : esc_html__( 'Start', 'academy' ); ?></span>
							</span>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	/**
	 * Bootstrap grid column for the enrolled courses grid.
	 *
	 * @return string Column class suffix.
	 */
	public static function course_column() {
		$columns = Settings::get()['courseColumns'];

		return (string) ( 12 / max( 2, min( 4, $columns ) ) );
	}
}
