<?php
namespace Academy\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Helper;

class Menu {

	public static function init() {
		$self = new self();
		add_action( 'admin_menu', array( $self, 'admin_menu' ) );
		add_action( 'admin_head', array( $self, 'add_admin_menu_css' ) );
		add_action( 'admin_footer', array( $self, 'print_flyout_submenus' ) );
		// Priority 20 so it runs after Migration::run_migration() (default 10)
		// has had the chance to set the show_whats_new flag on the same
		// request the version change is first detected.
		add_action( 'admin_init', array( $self, 'maybe_redirect_about' ), 20 );
	}

	/**
	 * Add admin menu page
	 *
	 * @return void
	 */
	public function admin_menu() {
		$icon_url = $this->get_toplevel_menu_icon_url();
		$page_title = $this->get_toplevel_menu_title();
		add_menu_page( $page_title, $page_title, 'manage_options', ACADEMY_PLUGIN_SLUG, [ $this, 'load_main_template' ], $icon_url, 2 );
		foreach ( Helper::get_admin_menu_list() as $item_key => $item ) {
			add_submenu_page( $item['parent_slug'], $item['title'], $this->menu_title( $item ), $item['capability'], $item_key, [ $this, 'load_main_template' ] );
		}

		// "About" — registered as a real submenu so ?page=academy-about
		// resolves and passes the admin-page access check, but hidden from the
		// visible menu via CSS (add_admin_menu_css). It is shown once per update
		// instead of via a permanent menu item.
		$about = ACADEMY_PLUGIN_SLUG . '-about';
		add_submenu_page( ACADEMY_PLUGIN_SLUG, __( 'About', 'academy' ), __( 'About', 'academy' ), 'manage_options', $about, [ $this, 'load_main_template' ] );
	}

	/**
	 * A menu item's name, with its badge ("New") when it has one.
	 *
	 * @param array $item Menu item.
	 * @return string
	 */
	private function menu_title( array $item ) {
		// A group's link shows the group name (Helper::nest_admin_menu_pages()).
		$title = ! empty( $item['menu_title'] ) ? $item['menu_title'] : $item['title'];
		if ( empty( $item['badge'] ) ) {
			return $title;
		}

		return $title . ' <span class="academy-menu-badge">' . esc_html( $item['badge'] ) . '</span>';
	}

