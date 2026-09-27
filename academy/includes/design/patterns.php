<?php
namespace Academy\Design;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The parts of a course page people can open in the block editor on any theme.
 *
 * The course card and the course page layout are kept as synced patterns, so
 * the normal block editor can edit them even when the theme has no Site Editor.
 * Once a part has a pattern, the templates use it instead of building that part
 * from the Design screen's switches.
 */
class Patterns {

	/**
	 * Option holding the pattern post IDs.
	 */
	/**
	 * Key in the design state option: part => pattern post ID.
	 */
	const KEY = 'patterns';

	/**
	 * Whether a pattern is being created right now, so the templates hand back
	 * what the design builds instead of the pattern being seeded from it.
	 *
	 * @var bool
	 */
	public static $seeding = false;

	/**
	 * The parts that can be edited this way.
	 *
	 * @return array Key => label.
	 */
	public static function pieces() {
		return [
			'card' => __( 'Course card', 'academy' ),
			'page' => __( 'Course page layout', 'academy' ),
		];
	}

	/**
	 * Whole course pages that can be edited this way, on themes without a Site
	 * Editor. Key => template slug.
	 *
	 * @return array
	 */
	public static function template_pieces() {
		return [
			'catalog' => 'archive-academy_courses',
			'course'  => 'single-academy_courses',
		];
	}

	/**
	 * Every editable part, with its label.
	 *
	 * @return array Key => label.
	 */
	public static function all_pieces() {
		return self::pieces() + [
			'catalog' => __( 'Course catalog', 'academy' ),
			'course'  => __( 'Course page', 'academy' ),
		];
	}

	/**
	 * The pattern that has taken over a whole template, if there is one.
	 *
	 * @param string $slug Template slug.
	 * @return \WP_Post|null
	 */
	public static function template_post( $slug ) {
		$piece = array_search( $slug, self::template_pieces(), true );

		return $piece ? self::post( $piece ) : null;
	}

	/**
	 * The pattern for a part, if it has one.
	 *
	 * @param string $piece card|page.
	 * @return \WP_Post|null
	 */
	public static function post( $piece ) {
		$ids  = (array) \Academy\Options::get( \Academy\Options::DESIGN_STATE, self::KEY, [] );
		$post = isset( $ids[ $piece ] ) ? get_post( (int) $ids[ $piece ] ) : null;

		if ( ! $post || 'wp_block' !== $post->post_type || 'trash' === $post->post_status ) {
			return null;
		}

		return $post;
	}

	/**
	 * The pattern for a part, created from the current design when it has none.
	 *
	 * @param string $piece card|page.
	 * @return \WP_Post|null
	 */
	public static function ensure( $piece ) {
		$labels = self::all_pieces();
		if ( ! isset( $labels[ $piece ] ) ) {
			return null;
		}
		$existing = self::post( $piece );
		if ( $existing ) {
			return $existing;
		}

		$generator = new Generator();
		$design    = Settings::get();
		$templates = self::template_pieces();
		if ( isset( $templates[ $piece ] ) ) {
			$content = self::template_seed( $templates[ $piece ] );
		} else {
			$content = 'card' === $piece ? $generator->card_markup( $design ) : $generator->section_markup( $design );
		}
		if ( '' === trim( (string) $content ) ) {
			return null;
		}

		$id = wp_insert_post(
			[
				'post_type'    => 'wp_block',
				'post_status'  => 'publish',
				/* translators: %s: name of the part of a course page, e.g. Course card. */
				'post_title'   => sprintf( __( 'Academy: %s', 'academy' ), $labels[ $piece ] ),
				'post_content' => $content,
			],
			true
		);
		if ( is_wp_error( $id ) ) {
			return null;
		}

		$ids           = (array) \Academy\Options::get( \Academy\Options::DESIGN_STATE, self::KEY, [] );
		$ids[ $piece ] = (int) $id;
		\Academy\Options::set( \Academy\Options::DESIGN_STATE, self::KEY, $ids );

		return get_post( $id );
	}

	/**
	 * Drop a part's pattern, so the Design screen's switches build it again.
	 *
	 * @param string $piece card|page.
	 * @return bool Whether a pattern was removed.
	 */
	public static function reset( $piece ) {
		$post = self::post( $piece );
		if ( ! $post ) {
			return false;
		}
		wp_delete_post( $post->ID, true );

		$ids = (array) \Academy\Options::get( \Academy\Options::DESIGN_STATE, self::KEY, [] );
		unset( $ids[ $piece ] );
		\Academy\Options::set( \Academy\Options::DESIGN_STATE, self::KEY, $ids );

		return true;
	}

	/**
	 * What a whole template looks like today: the design applied to Academy's
	 * template, without the header and footer, which the theme provides.
	 *
	 * @param string $slug Template slug.
	 * @return string
	 */
	private static function template_seed( $slug ) {
		self::$seeding = true;
		$template      = get_block_template( ACADEMY_PLUGIN_SLUG . '//' . $slug );
		self::$seeding = false;

		if ( ! $template || '' === trim( (string) $template->content ) ) {
			return '';
		}

		$blocks = array_filter(
			parse_blocks( $template->content ),
			function ( $block ) {
				if ( 'core/template-part' !== $block['blockName'] ) {
					return true;
				}
				$slug = isset( $block['attrs']['slug'] ) ? $block['attrs']['slug'] : '';
				$tag  = isset( $block['attrs']['tagName'] ) ? $block['attrs']['tagName'] : '';

				return ! in_array( $slug, [ 'header', 'footer' ], true ) && ! in_array( $tag, [ 'header', 'footer' ], true );
			}
		);

		return serialize_blocks( array_values( $blocks ) );
	}

	/**
	 * Where to edit a pattern.
	 *
	 * @param \WP_Post $post Pattern.
	 * @return string
	 */
	public static function edit_url( $post ) {
		return admin_url( 'post.php?post=' . (int) $post->ID . '&action=edit' );
	}

	/**
	 * A synced pattern block, for the templates.
	 *
	 * @param \WP_Post $post Pattern.
	 * @return array Parsed blocks.
	 */
	public static function block( $post ) {
		return parse_blocks( self::block_markup( $post ) );
	}

	/**
	 * A synced pattern block, as markup.
	 *
	 * @param \WP_Post $post Pattern.
	 * @return string
	 */
	public static function block_markup( $post ) {
		return sprintf( '<!-- wp:block {"ref":%d} /-->', (int) $post->ID );
	}
}
