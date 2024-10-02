<?php
namespace  Academy\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class AcademySearch {

	public function __construct() {
		add_shortcode( 'academy_course_search', array( $this, 'search_form' ) );
		add_action( 'wp_ajax_academy/shortcode/search_form_handler', array( $this, 'search_form_handler' ) );
		add_action( 'wp_ajax_nopriv_academy/shortcode/search_form_handler', array( $this, 'search_form_handler' ) );
		add_action( 'wp_ajax_nopriv_academy/shortcode/academy_course_search', array( $this, 'academy_course_search' ) );
	}

	public function search_form_handler() {
		check_ajax_referer( 'academy_nonce', 'security' );
		$keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( $_POST['keyword'] ) : '';
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
								<a href="' . get_the_permalink() . '">
									<img src="' . esc_url( \Academy\Helper::get_the_course_thumbnail_url( 'academy_thumbnail' ) ) . '">
									' . get_the_title() . '
								</a>
							</li>';
			endwhile;
			wp_reset_postdata();
		} else {
			$item_markup = '<li><span>' . esc_html__( 'No course found', 'academy' ) . '</span></li>';
		}

		wp_send_json_success( '<ul class="academy-search-results' . ( $query->found_posts > 3 ? ' scrollbar' : '' ) . '">' . $item_markup . '</ul>' );
	}

	public function search_form( $atts ) {
		$attributes = shortcode_atts(array(
			'placeholder'                => esc_html__( 'Search Course', 'academy' ),
		), $atts);
		ob_start();
		\Academy\Helper::get_template(
			'shortcode/search.php',
			shortcode_atts(array(
				'placeholder'    => $attributes['placeholder'],
			), $atts)
		);
		return apply_filters( 'academy/templates/shortcode/academy_course_search', ob_get_clean() );
	}
}


