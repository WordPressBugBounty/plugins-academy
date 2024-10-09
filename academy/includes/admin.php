<?php
namespace Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin {

	public static function init() {
		$self = new self();
		Admin\Menu::init();
		Admin\Media::init();
		Admin\Notices::init();
		Admin\Setup::init();
		Admin\Export::init();
		$self->dispatch_hooks();
	}
	public function dispatch_hooks() {
		// Add a post display state for special Academy pages.
		add_filter( 'allowed_redirect_hosts', array( $this, 'add_white_listed_redirect_hosts' ) );
		add_filter( 'display_post_states', array( $this, 'add_display_post_states' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'flush_rewrite_rules' ) );
		add_action( 'current_screen', array( $this, 'conditional_loaded' ) );
		add_filter( 'plugin_action_links_' . ACADEMY_PLUGIN_BASENAME, [ $this, 'plugin_action_links' ] );
		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_links' ), 10, 2 );
	}
	public function add_white_listed_redirect_hosts( $hosts ) {
		$hosts[] = 'academylms.net';
		return $hosts;
	}

	/**
	 * Add a post display state for special WC pages in the page list table.
	 *
	 * @param array   $post_states An array of post display states.
	 * @param WP_Post $post        The current post object.
	 */
	public function add_display_post_states( $post_states, $post ) {
		if ( (int) \Academy\Helper::get_settings( 'frontend_dashboard_page' ) === $post->ID ) {
			$post_states['academy_page_for_frontend_dashboard'] = __( 'Academy Frontend Dashboard Page', 'academy' );
		}
		if ( (int) \Academy\Helper::get_settings( 'course_page' ) === $post->ID ) {
			$post_states['academy_page_for_course'] = __( 'Academy Course Page', 'academy' );
		}
		if ( (int) \Academy\Helper::get_settings( 'frontend_instructor_reg_page' ) === $post->ID ) {
			$post_states['academy_instructor_registration_form'] = __( 'Academy Instructor Registration Page', 'academy' );
		}
		if ( (int) \Academy\Helper::get_settings( 'frontend_student_reg_page' ) === $post->ID ) {
			$post_states['academy_student_registration_form'] = __( 'Academy Student Registration Page', 'academy' );
		}
		if ( (int) \Academy\Helper::get_settings( 'password_reset_page' ) === $post->ID ) {
			$post_states['academy_student_registration_form'] = __( 'Academy Password Reset Page', 'academy' );
		}
		if ( (int) \Academy\Helper::get_settings( 'lessons_page' ) === $post->ID ) {
			$post_states['academy_page_for_lessons'] = __( 'Academy Learn Page', 'academy' );
		}
		return $post_states;
	}

	public function flush_rewrite_rules() {
		if ( get_option( 'academy_required_rewrite_flush' ) ) {
			delete_option( 'academy_required_rewrite_flush' );
			flush_rewrite_rules();
		}
	}

	public function conditional_loaded() {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		switch ( $screen->id ) {
			case 'options-permalink':
				Admin\PermalinkSettings::init();
				break;
			case 'users':
			case 'user':
			case 'profile':
			case 'user-edit':
				Admin\User::init();
				break;
			case 'academy-lms_page_academy-get-pro':
				wp_safe_redirect( 'https://academylms.net/pricing/' );
				break;
		}
	}
	public function add_plugin_links( $links, $file ) {
		if ( ACADEMY_PLUGIN_BASENAME !== $file ) {
			return $links;
		}

		$map_block_links = array(
			'docs'    => array(
				'url'        => 'https://academylms.net/docs/',
				'label'      => __( 'Docs', 'academy' ),
				'aria-label' => __( 'View Academy documentation', 'academy' ),
			),
			'video' => array(
				'url'        => 'https://www.youtube.com/@AcademyLMS',
				'label'      => __( 'Video Tutorials', 'academy' ),
				'aria-label' => __( 'See Video Tutorials', 'academy' ),
			),
			'support' => array(
				'url'        => 'https://wordpress.org/support/plugin/academy/',
				'label'      => __( 'Community Support', 'academy' ),
				'aria-label' => __( 'Visit community forums', 'academy' ),
			),
			'review'  => array(
				'url'        => 'https://wordpress.org/support/plugin/academy/reviews/#new-post',
				'label'      => __( 'Rate the plugin ★★★★★', 'academy' ),
				'aria-label' => __( 'Rate the plugin.', 'academy' ),
			),
		);

		foreach ( $map_block_links as $key => $link ) {
			$links[ $key ] = sprintf(
				'<a target="_blank" href="%s" aria-label="%s">%s</a>',
				esc_url( $link['url'] ),
				esc_attr( $link['aria-label'] ),
				esc_html( $link['label'] )
			);
		}

		return $links;
	}
	public function plugin_action_links( $links ) {
		$settings_link = sprintf( '<a href="%1$s">%2$s</a>', admin_url( 'admin.php?page=academy-settings' ), esc_html__( 'Settings', 'academy' ) );

		array_unshift( $links, $settings_link );

		if ( ! defined( 'ACADEMY_PRO_VERSION' ) ) {
			$links['go_pro'] = sprintf( '<a href="%1$s" target="_blank" class="academy-plugins-gopro" style="color: #7b68ee; font-weight: bold;">%2$s</a>', 'https://academylms.net/pricing/', esc_html__( 'Get Academy Pro', 'academy' ) );
		}
		return $links;
	}
}
