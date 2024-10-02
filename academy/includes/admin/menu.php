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
			add_submenu_page( $item['parent_slug'], $item['title'], $item['title'], $item['capability'], $item_key, [ $this, 'load_main_template' ] );
		}
	}
	public function load_main_template() {
		$preloader_html = apply_filters( 'academy/preloader', academy_get_preloader_html() );
		echo '<div id="academywrap" class="academywrap">' . wp_kses_post( $preloader_html ) . '</div>';
	}
	public function get_toplevel_menu_title() {
		return apply_filters( 'academy/admin/toplevel_menu_title', __( 'Academy LMS', 'academy' ) );
	}
	public function get_toplevel_menu_icon_url() {
		// phpcs:disable
		if ( isset( $_GET['page'] ) && 'academy' === $_GET['page'] ) {
			$icon_url = 'data:image/svg+xml;base64, ' . base64_encode( file_get_contents( ACADEMY_ASSETS_DIR_PATH . 'images/logo-white.svg' ) );
			return apply_filters( 'academy/admin/toplevel_active_menu_icon', $icon_url );
		}
		$icon_url = 'data:image/svg+xml;base64, ' . base64_encode( file_get_contents( ACADEMY_ASSETS_DIR_PATH . 'images/admin-logo.svg' ) );
		return apply_filters( 'academy/admin/toplevel_inactive_menu_icon', $icon_url );
	}
	public function get_logo_url(){
		return apply_filters( 'academy/admin/logo_url',  ACADEMY_ASSETS_URI . 'images/logo.svg' );
	}
	function add_admin_menu_css() {
		echo '<style>
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
			#adminmenu li.toplevel_page_academy ul.wp-submenu li a[href*="admin.php?page=academy-addons"],
			#adminmenu li.toplevel_page_academy ul.wp-submenu li a[href^="admin.php?page=academy-addons"] {
				color: #FDB022;
			}
			#adminmenu li.toplevel_page_academy ul.wp-submenu li.wp-first-item a[href^="admin.php?page=academy"]:after,
			#adminmenu li.toplevel_page_academy ul.wp-submenu li.wp-first-item a[href*="admin.php?page=academy"]:after,
			#adminmenu li.toplevel_page_academy ul.wp-submenu li a[href*="admin.php?page=academy-announcements"]:after,
			#adminmenu li.toplevel_page_academy ul.wp-submenu li a[href^="admin.php?page=academy-announcements"]:after,
			#adminmenu li.toplevel_page_academy ul.wp-submenu li a[href*="admin.php?page=academy-tools"]:after,
			#adminmenu li.toplevel_page_academy ul.wp-submenu li a[href^="admin.php?page=academy-tools"]:after {
				border-bottom: 1px solid hsla(0,0%,100%,.2);
				display: block;
				float: left;
				margin: 15px -15px 7px;
				content: "";
				width: calc(100% + 26px);
			}
		</style>';
	}	
}
