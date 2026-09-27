<?php
namespace Academy\FrontendDashboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The block dashboard's blocks, as a synced pattern people can open in the
 * block editor on any theme. Without one, the dashboard uses Academy's own
 * arrangement.
 */
class Layout {

	/**
	 * Option holding the pattern's post ID.
	 */
	const KEY = 'dashboard_layout';

	/**
	 * Academy's own arrangement: the menu, the top bar, and the home page made
	 * of blocks that can be moved, removed or joined by any other block.
	 *
	 * @return string Block markup.
	 */
	public static function default_markup() {
		return '<!-- wp:academy/dashboard -->'
			. '<!-- wp:academy/dashboard-sidebar /-->'
			. '<!-- wp:academy/dashboard-topbar /-->'
			. '<!-- wp:academy/dashboard-content -->'
			. '<!-- wp:academy/dashboard-welcome /-->'
			. '<!-- wp:academy/dashboard-stats /-->'
			. '<!-- wp:academy/dashboard-continue /-->'
			. '<!-- wp:academy/dashboard-teaching-courses /-->'
			. '<!-- /wp:academy/dashboard-content -->'
			. '<!-- /wp:academy/dashboard -->';
	}

	/**
	 * The pattern, if one was made.
	 *
	 * @return \WP_Post|null
	 */
	public static function post() {
		$post = get_post( (int) \Academy\Options::get( \Academy\Options::DESIGN_STATE, self::KEY, 0 ) );

		return $post && 'wp_block' === $post->post_type && 'trash' !== $post->post_status ? $post : null;
	}

	/**
	 * The dashboard's block markup: the pattern's when there is one.
	 *
	 * @return string
	 */
	public static function markup() {
		$post   = self::post();
		$markup = $post && has_block( 'academy/dashboard', $post->post_content ) ? $post->post_content : self::default_markup();

		/**
		 * Filters the block markup of the block dashboard.
		 *
		 * @param string $markup Block markup.
		 */
		return (string) apply_filters( 'academy/dashboard/template', $markup );
	}

	/**
	 * The pattern, made from Academy's arrangement when there is none yet.
	 *
	 * @return \WP_Post|null
	 */
	public static function ensure() {
		$post = self::post();
		if ( $post ) {
			return $post;
		}
		$id = wp_insert_post(
			[
				'post_type'    => 'wp_block',
				'post_status'  => 'publish',
				'post_title'   => __( 'Academy: Dashboard', 'academy' ),
				'post_content' => self::default_markup(),
			],
			true
		);
		if ( is_wp_error( $id ) ) {
			return null;
		}
		\Academy\Options::set( \Academy\Options::DESIGN_STATE, self::KEY, (int) $id );

		return get_post( $id );
	}

	/**
	 * Drop the pattern, so the dashboard uses Academy's arrangement again.
	 *
	 * @return bool Whether a pattern was removed.
	 */
	public static function reset() {
		$post = self::post();
		\Academy\Options::delete( \Academy\Options::DESIGN_STATE, self::KEY );
		if ( ! $post ) {
			return false;
		}
		wp_delete_post( $post->ID, true );

		return true;
	}

	/**
	 * What the Customize screen shows about the layout.
	 *
	 * @return array
	 */
	public static function state() {
		$post = self::post();

		return [
			'customized' => (bool) $post,
			'editUrl'    => $post ? admin_url( 'post.php?post=' . (int) $post->ID . '&action=edit' ) : '',
		];
	}
}
