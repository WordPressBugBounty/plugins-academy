<?php
namespace Academy\Blocks;

use Academy\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Course filters and sorting from the URL, shared by the Course Filters and
 * Course Sort blocks and applied to course Query Loops and course archives.
 *
 * Filters are plain GET parameters, so filtered pages can be shared and
 * bookmarked and work without JavaScript.
 */
class CourseFilters {

	/**
	 * GET parameter names.
	 */
	const PARAMS = [
		'category' => 'academy_category',
		'level'    => 'academy_level',
		'type'     => 'academy_type',
		'search'   => 'academy_search',
		'sort'     => 'academy_sort',
	];

	/**
	 * Current filter values from the request.
	 *
	 * @return array{category: string[], level: string[], type: string[], search: string, sort: string}
	 */
	public static function current() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only public filters.
		$list = function ( $param, $sanitize ) {
			$raw = isset( $_GET[ $param ] ) ? wp_unslash( $_GET[ $param ] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below.
			return array_values( array_filter( array_map( $sanitize, (array) $raw ) ) );
		};

		$values = [
			'category' => $list( self::PARAMS['category'], 'sanitize_title' ),
			'level'    => array_values( array_intersect( $list( self::PARAMS['level'], 'sanitize_key' ), array_keys( self::levels() ) ) ),
			'type'     => array_values( array_intersect( $list( self::PARAMS['type'], 'sanitize_key' ), array_keys( self::types() ) ) ),
			'search'   => isset( $_GET[ self::PARAMS['search'] ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::PARAMS['search'] ] ) ) : '',
			'sort'     => isset( $_GET[ self::PARAMS['sort'] ] ) ? sanitize_key( wp_unslash( $_GET[ self::PARAMS['sort'] ] ) ) : '',
		];
		// phpcs:enable

		if ( ! isset( self::sort_options()[ $values['sort'] ] ) ) {
			$values['sort'] = '';
		}