	/**
	 * Show the "About" page once after each plugin update.
	 *
	 * Driven entirely by the version bump: Migration::run_migration() sets the
	 * `show_whats_new` flag (in `academy_migrations`) when ACADEMY_VERSION changes (updates only,
	 * not fresh installs). This just consumes that flag once — redirecting to
	 * the About page from ANY screen the first safe request after the update,
	 * then clearing the flag so it never fires again until the next version bump.
	 * Contexts where a redirect would break something (the update/install flow,
	 * bulk/row actions, form POSTs) are skipped so it lands on the next safe page.
	 *
	 * @return void
	 */
	public function maybe_redirect_about() {
		if ( wp_doing_ajax() || wp_doing_cron() || is_network_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Nothing to do unless the migrator flagged a version change.
		if ( ! \Academy\Options::get( \Academy\Options::MIGRATIONS, 'show_whats_new' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page  = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$about = ACADEMY_PLUGIN_SLUG . '-about';

		// Already on the About page → clear the flag and stop redirecting.
		if ( $about === $page ) {
			\Academy\Options::delete( \Academy\Options::MIGRATIONS, 'show_whats_new' );
			return;
		}

		// Don't hijack the update/install flow, bulk or row actions, or form
		// submissions — let those complete and fire on the next plain page view
		// (the flag survives, so the redirect still happens next safe request).
		global $pagenow;
		$blocked_pages = [ 'update.php', 'update-core.php', 'plugins.php', 'plugin-install.php', 'admin-post.php', 'options.php', 'async-upload.php', 'media-upload.php' ];
		if ( in_array( $pagenow, $blocked_pages, true ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'GET' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) ) || isset( $_GET['action'] ) ) {
			return;
		}

		// Clear before redirecting so a failed redirect can't cause a loop.
		\Academy\Options::delete( \Academy\Options::MIGRATIONS, 'show_whats_new' );
		wp_safe_redirect( admin_url( 'admin.php?page=' . $about ) );
		exit;
	}
	// Menu visibility for `academy_manager` (academy/openspec/changes/academy-manager-role/)
	// is deliberately NOT handled here. That role carries no capabilities of
	// its own beyond a marker, so a holder is routed through the exact same
	// per-area menu filtering every other Manage Roles staff member already
	// gets from the pro `role-permission` addon's own
	// `AcademyProRolePermission\Menu::filter_menu_for_staff()` /
	// `relax_top_level()` — a blanket "show everything" version lived here
	// briefly and was reverted once it defeated per-area granularity.

	/**
	 * Prints the root element the Academy admin app mounts into.
	 */
	public function load_main_template() {
		$preloader_html = apply_filters( 'academy/preloader', academy_get_preloader_html() );
		echo '<div id="academywrap" class="academywrap">' . wp_kses_post( $preloader_html ) . '</div>';
	}
	public function get_toplevel_menu_title() {
		return apply_filters( 'academy/admin/toplevel_menu_title', __( 'Academy LMS', 'academy' ) );
	}
	public function get_toplevel_menu_icon_url() {
		// phpcs:disable
		if ( $this->is_academy_admin_page() ) {
			$icon_url = 'data:image/svg+xml;base64, ' . base64_encode( file_get_contents( ACADEMY_ASSETS_DIR_PATH . 'images/logo-white.svg' ) );
			return apply_filters( 'academy/admin/toplevel_active_menu_icon', $icon_url );
		}
		$icon_url = 'data:image/svg+xml;base64, ' . base64_encode( file_get_contents( ACADEMY_ASSETS_DIR_PATH . 'images/admin-logo.svg' ) );
		return apply_filters( 'academy/admin/toplevel_inactive_menu_icon', $icon_url );
	}

	/**
	 * Whether the current admin page lives under the Academy top-level menu
	 * (Dashboard, Courses, Settings, …), i.e. when that menu shows as open and
	 * needs its "active" icon — not only the bare `?page=academy` dashboard.
	 *
	 * @return bool
	 */
	private function is_academy_admin_page() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( '' === $page ) {
			return false;
		}
		if ( ACADEMY_PLUGIN_SLUG . '-about' === $page ) {
			return true;
		}
		$menu = Helper::get_admin_menu_list();
		return isset( $menu[ $page ] ) && ACADEMY_PLUGIN_SLUG === $menu[ $page ]['parent_slug'];
	}
	public function get_logo_url(){
		return apply_filters( 'academy/admin/logo_url',  ACADEMY_ASSETS_URI . 'images/logo.svg' );
	}
	/**
	 * Hide pages shown inside another item's submenu (Helper::nest_admin_menu_pages())
	 * from the flat WordPress menu. They stay registered so their URLs work.
	 *
	 * @return string
	 */
	private function nested_pages_css() {
		$selectors = [];
		foreach ( Helper::get_admin_menu_list() as $key => $item ) {
			if ( ! empty( $item['menu_parent'] ) ) {
				// `>` so only the page's own item hides, not a parent whose submenu
				// links to it; `$=` so academy-courses won't match academy-courses-x.
				$selectors[] = '#adminmenu li.toplevel_page_academy > ul.wp-submenu > li:has(> a[href$="page=' . esc_attr( $key ) . '"])';
			}
		}
		return $selectors ? "\n\t\t\t" . implode( ",\n\t\t\t", $selectors ) . ' { display: none; }' : '';
	}

	/**
	 * Second-level hover menus for the collapsed Academy LMS flyout.
	 *
	 * WordPress menus are one level deep, so on non-Academy pages hovering
	 * "Academy LMS" showed Courses, Quizzes, … with none of their children
	 * (Category, Tags, Bundles, Certificates, …). This adds each item's
	 * `sub_items` as a nested list that opens beside it on hover/focus.
	 * Academy pages render their own React menu (which already shows the
	 * children), so the script bails out there.
	 *
	 * @return void
	 */
	public function print_flyout_submenus() {
		$submenus = [];
		foreach ( Helper::get_admin_menu_list() as $key => $item ) {
			if ( empty( $item['sub_items'] ) || ! empty( $item['menu_parent'] ) || ! current_user_can( $item['capability'] ) ) {
				continue;
			}
			$links = [];
			foreach ( $item['sub_items'] as $sub ) {
				if ( ! empty( $sub['page'] ) ) {
					$url = admin_url( 'admin.php?page=' . $sub['page'] );
				} else {
					$url = admin_url( 'admin.php?page=' . $key . ( ! empty( $sub['slug'] ) ? '&path=' . $sub['slug'] : '' ) );
				}
				$links[] = [
					'url'   => $url,
					'title' => wp_strip_all_tags( $sub['title'] ),
				];
			}
			$submenus[ $key ] = $links;
		}
		if ( ! $submenus ) {
			return;
		}
		?>
		<script>
		( function () {
			if ( document.getElementById( 'academywrap' ) ) {
				return;
			}
			var submenus = <?php echo wp_json_encode( $submenus ); ?>;
			var list = document.querySelector( '#toplevel_page_academy > ul.wp-submenu' );
			if ( ! list ) {
				return;
			}
			Object.keys( submenus ).forEach( function ( key ) {
				var link = list.querySelector( ':scope > li > a[href$="page=' + key + '"]' );
				if ( ! link ) {
					return;
				}
				var ul = document.createElement( 'ul' );
				ul.className = 'academy-flyout-sub';
				submenus[ key ].forEach( function ( sub ) {
					var li = document.createElement( 'li' );
					var a = document.createElement( 'a' );
					a.href = sub.url;
					a.textContent = sub.title;
					li.appendChild( a );
					ul.appendChild( li );
				} );
				link.parentNode.classList.add( 'academy-has-flyout-sub' );
				link.parentNode.appendChild( ul );
			} );
		} )();
		</script>
		<?php
	}

	/**
	 * Divider lines after each menu section (items with `separator_after`).
	 * On the top-level rows, so a divider sits below a group and its open
	 * submenu and never inside a nested submenu.
	 *
	 * @return string
	 */
	private function separators_css() {
		$selectors = [];
		foreach ( Helper::get_admin_menu_list() as $key => $item ) {
			if ( ! empty( $item['separator_after'] ) ) {
				$selectors[] = '#adminmenu li.toplevel_page_academy > ul.wp-submenu > li:has(> a[href$="page=' . esc_attr( $key ) . '"])';
			}
		}
		if ( ! $selectors ) {
			return '';
		}
		return "\n\t\t\t" . implode( ",\n\t\t\t", $selectors ) . " {\n\t\t\t\tmargin-bottom: 7px;\n\t\t\t\tpadding-bottom: 7px;\n\t\t\t\tborder-bottom: 1px solid hsla(0,0%,100%,.2);\n\t\t\t}";
	}

	function add_admin_menu_css() {
		echo '<style>
			/* "About" stays registered (accessible via ?page=) but hidden
			   from the menu — it is shown once per update, not permanently. */
			#adminmenu li.toplevel_page_academy ul.wp-submenu li:has(a[href*="page=academy-about"]) {
				display: none;
			}' . $this->nested_pages_css() . '
			#adminmenu li.toplevel_page_academy a.toplevel_page_academy > .wp-menu-image {
				display: flex;
				justify-content: center;
				align-items: center;
			}
			#adminmenu li.toplevel_page_academy a.toplevel_page_academy > .wp-menu-image img {
				max-width: 20px;
				height: auto;
				padding: 0 !important;
			}
			#adminmenu li.toplevel_page_academy ul li a, #adminmenu li.toplevel_page_academy .wp-submenu > li > a {
				padding: 7px 12px;
			}

			#adminmenu li.toplevel_page_academy ul.wp-submenu li {
				clear: both;
			}
			/* Second-level flyout (print_flyout_submenus()). */
			#adminmenu li.toplevel_page_academy .academy-has-flyout-sub {
				position: relative;
			}
			/* The React menu already shows its own angle icon. */
			#adminmenu li.toplevel_page_academy .academy-has-flyout-sub > a:not(:has(.academy-icon--angle-right))::after {
				content: "\\203A";
				float: right;
				opacity: 0.6;
			}
			#adminmenu li.toplevel_page_academy .academy-flyout-sub {
				display: none;
				position: absolute;
				top: 0;
				left: 100%;
				z-index: 10000;
				min-width: 180px;
				margin: 0;
				padding: 7px 0 8px;
				background: #2c3338;
				box-shadow: 0 3px 5px rgba(0, 0, 0, 0.2);
			}
			#adminmenu li.toplevel_page_academy .academy-has-flyout-sub:hover > .academy-flyout-sub,
			#adminmenu li.toplevel_page_academy .academy-has-flyout-sub:focus-within > .academy-flyout-sub {
				display: block;
			}
			#adminmenu li.toplevel_page_academy .academy-flyout-sub li {
				margin: 0;
			}
			#adminmenu .academy-menu-badge {
				display: inline-block;
				margin-left: 6px;
				padding: 0 7px;
				border-radius: 999px;
				background: #5B47E0;
				color: #fff;
				font-size: 10px;
				font-weight: 600;
				line-height: 17px;
				letter-spacing: 0.02em;
				vertical-align: 1px;
			}
			#adminmenu li.toplevel_page_academy ul.wp-submenu li a[href*="admin.php?page=academy-addons"],
			#adminmenu li.toplevel_page_academy ul.wp-submenu li a[href^="admin.php?page=academy-addons"] {
				color: #FDB022;
			}
			#adminmenu li.toplevel_page_academy ul.wp-submenu li a[href*="admin.php?page=academy-discover"],
			#adminmenu li.toplevel_page_academy ul.wp-submenu li a[href^="admin.php?page=academy-discover"] {
				color: #55F05B;
			}
			/* Section dividers (Helper::order_admin_menu() marks them). */' . $this->separators_css() . '
		</style>';
	}	
}
