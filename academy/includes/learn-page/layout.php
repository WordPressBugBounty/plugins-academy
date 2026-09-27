<?php
namespace Academy\LearnPage;

use Academy\LearnPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The learn page's blocks, as a synced pattern people can open in the block
 * editor on any theme: reorder or remove the top bar, curriculum, content and
 * footer, and style each one. Without a pattern, the page uses Academy's own
 * arrangement.
 */
class Layout {

	/**
	 * Option holding the pattern's post ID.
	 */
	const KEY = 'learn_page_layout';

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
	 * The page's block markup: the pattern's when there is one.
	 *
	 * @return string
	 */
	public static function markup() {
		$post = self::post();
		if ( $post && has_block( 'academy/learn-page', $post->post_content ) ) {
			return $post->post_content;
		}

		return LearnPage::default_template();
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
				'post_title'   => __( 'Academy: Learn page', 'academy' ),
				'post_content' => LearnPage::default_template(),
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
	 * Drop the pattern, so the page uses Academy's arrangement again.
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
