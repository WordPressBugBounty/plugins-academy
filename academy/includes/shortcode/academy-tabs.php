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

		$title_lists = explode( ',', $attributes['render_title'] );
		$shortcode_lists = explode( ',', $attributes['render_shortcode'] );

		$shortcode_lists_with_title = [];

		foreach ( $title_lists as $key => $title ) {
			$shortcode_lists_with_title[] = [
				'title' => $title,
				'shortcode' => $shortcode_lists[ $key ] ?? ''
			];
		}

		ob_start();

		\Academy\Helper::get_template( 'shortcode/tabs.php', [
			'title_lists' => $title_lists,
			'shortcode_lists_with_title' => $shortcode_lists_with_title,
		]);

		return apply_filters( 'academy/templates/shortcode/tabs', ob_get_clean() );
	}
}