		return $values;
	}

	/**
	 * Whether any filter (not counting sorting) is active.
	 *
	 * @param array $values Values from current().
	 * @return bool
	 */
	public static function has_filters( array $values ) {
		return $values['category'] || $values['level'] || $values['type'] || '' !== $values['search'];
	}

	/**
	 * Sorting choices.
	 *
	 * @return array Key => label.
	 */
	public static function sort_options() {
		return (array) apply_filters(
			'academy/blocks/course_sort_options',
			[
				'newest'     => __( 'Newest', 'academy' ),
				'oldest'     => __( 'Oldest', 'academy' ),
				'title'      => __( 'Title: A to Z', 'academy' ),
				'title_desc' => __( 'Title: Z to A', 'academy' ),
				'popular'    => __( 'Most students', 'academy' ),
				'rating'     => __( 'Top rated', 'academy' ),
				'price_low'  => __( 'Price: low to high', 'academy' ),
				'price_high' => __( 'Price: high to low', 'academy' ),
				'menu_order' => __( 'Custom order', 'academy' ),
			]
		);
	}

	/**
	 * Which sort_options() key the site's configured archive order (Settings →
	 * Course → Course Archive Order, the older classic-archive setting) reads
	 * as, so the Course Sort block can show it as selected when the visitor
	 * hasn't picked one via the URL. Deliberately display-only: the query
	 * itself is already sorted correctly by Frontend\Template::pre_get_posts
	 * reading that same setting directly, so this never feeds back into
	 * query_args() and never changes what's actually queried — only which
	 * option looks selected in a fresh page load.
	 *
	 * @return string A sort_options() key, or '' if the setting has no
	 *                equivalent (Modified Date, ID) — the dropdown then falls
	 *                back to its generic "Default" option, same as before.
	 */
	public static function default_sort() {
		$map = [
			'DESC'       => 'newest',
			'date'       => 'newest',
			'name'       => 'title',
			'menu_order' => 'menu_order',
			'ratings'    => 'rating',
		];
		$order = Helper::get_settings( 'course_archive_courses_order', 'DESC' );

		return $map[ $order ] ?? '';
	}

	/**
	 * Difficulty levels.
	 *
	 * @return array Key => label.
	 */
	public static function levels() {
		return (array) apply_filters(
			'academy/difficulty_level',
			[
				'all_levels'   => __( 'All Levels', 'academy' ),
				'beginner'     => __( 'Beginner', 'academy' ),
				'intermediate' => __( 'Intermediate', 'academy' ),
				'experts'      => __( 'Experts', 'academy' ),
			]
		);
	}

	/**
	 * Price types.
	 *
	 * @return array Key => label.
	 */
	public static function types() {
		return (array) apply_filters(
			'academy/get_course_filter_types',
			[
				'free' => __( 'Free', 'academy' ),
				'paid' => __( 'Paid', 'academy' ),
			]
		);
	}

	/**
	 * WP_Query arguments for filter values.
	 *
	 * @param array $values Values from current().
	 * @return array
	 */
	public static function query_args( array $values ) {
		$args = [];

		if ( $values['category'] ) {
			$args['tax_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				[
					'taxonomy'         => 'academy_courses_category',
					'field'            => 'slug',
					'terms'            => $values['category'],
					'include_children' => true,
				],
			];
		}

		$meta = [];
		if ( $values['level'] ) {
			$meta[] = [
				'key'     => 'academy_course_difficulty_level',
				'value'   => $values['level'],
				'compare' => 'IN',
			];
		}
		if ( $values['type'] ) {
			$meta[] = [
				'key'     => 'academy_course_type',
				'value'   => $values['type'],
				'compare' => 'IN',
			];
		}
		if ( $meta ) {
			$args['meta_query'] = count( $meta ) > 1 ? array_merge( [ 'relation' => 'AND' ], $meta ) : $meta; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		if ( '' !== $values['search'] ) {
			$args['s'] = $values['search'];
		}

		$sorting = [
			'newest'     => [ 'date', 'DESC' ],
			'oldest'     => [ 'date', 'ASC' ],
			'title'      => [ 'title', 'ASC' ],
			'title_desc' => [ 'title', 'DESC' ],
			'menu_order' => [ 'menu_order', 'ASC' ],
		];
		if ( isset( $sorting[ $values['sort'] ] ) ) {
			$args['orderby'] = $sorting[ $values['sort'] ][0];
			$args['order']   = $sorting[ $values['sort'] ][1];
		} elseif ( in_array( $values['sort'], [ 'popular', 'rating', 'price_low', 'price_high' ], true ) ) {
			// Ordered in SQL by sort_clauses().
			$args['orderby']          = 'date';
			$args['order']            = 'DESC';
			$args['academy_sort_key'] = $values['sort'];
		}

		/**
		 * Filters the query arguments built from the course filters.
		 *
		 * @param array $args   WP_Query arguments.
		 * @param array $values Filter values.
		 */
		return (array) apply_filters( 'academy/blocks/course_filter_query_args', $args, $values );
	}

	/**
	 * Apply the filters to course Query Loops (unless the block opted out).
	 *
	 * @param array     $query Query arguments.
	 * @param \WP_Block $block Post Template block.
	 * @return array
	 */
	public static function filter_query_loop( $query, $block ) {
		$settings = isset( $block->context['query'] ) ? (array) $block->context['query'] : [];
		if ( empty( $settings['postType'] ) || ! in_array( $settings['postType'], CourseData::post_types(), true ) ) {
			return $query;
		}

		// "Featured only" means the courses marked Featured in Academy. Core
		// reads WordPress's sticky posts list for this, which Academy does not
		// use, so a featured course never showed up.
		if ( isset( $settings['sticky'] ) && 'only' === $settings['sticky'] ) {
			unset( $query['post__in'], $query['ignore_sticky_posts'] );
			$featured = [
				'key'   => \Academy\Helper::STICKY_META_KEY,
				'value' => '1',
			];
			// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- meta/tax lookup the feature depends on; no cheaper equivalent
			$query['meta_query'] = empty( $query['meta_query'] ) ? [ $featured ] : [
				'relation' => 'AND',
				$query['meta_query'],
				[ $featured ],
			];
			// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		if ( isset( $settings['academyFilters'] ) && false === $settings['academyFilters'] ) {
			return $query;
		}

		$args = self::query_args( self::current() );
		if ( ! $args ) {
			return $query;
		}

		foreach ( [ 'tax_query', 'meta_query' ] as $key ) {
			if ( empty( $args[ $key ] ) ) {
				continue;
			}
			$query[ $key ] = empty( $query[ $key ] ) ? $args[ $key ] : [
				'relation' => 'AND',
				$query[ $key ],
				$args[ $key ],
			];
		}
		foreach ( [ 's', 'orderby', 'order', 'academy_sort_key' ] as $key ) {
			if ( isset( $args[ $key ] ) ) {
				$query[ $key ] = $args[ $key ];
			}
		}

		return $query;
	}

	/**
	 * Apply the filters to the course archive's main query, which archive
	 * templates show with an inherited Query Loop.
	 *
	 * @param \WP_Query $q Query.
	 * @return void
	 */
	public static function filter_main_query( $q ) {
		if ( is_admin() || ! $q->is_main_query() ) {
			return;
		}
		if ( ! $q->is_post_type_archive( 'academy_courses' ) && ! $q->is_tax( [ 'academy_courses_category', 'academy_courses_tag' ] ) ) {
			return;
		}

		$args = self::query_args( self::current() );
		if ( ! $args ) {
			return;
		}

		foreach ( [ 'tax_query', 'meta_query' ] as $key ) {
			if ( empty( $args[ $key ] ) ) {
				continue;
			}
			$existing = $q->get( $key );
			$q->set(
				$key,
				empty( $existing ) ? $args[ $key ] : [
					'relation' => 'AND',
					$existing,
					$args[ $key ],
				]
			);
		}
		if ( isset( $args['s'] ) ) {
			$q->set( 's', $args['s'] );
		}
		if ( isset( $args['orderby'] ) ) {
			$q->set( 'orderby', $args['orderby'] );
			$q->set( 'order', $args['order'] );
			$q->set( Helper::STICKY_QUERY_VAR, false );
		}
		if ( isset( $args['academy_sort_key'] ) ) {
			$q->set( 'academy_sort_key', $args['academy_sort_key'] );
		}
	}

	/**
	 * SQL ordering for sorts WP_Query cannot express: most students, top rated
	 * and price.
	 *
	 * @param array     $clauses Query clauses.
	 * @param \WP_Query $query   Query.
	 * @return array
	 */
	public static function sort_clauses( $clauses, $query ) {
		$key = $query->get( 'academy_sort_key' );
		if ( ! $key ) {
			return $clauses;
		}

		global $wpdb;
		$posts = $wpdb->posts;

		// Table names come from $wpdb; values go through prepare().
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		switch ( $key ) {
			case 'popular':
				$clauses['join']   .= $wpdb->prepare(
					" LEFT JOIN ( SELECT post_parent AS course_id, COUNT(*) AS students FROM {$posts} WHERE post_type = %s AND post_status = %s GROUP BY post_parent ) academy_sort ON academy_sort.course_id = {$posts}.ID",
					'academy_enrolled',
					'completed'
				);
				$clauses['orderby'] = "COALESCE( academy_sort.students, 0 ) DESC, {$posts}.post_date DESC";
				break;
			case 'rating':
				$clauses['join']   .= $wpdb->prepare(
					" LEFT JOIN ( SELECT c.comment_post_ID AS course_id, AVG( cm.meta_value ) AS rating FROM {$wpdb->comments} c INNER JOIN {$wpdb->commentmeta} cm ON cm.comment_id = c.comment_ID AND cm.meta_key = %s WHERE c.comment_type = %s AND c.comment_approved = '1' GROUP BY c.comment_post_ID ) academy_sort ON academy_sort.course_id = {$posts}.ID",
					'academy_rating',
					'academy_courses'
				);
				$clauses['orderby'] = "COALESCE( academy_sort.rating, 0 ) DESC, {$posts}.post_date DESC";
				break;
			case 'price_low':
			case 'price_high':
				$clauses['join']   .= $wpdb->prepare( " LEFT JOIN {$wpdb->postmeta} academy_sort ON academy_sort.post_id = {$posts}.ID AND academy_sort.meta_key = %s", 'academy_course_price' );
				$clauses['orderby'] = 'CAST( COALESCE( academy_sort.meta_value, 0 ) AS DECIMAL( 12, 2 ) ) ' . ( 'price_low' === $key ? 'ASC' : 'DESC' ) . ", {$posts}.post_date DESC";
				break;
		}//end switch
		// phpcs:enable

		return $clauses;
	}

	/**
	 * The category being viewed on a course category archive.
	 *
	 * @return \WP_Term|null
	 */
	public static function current_category() {
		$term = is_tax( 'academy_courses_category' ) ? get_queried_object() : null;

		return $term instanceof \WP_Term ? $term : null;
	}

	/**
	 * Where the filter form goes. On a category archive that is the course
	 * archive, with the category pre-selected, so choosing more categories
	 * widens the results instead of narrowing them to nothing.
	 *
	 * @return string
	 */
	public static function filter_action() {
		if ( self::current_category() ) {
			$archive = get_post_type_archive_link( 'academy_courses' );
			if ( $archive ) {
				return $archive;
			}
		}

		return self::form_action();
	}

	/**
	 * Form action for the current page: its URL without query string or a
	 * page number, so a new filter starts again from page one.
	 *
	 * @return string
	 */
	public static function form_action() {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );

		return home_url( preg_replace( '#/page/\d+/?$#', '/', $path ) );
	}

	/**
	 * Hidden inputs that keep the page's other query parameters (such as
	 * page_id on sites without pretty permalinks) when a form is submitted.
	 *
	 * @param string[] $skip Parameters the form sets itself.
	 * @return string
	 */
	public static function hidden_inputs( array $skip ) {
		$html = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only.
		foreach ( $_GET as $key => $value ) {
			$key = (string) $key;
			if ( in_array( $key, $skip, true ) || 'paged' === $key || preg_match( '/^query-\d+-page$/', $key ) || is_array( $value ) ) {
				continue;
			}
			$html .= sprintf(
				'<input type="hidden" name="%1$s" value="%2$s" />',
				esc_attr( $key ),
				esc_attr( sanitize_text_field( wp_unslash( $value ) ) )
			);
		}

		return $html;
	}

	/**
	 * URL of the current page with all filters removed (sorting is kept).
	 *
	 * @return string
	 */
	public static function clear_url() {
		$keys = [ self::PARAMS['category'], self::PARAMS['level'], self::PARAMS['type'], self::PARAMS['search'], 'paged' ];
		$url  = remove_query_arg( $keys );

		return (string) preg_replace( '#/page/\d+/?(\?|$)#', '/$1', $url );
	}
}
