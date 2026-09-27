<?php
namespace Academy;

use Academy\LearnPage\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The block learn page: the page a student takes a course on, built from
 * Academy blocks and made interactive with the Interactivity API.
 *
 * Every topic is its own server-rendered URL (/course/{course}/{type}/{topic}),
 * and moving between topics swaps only the content, so it feels as quick as a
 * single-page app while third-party shortcodes and blocks inside lessons keep
 * working.
 */
class LearnPage {

	/**
	 * Script module shared by the learn page blocks.
	 */
	const SCRIPT_MODULE = '@academy/learn-page';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'body_class', [ __CLASS__, 'body_class' ] );
		add_filter( 'pre_handle_404', [ __CLASS__, 'not_a_404' ], 10, 2 );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_styles' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'dequeue_theme_scripts' ], 100 );
	}

	/**
	 * Whether the block learn page is switched on.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return 'blocks' === Settings::get()['engine'];
	}

	/**
	 * Whether this request is a topic on the block learn page.
	 *
	 * @return bool
	 */
	public static function is_request() {
		return self::is_enabled() && '' !== (string) get_query_var( 'curriculum_type' ) && '' !== (string) get_query_var( 'name' );
	}

	/**
	 * The blocks the page is built from.
	 *
	 * @return string Block markup.
	 */
	public static function template() {
		/**
		 * Filters the block markup of the learn page.
		 *
		 * @param string $markup Block markup.
		 */
		return (string) apply_filters( 'academy/learn_page/template', LearnPage\Layout::markup() );
	}

	/**
	 * Academy's own arrangement of the learn page blocks.
	 *
	 * @return string Block markup.
	 */
	public static function default_template() {
		return '<!-- wp:academy/learn-page -->'
			. '<!-- wp:academy/learn-topbar /-->'
			. '<!-- wp:academy/learn-curriculum /-->'
			. '<!-- wp:academy/learn-content /-->'
			. '<!-- wp:academy/learn-footer /-->'
			. '<!-- /wp:academy/learn-page -->';
	}

	/**
	 * A topic's page is not a post WordPress can find by its slug (lessons live
	 * in their own table), so WordPress would answer 404. A topic that exists
	 * is a real page: answer 200, which client-side navigation also relies on.
	 *
	 * @param bool      $preempt  Whether to skip WordPress's own 404 handling.
	 * @param \WP_Query $wp_query Main query.
	 * @return bool
	 */
	public static function not_a_404( $preempt, $wp_query ) {
		if ( $preempt || ! self::is_request() ) {
			return $preempt;
		}
		$data = LearnPage\Data::current();
		if ( ! $data['courseId'] || ! $data['topicId'] ) {
			return $preempt;
		}
		status_header( 200 );
		$wp_query->is_404 = false;

		// The topic's slug arrives as the `name` query var, so WordPress treats
		// the request as a single post and then finds none. Anything that asks
		// for the queried post (body_class() in core, themes, SEO and community
		// plugins) reads a property of null and prints PHP warnings into the
		// page. The page belongs to the course, so make that the queried post.
		$course = get_post( (int) $data['courseId'] );
		if ( $course instanceof \WP_Post ) {
			$wp_query->queried_object    = $course;
			$wp_query->queried_object_id = (int) $course->ID;
		}

		return true;
	}

	/**
	 * Mark the page for styling.
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function body_class( $classes ) {
		if ( self::is_request() ) {
			$classes[] = 'academy-learn-body';
			if ( LearnPage\Settings::get()['themeChrome'] ) {
				$classes[] = 'academy-learn-body--theme';
			}
		}

		return $classes;
	}

	/**
	 * Load the page's stylesheet and script in the head, before the blocks
	 * render, so the layout does not jump.
	 *
	 * @return void
	 */
	public static function enqueue_styles() {
		if ( self::is_request() ) {
			wp_enqueue_style( 'academy-learn-page-style' );
			// Before the head prints the import map, so the page can load the
			// router when a student moves to another topic.
			wp_enqueue_script_module( 'academy-learn-page-view-script-module' );
		}
	}

	/**
	 * The page draws its own full-screen layout; floating widgets pinned to the
	 * window by other plugins sit on top of the lesson otherwise.
	 *
	 * @return void
	 */
	public static function dequeue_theme_scripts() {
		if ( ! self::is_request() ) {
			return;
		}
		wp_dequeue_style( 'storeengine-floating-cart' );
	}
}
