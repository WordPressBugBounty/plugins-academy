<?php
namespace  Academy\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AcademyCourses {
	public function __construct() {
		add_shortcode( 'academy_courses', array( $this, 'academy_courses' ) );

	}
	public function academy_courses( $atts, $content = '' ) {

		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract(shortcode_atts(array(
			'ids'               => '',
			'exclude_ids'       => '',
			'category'          => '',
			'cat_not_in'        => '',
			'tag'               => '',
			'tag_not_in'        => '',
			'course_level'      => '',
			'price_type'        => '',
			'orderby'           => '',
			'order'             => '',
			'count'             => '3',
			'column_per_row'    => '3',
			'has_pagination'    => false
		), $atts));

		$args = [
			'post_type'   => 'academy_courses',
			'post_status' => 'publish',
		];

		if ( ! empty( $ids ) ) {
			$ids = (array) explode( ',', $ids );
			$args['post__in'] = $ids;
		}

		if ( ! empty( $exclude_ids ) ) {
			$exclude_ids = (array) explode( ',', $exclude_ids );
			$args['post__not_in'] = $exclude_ids;
		}

		// taxonomy
		$tax_query = array();
		if ( ! empty( $category ) ) {
			$category = (array) explode( ',', $category );
			$tax_query[] = array(
				'taxonomy' => 'academy_courses_category',
				'field'    => 'term_id',
				'terms'    => $category,
				'operator' => 'IN',
			);
		}

		if ( ! empty( $cat_not_in ) ) {
			$cat_not_in = (array) explode( ',', $cat_not_in );
			$tax_query[] = array(
				'taxonomy' => 'academy_courses_category',
				'field'    => 'term_id',
				'terms'    => $cat_not_in,
				'operator' => 'NOT IN',
			);
		}

		if ( ! empty( $tag ) ) {
			$tag = (array) explode( ',', $tag );
			$tax_query[] = array(
				'taxonomy' => 'academy_courses_tag',
				'field'    => 'term_id',
				'terms'    => $tag,
				'operator' => 'IN',
			);
		}

		if ( ! empty( $tag_not_in ) ) {
			$tag_not_in = (array) explode( ',', $tag_not_in );
			$tax_query[] = array(
				'taxonomy' => 'academy_courses_tag',
				'field'    => 'term_id',
				'terms'    => $tag_not_in,
				'operator' => 'NOT IN',
			);
		}

		if ( count( $tax_query ) > 0 ) {
			if ( count( $tax_query ) > 1 ) {
				$tax_query['relation'] = 'AND';
			}
			$args['tax_query']     = $tax_query;
		}

		// meta
		$meta_query = array();
		if ( ! empty( $course_level ) ) {
			$meta_query[] = array(
				'key'      => 'academy_course_difficulty_level',
				'value'    => $course_level,
				'compare'  => 'IN',
			);
		}

		if ( ! empty( $price_type ) ) {
			$meta_query[] = array(
				'key'      => 'academy_course_type',
				'value'    => $price_type,
				'compare'  => 'IN',
			);
		}

		if ( count( $meta_query ) > 0 ) {
			if ( count( $meta_query ) > 1 ) {
				$meta_query['relation'] = 'OR';
			}
			$args['meta_query']    = $meta_query;
		}

		if ( ! empty( $orderby ) ) {
			switch ( $orderby ) {
				case 'title':
					$args['orderby'] = 'post_title';
					break;
				case 'date':
					$args['orderby'] = 'publish_date';
					break;
				case 'modified':
					$args['orderby'] = 'modified';
					break;
				default:
					$args['orderby'] = 'ID';
			}
		}
		$args['order'] = ! empty( $order ) ? $order : 'DESC';
		$args['posts_per_page'] = (int) $count;

		$grid_class = \Academy\Helper::get_responsive_column( array(
			'desktop' => $column_per_row,
			'tablet' => 2,
			'mobile' => 1,
		) );

		$attr_str = '';
		foreach ( [ 'column_per_row', 'exclude_ids', 'ids', 'count', 'order', 'orderby', 'category', 'cat_not_in', 'course_level', 'tag', 'tag_not_in', 'price_type' ] as $attribute ) {
			if ( ! empty( $atts[ $attribute ] ) ) {
				$attribute = 'column_per_row' === $atts[ $attribute ] ? 'per_row' : $attribute;
				$attribute = 'course_level' === $atts[ $attribute ] ? 'levels' : $attribute;
				$attribute = 'tag' === $atts[ $attribute ] ? 'tags' : $attribute;
				$attribute = 'price_type' === $atts[ $attribute ] ? 'type' : $attribute;
				$attr_str .= ' data-' . esc_attr( $attribute ) . '="' . esc_attr( $atts[ $attribute ] ) . '"';
			}
		}

		wp_reset_query();
		// phpcs:ignore WordPress.WP.DiscouragedFunctions.query_posts_query_posts
		query_posts( apply_filters( 'academy_courses_shortcode_args', $args ) );

		ob_start();

		echo '<div class="academy-courses academy-courses--grid"' . $attr_str . '>'; //phpcs:ignore
		echo '<div class="academy-courses__body">';
		echo '<div class="academy-row">';

		if ( have_posts() ) {
			// Load posts loop.
			while ( have_posts() ) {
				the_post();
				\Academy\Helper::get_template( 'content-course.php', array( 'grid_class' => $grid_class ) );
			}
			wp_reset_postdata();
			if ( $has_pagination ) {
				\Academy\Helper::get_template( 'archive/pagination.php' );
			}
		} else {
			\Academy\Helper::get_template( 'archive/course-none.php' );
		}

		echo '</div>';
		echo '</div>';
		echo '</div>';

		$output = ob_get_clean();
		wp_reset_query();

		return $output;
	}
}
