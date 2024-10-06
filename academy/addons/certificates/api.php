<?php
namespace AcademyCertificates;

use Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class API {

	public static function init() {
		$self = new self();
		add_filter( 'rest_prepare_academy_certificate', array( $self, 'add_author_name_to_rest_response' ), 10, 3 );
		add_filter( 'rest_prepare_academy_certificate', [ $self, 'decode_special_characters_from_title' ], 10, 3 );
	}
	public function add_author_name_to_rest_response( $item, $post, $request ) {
		$author_data = get_userdata( $item->data['author'] );
		$item->data['author_name'] = $author_data->display_name;
		return $item;
	}
	public function decode_special_characters_from_title( $item, $post, $request ) {
		$item->data['title']['rendered'] = html_entity_decode( $item->data['title']['rendered'] );
		return $item;
	}
}
