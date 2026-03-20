<?php
namespace Academy\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_REST_Server;
use WP_REST_Request;

class CourseFilterHandler {

	public static function init() {
		$self = new self();
		add_action( 'rest_api_init', array( $self, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			ACADEMY_PLUGIN_SLUG . '/v1',
			'/courses-filter',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'archive_course_filter' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'search' => array(
						'required'          => false,
						'type'              => 'string',
						'description'       => 'Search term for filtering courses.',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'category' => array(
						'required'          => false,
						'type'              => 'array',
						'description'       => 'Array of category slugs to filter courses.',
						'sanitize_callback' => function( $value ) {
							return array_map( 'sanitize_text_field', (array) $value );
						},
					),
					'tags' => array(
						'required'          => false,
						'type'              => 'array',
						'description'       => 'Array of tag slugs to filter courses.',
						'sanitize_callback' => function( $value ) {
							return array_map( 'sanitize_text_field', (array) $value );
						},
					),
					'levels' => array(
						'required'          => false,
						'type'              => 'array',
						'description'       => 'Array of course levels to filter courses.',
						'sanitize_callback' => function( $value ) {
							return array_map( 'sanitize_text_field', (array) $value );
						},
					),
					'type' => array(
						'required'          => false,
						'type'              => 'array',
						'description'       => 'Array of course types to filter courses.',
						'sanitize_callback' => function( $value ) {
							return array_map( 'sanitize_text_field', (array) $value );
						},
					),
					'orderby' => array(
						'required'          => false,
						'type'              => 'string',
						'description'       => 'Field to order courses by.',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'paged' => array(
						'required'          => false,
						'type'              => 'integer',
						'description'       => 'Page number for pagination.',
						'sanitize_callback' => 'absint',
					),
					'per_row' => array(
						'required'          => false,
						'type'              => 'integer',
						'description'       => 'Number of courses per row for grid display.',
						'sanitize_callback' => 'absint',
					),
					'per_page' => array(
						'required'          => false,
						'type'              => 'integer',
						'description'       => 'Number of courses per page for pagination.',
						'sanitize_callback' => 'absint',
					),
					'ids' => array(
						'required'          => false,
						'type'              => 'string',
						'description'       => 'Comma-separated list of course IDs to include.',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'exclude_ids' => array(
						'required'          => false,
						'type'              => 'string',
						'description'       => 'String of course IDs to exclude.',
						'sanitize_callback' => 'sanitize_text_field'
					),
					'count' => array(
						'required'          => false,
						'type'              => 'integer',
						'description'       => 'Number of courses to return (overrides per_page).',
						'sanitize_callback' => 'absint',
					),
					'cat_not_in' => array(
						'required'          => false,
						'type'              => 'array',
						'description'       => 'Array of category slugs to exclude from filtering.',
						'sanitize_callback' => function( $value ) {
							return array_map( 'sanitize_text_field', (array) $value );
						},
					),
					'tag_not_in' => array(
						'required'          => false,
						'type'              => 'array',
						'description'       => 'Array of tag slugs to exclude from filtering.',
						'sanitize_callback' => function( $value ) {
							return array_map( 'sanitize_text_field', (array) $value );
						},
					),
				),
			)
		);

		register_rest_route(
			ACADEMY_PLUGIN_SLUG . '/v1',
			'/search-courses',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'search_form_handler' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'keyword' => array(
						'required'          => false,
						'type'              => 'string',
						'description'       => 'Search term for filtering courses.',
						'sanitize_callback' => 'sanitize_text_field',
					)
				),
			)
		);
	}

