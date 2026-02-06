<?php
namespace  Academy\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class AcademyTabs {

	public function __construct() {
		add_shortcode( 'academy_tabs', [
			$this,
			'academy_tabs',
		]);
	}

	public function academy_tabs( $attributes, $content = '' ) {
		$attributes = shortcode_atts(
			[
				'render_title' => '',
				'render_shortcode' => '',
			],
			$attributes,
			'academy_tabs'
		);

		$render_title     = sanitize_text_field( $attributes['render_title'] );
		$render_shortcode = sanitize_text_field( $attributes['render_shortcode'] );

		$title_lists     = explode( ',', $render_title );
		$shortcode_lists = explode( ',', $render_shortcode );

		$shortcode_lists_with_title = [];

		foreach ( $title_lists as $key => $title ) {
			$title = sanitize_text_field( $title );
			switch ( $title ) {
				case 'Course Content':
					$display_title = __( 'Course Content', 'academy' );
					break;
				case 'Announcement':
					$display_title = __( 'Announcement', 'academy' );
					break;
				case 'Lesson Comments':
					$display_title = __( 'Comments', 'academy' );
					break;
				default:
					$display_title = $title;
					break;
			}
			$display_titles[] = $display_title;
			$shortcode_lists_with_title[] = [
				'title' => $title,
				'shortcode' => isset( $shortcode_lists[ $key ] ) ? sanitize_key( $shortcode_lists[ $key ] ) : '',
			];
		}//end foreach

		ob_start();
		\Academy\Helper::get_template( 'shortcode/tabs.php', [
			'title_lists' => $display_titles,
			'shortcode_lists_with_title' => $shortcode_lists_with_title,
		]);

		return apply_filters( 'academy/templates/shortcode/tabs', ob_get_clean() );
	}
}
