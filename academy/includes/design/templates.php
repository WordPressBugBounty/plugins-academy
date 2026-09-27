<?php
namespace Academy\Design;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The course templates behind the Design screen: where to edit them, where to
 * see them, and whether someone has taken one over in the block editor.
 */
class Templates {

	/**
	 * The templates the Design screen builds.
	 *
	 * @return array Slug => label.
	 */
	public static function all() {
		return [
			'archive-academy_courses' => __( 'Course catalog', 'academy' ),
			'single-academy_courses'  => __( 'Course page', 'academy' ),
		];
	}

	/**
	 * What the screen shows for each template.
	 *
	 * @return array[]
	 */
	public static function state() {
		$course = get_posts(
			[
				'post_type'      => 'academy_courses',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			]
		);
		$view   = [
			'archive-academy_courses' => (string) get_post_type_archive_link( 'academy_courses' ),
			'single-academy_courses'  => $course ? (string) get_permalink( $course[0] ) : '',
		];

		// The Site Editor only edits templates for block themes; classic themes
		// still get the design, they just cannot open it block by block.
		$editable = \Academy\Helper::is_fse_theme();

		$pieces = array_flip( Patterns::template_pieces() );

		$state = [];
		foreach ( self::all() as $slug => $label ) {
			$custom  = self::custom_template( $slug );
			$piece   = $editable || ! isset( $pieces[ $slug ] ) ? '' : $pieces[ $slug ];
			$state[] = [
				'slug'       => $slug,
				'label'      => $label,
				'customized' => $piece ? (bool) Patterns::post( $piece ) : (bool) $custom,
				'editable'   => $editable,
				'piece'      => $piece,
				'editUrl'    => admin_url( 'site-editor.php?p=' . rawurlencode( '/wp_template/' . ( $custom ? get_stylesheet() : ACADEMY_PLUGIN_SLUG ) . '//' . $slug ) . '&canvas=edit' ),
				'viewUrl'    => $view[ $slug ],
			];
		}

		return $state;
	}

	/**
	 * The parts that open in the block editor on any theme.
	 *
	 * @return array[]
	 */
	public static function pieces() {
		$pieces = [];
		foreach ( Patterns::pieces() as $key => $label ) {
			$pattern  = Patterns::post( $key );
			$pieces[] = [
				'piece'      => $key,
				'label'      => $label,
				'customized' => (bool) $pattern,
				'editUrl'    => $pattern ? Patterns::edit_url( $pattern ) : '',
			];
		}

		return $pieces;
	}

	/**
	 * The block editor's own copy of a course template, if someone saved one.
	 *
	 * @param string $slug Template slug.
	 * @return \WP_Post|null
	 */
	public static function custom_template( $slug ) {
		$posts = get_posts(
			[
				'post_type'      => 'wp_template',
				'post_status'    => [ 'publish', 'draft', 'auto-draft' ],
				'name'           => $slug,
				'posts_per_page' => 1,
				'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					[
						'taxonomy' => 'wp_theme',
						'field'    => 'name',
						'terms'    => get_stylesheet(),
					],
				],
			]
		);

		return $posts ? $posts[0] : null;
	}

	/**
	 * Drop the block editor's copy, so the template follows the Design screen again.
	 *
	 * @param string $slug Template slug.
	 * @return bool Whether a template was removed.
	 */
	public static function reset( $slug ) {
		if ( ! isset( self::all()[ $slug ] ) ) {
			return false;
		}
		$custom = self::custom_template( $slug );
		if ( ! $custom ) {
			return false;
		}
		wp_delete_post( $custom->ID, true );

		return true;
	}
}
