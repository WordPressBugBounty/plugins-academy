<?php
namespace Academy\API;

use Academy\Design\Outline;
use Academy\Design\Palette;
use Academy\Design\Patterns;
use Academy\Design\Presets;
use Academy\Design\Preview;
use Academy\Design\Settings;
use Academy\Design\Templates;
use Academy\LearnPage\Settings as LearnSettings;
use Academy\FrontendDashboard\Settings as DashboardSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads and saves the course design, and reports on the course templates.
 */
class CourseDesign extends Controller {

	/**
	 * User meta set once someone has finished or skipped the Customize tour.
	 */
	const TOUR_META = 'academy_customize_tour_seen';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		$self = new self();
		add_action( 'rest_api_init', [ $self, 'register_routes' ] );
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/course-design',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_value' ],
					'permission_callback' => [ $this, 'permissions_check' ],
				],
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_value' ],
					'permission_callback' => [ $this, 'permissions_check' ],
				],
			]
		);
		register_rest_route(
			$this->namespace,
			'/course-design/learn-layout',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'learn_layout' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'do' => [
						'type' => 'string',
						'enum' => [ 'edit', 'reset' ],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/course-design/dashboard-layout',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'dashboard_layout' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'do' => [
						'type' => 'string',
						'enum' => [ 'edit', 'reset' ],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/course-design/dashboard-page',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'dashboard_page' ],
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' ) && current_user_can( 'publish_pages' );
				},
				'args'                => [
					'label' => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/course-design/outline',
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_outline' ],
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);
		register_rest_route(
			$this->namespace,
			'/course-design/preview',
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_preview' ],
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);
		register_rest_route(
			$this->namespace,
			'/course-design/edit',
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'edit_piece' ],
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);
		register_rest_route(
			$this->namespace,
			'/course-design/tour',
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'finish_tour' ],
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);
		register_rest_route(
			$this->namespace,
			'/course-design/reset',
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'create_value' ],
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);
	}

	/**
	 * The saved design, what it can be made of, and the course templates.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_value( $request ) {
		return rest_ensure_response(
			[
				'design'    => Settings::get(),
				'elements'  => Settings::card_elements(),
				'sections'  => Settings::page_sections(),
				'pieces'    => Templates::pieces(),
				'templates' => Templates::state(),
				'outline'   => self::outlines(),
				'presets'   => Presets::all(),
				'preview'   => Preview::urls(),
				'colors'    => self::colors(),
				'display'   => Settings::display(),
				'mode'      => Settings::mode(),
				'learn'     => LearnSettings::get(),
				'learnLayout' => \Academy\LearnPage\Layout::state(),
				'dashboard'   => DashboardSettings::get(),
				'dashboardParts' => self::dashboard_parts(),
				'dashboardLayout' => \Academy\FrontendDashboard\Layout::state(),
				'tourSeen'  => (bool) get_user_meta( get_current_user_id(), self::TOUR_META, true ),
			]
		);
	}

	/**
	 * Everything the Colours & Style tab needs.
	 *
	 * @return array
	 */
	private static function colors() {
		return [
			'palette'      => Palette::get(),
			'defaults'     => Palette::defaults(),
			'themeSync'    => Palette::syncs_theme(),
			'themeCanSync' => Palette::theme_can_sync(),
			'themeName'    => wp_get_theme()->get( 'Name' ),
			'palettes'     => \Academy\Design\ThemeColors::palettes(),
			'legacy'       => ! Settings::mode()['applies'],
			'customizer'   => admin_url( 'customize.php' ),
		];
	}

	/**
	 * What every part that has a pattern is made of.
	 *
	 * @return array
	 */
	private static function outlines() {
		$outlines = [];
		foreach ( array_keys( Patterns::all_pieces() ) as $piece ) {
			$outlines[ $piece ] = Outline::get( $piece );
		}

		return $outlines;
	}

	/**
	 * Reorder a pattern's blocks, or switch some of them off.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function update_outline( $request ) {
		$piece = sanitize_key( (string) $request->get_param( 'piece' ) );
		$items = (array) $request->get_param( 'items' );

		return rest_ensure_response(
			[
				'saved'   => Outline::apply( $piece, $items ),
				'outline' => self::outlines(),
			]
		);
	}

	/**
	 * Remember that the current user has seen the Customize tour.
	 *
	 * @return \WP_REST_Response
	 */
	public function finish_tour() {
		update_user_meta( get_current_user_id(), self::TOUR_META, 1 );

		return rest_ensure_response( [ 'tourSeen' => true ] );
	}

	/**
	 * The dashboard's menu items and home cards, for the Customize screen.
	 *
	 * @return array
	 */
	private static function dashboard_parts() {
		$menu = [];
		foreach ( DashboardSettings::menu_catalog() as $key => $item ) {
			$menu[] = [
				'key'      => $key,
				'label'    => $item['label'],
				'area'     => $item['area'],
				'required' => in_array( $key, DashboardSettings::REQUIRED_MENU, true ),
			];
		}
		$cards = [];
		foreach ( DashboardSettings::card_catalog() as $key => $card ) {
			$cards[] = [
				'key'   => $key,
				'label' => $card[0],
				'view'  => $card[1],
			];
		}

		// Pages that can fill a menu item, but not the dashboard page itself.
		$pages = [];
		// phpcs:disable WordPress.WP.PostsPerPage.posts_per_page_posts_per_page, WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- picker list capped at 200; excludes a single page from an admin picker
		foreach (
			get_posts(
				[
					'post_type'      => 'page',
					'post_status'    => [ 'publish', 'private' ],
					'posts_per_page' => 200,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'exclude'        => [ (int) \Academy\Helper::get_settings( 'frontend_dashboard_page' ) ],
				]
			) as $page
		) {
		// phpcs:enable WordPress.WP.PostsPerPage.posts_per_page_posts_per_page, WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
			$pages[] = self::page_option( $page );
		}

		return [
			'menu'  => $menu,
			'cards' => $cards,
			'pages' => $pages,
			'icons' => DashboardSettings::PAGE_ICONS,
		];
	}

	/**
	 * Open the learn page's blocks in the block editor, or go back to
	 * Academy's arrangement.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function learn_layout( $request ) {
		if ( 'reset' === $request->get_param( 'do' ) ) {
			\Academy\LearnPage\Layout::reset();

			return rest_ensure_response( \Academy\LearnPage\Layout::state() );
		}
		$post = \Academy\LearnPage\Layout::ensure();
		if ( ! $post ) {
			return new \WP_Error( 'academy_learn_layout', __( 'The learn page could not be opened in the block editor.', 'academy' ), [ 'status' => 500 ] );
		}

		return rest_ensure_response( \Academy\LearnPage\Layout::state() );
	}

	/**
	 * A new page for the dashboard menu. It is private, so only the
	 * dashboard shows it; it is edited like any other page.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function dashboard_page( $request ) {
		$label = (string) $request->get_param( 'label' );
		$id    = wp_insert_post(
			[
				'post_type'    => 'page',
				'post_status'  => 'private',
				'post_title'   => '' !== $label ? $label : __( 'Dashboard page', 'academy' ),
				'post_content' => '<!-- wp:paragraph --><p>' . esc_html__( 'Add blocks or shortcodes here. People see this page inside their dashboard.', 'academy' ) . '</p><!-- /wp:paragraph -->',
			],
			true
		);
		if ( is_wp_error( $id ) ) {
			return new \WP_Error( 'academy_dashboard_page', __( 'The page could not be created.', 'academy' ), [ 'status' => 500 ] );
		}

		return rest_ensure_response( self::page_option( get_post( $id ) ) );
	}

	/**
	 * A page as the Customize screen lists it.
	 *
	 * @param \WP_Post $post Page.
	 * @return array
	 */
	private static function page_option( $post ) {
		return [
			'id'      => (int) $post->ID,
			// The raw title: get_the_title() adds "Private:" and the screen marks those itself.
			'title'   => '' !== $post->post_title ? html_entity_decode( $post->post_title, ENT_QUOTES, 'UTF-8' ) : sprintf( '#%d', $post->ID ),
			'status'  => $post->post_status,
			'editUrl' => (string) get_edit_post_link( $post->ID, 'raw' ),
		];
	}

	/**
	 * Open the dashboard's blocks in the block editor, or go back to
	 * Academy's arrangement.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function dashboard_layout( $request ) {
		if ( 'reset' === $request->get_param( 'do' ) ) {
			\Academy\FrontendDashboard\Layout::reset();

			return rest_ensure_response( \Academy\FrontendDashboard\Layout::state() );
		}
		$post = \Academy\FrontendDashboard\Layout::ensure();
		if ( ! $post ) {
			return new \WP_Error( 'academy_dashboard_layout', __( 'The dashboard could not be opened in the block editor.', 'academy' ), [ 'status' => 500 ] );
		}

		return rest_ensure_response( \Academy\FrontendDashboard\Layout::state() );
	}

	/**
	 * Keep the design someone is editing, so the preview can show it.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function update_preview( $request ) {
		$design  = Settings::sanitize( (array) $request->get_param( 'design' ) );
		$palette = $request->get_param( 'palette' );
		$display = $request->get_param( 'display' );
		$learn   = $request->get_param( 'learn' );
		Preview::save_learn_draft( is_array( $learn ) ? LearnSettings::sanitize( $learn ) : null );
		$dashboard = $request->get_param( 'dashboard' );
		Preview::save_dashboard_draft( is_array( $dashboard ) ? DashboardSettings::sanitize( $dashboard ) : null );
		Preview::save_draft(
			$design,
			is_array( $palette ) ? $palette : null,
			rest_sanitize_boolean( $request->get_param( 'themeSync' ) ),
			is_array( $display ) ? Settings::sanitize_display( $display ) : null
		);

		return rest_ensure_response( [ 'saved' => true ] );
	}

	/**
	 * Save the design.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function update_value( $request ) {
		$design  = (array) $request->get_param( 'design' );
		$palette = $request->get_param( 'palette' );
		$sync    = $request->get_param( 'themeSync' );

		// Order matters: turning theme syncing on hands the saved palette over,
		// so the palette is saved first.
		if ( is_array( $palette ) ) {
			Palette::save( $palette );
		}
		if ( null !== $sync ) {
			Palette::set_theme_sync( rest_sanitize_boolean( $sync ) && Palette::theme_can_sync() );
		}
		$display = $request->get_param( 'display' );
		if ( is_array( $display ) ) {
			Settings::save_display( $display );
		}
		$learn = $request->get_param( 'learn' );
		if ( is_array( $learn ) ) {
			LearnSettings::save( $learn );
		}
		$dashboard = $request->get_param( 'dashboard' );
		if ( is_array( $dashboard ) ) {
			DashboardSettings::save( $dashboard );
		}

		return rest_ensure_response(
			[
				'design'    => Settings::save( $design ),
				'pieces'    => Templates::pieces(),
				'templates' => Templates::state(),
				'outline'   => self::outlines(),
				'colors'    => self::colors(),
				'display'   => Settings::display(),
				'mode'      => Settings::mode(),
				'learn'     => LearnSettings::get(),
				'dashboard' => DashboardSettings::get(),
				'preview'   => Preview::urls(),
			]
		);
	}

	/**
	 * Where to edit a part of a course page, making it editable first if it isn't.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function edit_piece( $request ) {
		$piece   = sanitize_key( (string) $request->get_param( 'piece' ) );
		$pattern = Patterns::ensure( $piece );
		if ( ! $pattern ) {
			return new \WP_Error( 'academy_design_piece', __( 'This part cannot be opened in the block editor.', 'academy' ), [ 'status' => 400 ] );
		}

		return rest_ensure_response(
			[
				'editUrl' => Patterns::edit_url( $pattern ),
				'pieces'  => Templates::pieces(),
				'outline' => self::outlines(),
			]
		);
	}

	/**
	 * Drop a template's block editor copy, so it follows the Design screen again.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function create_value( $request ) {
		$slug  = sanitize_key( (string) $request->get_param( 'template' ) );
		$piece = sanitize_key( (string) $request->get_param( 'piece' ) );
		$reset = $piece ? Patterns::reset( $piece ) : Templates::reset( $slug );

		return rest_ensure_response(
			[
				'reset'     => $reset,
				'pieces'    => Templates::pieces(),
				'templates' => Templates::state(),
				'outline'   => self::outlines(),
			]
		);
	}

	/**
	 * Not used.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function delete_value( $request ) {
		return rest_ensure_response( [] );
	}

	/**
	 * Only people who can manage the site may design course pages.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return bool
	 */
	public function permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}
}