	public function archive_course_filter( WP_REST_Request $request ) {
		$payload     = $request->get_params();
		$search      = ( isset( $payload['search'] ) ? $payload['search'] : '' );
		$category    = ( isset( $payload['category'] ) ? $payload['category'] : [] );
		$cat_not_in  = ( isset( $payload['cat_not_in'] ) ? $payload['cat_not_in'] : [] );
		$tags        = ( isset( $payload['tags'] ) ? $payload['tags'] : [] );
		$tag_not_in  = ( isset( $payload['tag_not_in'] ) ? $payload['tag_not_in'] : [] );
		$levels      = ( isset( $payload['levels'] ) ? $payload['levels'] : [] );
		$type        = ( isset( $payload['type'] ) ? $payload['type'] : [] );
		$orderby     = ( isset( $payload['orderby'] ) ? $payload['orderby'] : 'DESC' );
		$paged       = ( isset( $payload['paged'] ) ) ? $payload['paged'] : 1;
		$ids         = ( isset( $payload['ids'] ) ? $payload['ids'] : [] );
		$exclude_ids = ( isset( $payload['exclude_ids'] ) ? $payload['exclude_ids'] : [] );
		$count       = ( isset( $payload['count'] ) ? $payload['count'] : 0 );
		$per_row     = ( isset( $payload['per_row'] ) ? array(
			'desktop' => $payload['per_row'],
			'tablet'  => 2,
			'mobile'  => 1
		) : \Academy\Helper::get_settings( 'course_archive_courses_per_row', array(
			'desktop' => 3,
			'tablet'  => 2,
			'mobile'  => 1
		) ) );
		$per_page = ( isset( $payload['per_page'] ) ? $payload['per_page'] : (int) \Academy\Helper::get_settings( 'course_archive_courses_per_page', 12 ) );
		if ( $count ) {
			$per_page = $count;
		}
		if ( $cat_not_in || $tag_not_in ) {
			$category = array_diff( $category, $cat_not_in );
			$tags = array_diff( $tags, $tag_not_in );
		}
		$args = \Academy\Helper::prepare_course_search_query_args(
			[
				'search'         => $search,
				'category'       => $category,
				'tags'           => $tags,
				'levels'         => $levels,
				'type'           => $type,
				'paged'          => $paged,
				'orderby'        => $orderby,
				'posts_per_page' => $per_page,
			]
		);

		if ( $ids || $exclude_ids ) {
			$page_num = $paged - 1;
			$ids = $ids ? (array) explode( ',', $ids ) : [];
			$exclude_ids = $exclude_ids ? (array) explode( ',', $exclude_ids ) : [];
			$ids = array_diff( $ids, $exclude_ids );
			$found_posts = (int) count( $ids );
			$count = $count ?? 0;
			if ( $count && $found_posts > $count ) {
				$ids = array_slice( $ids, - ( $found_posts - ( $count * $page_num ) ) );
			}
			$args['post_type'] = [
				'academy_courses'
			];
			$args['post__in'] = $ids;
			$args['paged'] = $page_num;
		}
		$grid_class = \Academy\Helper::get_responsive_column( $per_row );
		// phpcs:ignore WordPress.WP.DiscouragedFunctions.query_posts_query_posts
		wp_reset_query();
		wp_reset_postdata();
		// remove empty values
		if ( isset( $args['tax_query'] ) ) {
			foreach ( $args['tax_query'] as $i => $tax ) {
				if ( isset( $tax['terms'] ) ) {

					$tax['terms'] = array_filter( $tax['terms'], function( $t ) {
						return ! empty( trim( $t ) );
					});

					// If terms array is empty, remove filter
					if ( empty( $tax['terms'] ) ) {
						unset( $args['tax_query'][ $i ] );
					} else {
						$args['tax_query'][ $i ] = $tax;
					}
				}
			}

			// re-index tax_query
			$args['tax_query'] = array_values( $args['tax_query'] );
		}
		$courses_query = new \WP_Query( apply_filters( 'academy_courses_filter_args', $args ) );

		if ( isset( $found_posts ) && ! empty( $found_posts ) ) {
			$courses_query->max_num_pages = ceil( $found_posts / $count );
		}
		ob_start();
		?>
		<div class="academy-row">
			<?php
			if ( $courses_query->have_posts() ) {
				// Load posts loop.
				while ( $courses_query->have_posts() ) {
					$courses_query->the_post();
					/**
					 * Hook: academy/templates/course_loop.
					 */
					do_action( 'academy/templates/course_loop' );
					\Academy\Helper::get_template( 'content-course.php', array( 'grid_class' => $grid_class ) );
				}
				\Academy\Helper::get_template( 'archive/pagination.php', array(
					'paged' => $paged,
					'max_num_pages' => $courses_query->max_num_pages,
				) );
				wp_reset_query();
				wp_reset_postdata();
			} else {
				\Academy\Helper::get_template( 'archive/course-none.php' );
			}
			?>
		</div>
		<?php
		$markup = ob_get_clean();
		return new \WP_REST_Response(
			[
				'markup'      => apply_filters( 'academy/course_filter_markup', $markup ),
				'found_posts' => $courses_query->found_posts,
			],
			200
		);
	}

	public function search_form_handler( WP_REST_Request $request ) {
		$keyword = $request->get_param( 'keyword' );

		if ( null !== $keyword ) {
			$keyword = sanitize_text_field( $keyword );
		} else {
			$keyword = '';
		}
		$args = array(
			'posts_per_page' => 5,
			's' => $keyword,
			'post_type' => 'academy_courses',
		);
		$query = new \WP_Query( apply_filters( ' academy/course_search_query_args', $args ) );
		$item_markup = '';
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) :
				$query->the_post();

				$item_markup .= '<li>
								<a href="' . get_the_permalink() . '">' .

									// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
									'<img src="' . esc_url( \Academy\Helper::get_the_course_thumbnail_url( 'academy_thumbnail' ) ) . '">
									' . get_the_title() . '
								</a>
							</li>';
			endwhile;
			wp_reset_postdata();
		} else {
			$item_markup = '<li><span>' . esc_html__( 'No course found', 'academy' ) . '</span></li>';
		}

		return new \WP_REST_Response(
			[
				'markup' => '<ul class="academy-search-results' . ( $query->found_posts > 3 ? ' scrollbar' : '' ) . '">' . $item_markup . '</ul>',
			],
			200
		);
	}

}
