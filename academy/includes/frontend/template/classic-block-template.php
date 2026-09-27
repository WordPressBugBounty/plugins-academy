<?php
namespace Academy\Frontend\Template;

use Academy\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block templates for course pages in classic themes.
 *
 * A classic theme opts in with the `academy/templates/use_block_templates`
 * filter. The theme keeps its own header and footer (get_header() and
 * get_footer()); the page body comes from Academy's block template, or from
 * the version saved in the Site Editor.
 */
class ClassicBlockTemplate {

	/**
	 * Rendered template body for this request.
	 *
	 * @var string|null
	 */
	private static $html = null;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		// After the PHP template loader (10), before the lesson page loader (99).
		add_filter( 'template_include', [ __CLASS__, 'template_include' ], 20 );
	}

	/**
	 * Block template slug for the current request.
	 *
	 * @return string '' when the request is not a course page with a block template.
	 */
	public static function template_slug() {
		if ( is_singular( 'academy_courses' ) ) {
			return 'single-academy_courses';
		}
		if ( is_tax( 'academy_courses_category' ) ) {
			return 'taxonomy-academy_courses_category';
		}
		if ( is_post_type_archive( 'academy_courses' ) && ! is_search() ) {
			return 'archive-academy_courses';
		}

		return '';
	}

	/**
	 * Use the block template wrapper for course pages.
	 *
	 * @param string $template Template path.
	 * @return string
	 */
	public static function template_include( $template ) {
		if ( is_embed() || Helper::is_fse_theme() || ! Helper::use_block_templates() ) {
			return $template;
		}
		if ( 'academy_courses' === get_query_var( 'post_type' ) && in_array( get_query_var( 'source' ), [ 'lessons', 'curriculums' ], true ) ) {
			return $template;
		}

		$slug = self::template_slug();
		if ( '' === $slug ) {
			return $template;
		}

		$block_template = self::find( $slug );
		if ( ! $block_template || '' === trim( (string) $block_template->content ) ) {
			return $template;
		}

		// Render before wp_head(), so layout rules and block styles print in the head.
		self::$html = self::render( $block_template->content );

		return Helper::plugin_path() . 'templates/block-template-classic.php';
	}

	/**
	 * The template saved in the Site Editor for this theme, or Academy's own.
	 *
	 * @param string $slug Template slug.
	 * @return \WP_Block_Template|null
	 */
	public static function find( $slug ) {
		$saved = get_block_template( get_stylesheet() . '//' . $slug );
		if ( $saved && ! empty( $saved->content ) ) {
			return $saved;
		}

		return get_block_template( ACADEMY_PLUGIN_SLUG . '//' . $slug );
	}

	/**
	 * Render a template body without its header and footer template parts,
	 * which the classic theme provides.
	 *
	 * @param string $content Template content.
	 * @return string
	 */
	private static function render( $content ) {
		$blocks = array_filter(
			parse_blocks( $content ),
			function ( $block ) {
				if ( 'core/template-part' !== $block['blockName'] ) {
					return true;
				}
				$slug = isset( $block['attrs']['slug'] ) ? $block['attrs']['slug'] : '';
				$tag  = isset( $block['attrs']['tagName'] ) ? $block['attrs']['tagName'] : '';

				return ! in_array( $slug, [ 'header', 'footer' ], true ) && ! in_array( $tag, [ 'header', 'footer' ], true );
			}
		);

		$html = do_blocks( serialize_blocks( $blocks ) );
		$html = wptexturize( $html );
		$html = convert_smilies( $html );

		return wp_filter_content_tags( $html, 'template' );
	}

	/**
	 * Rendered body for the wrapper template.
	 *
	 * @return string
	 */
	public static function content() {
		return (string) self::$html;
	}
}
