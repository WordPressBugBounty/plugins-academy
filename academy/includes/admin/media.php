<?php
namespace Academy\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Media {
	public static function init() {
		$self = new self();
		add_filter( 'ajax_query_attachments_args', array( $self, 'restrict_media_library_access' ) );
	}
	public function restrict_media_library_access( $query ) {
		$user_id = get_current_user_id();
		if ( $user_id && ! current_user_can( 'manage_options' ) ) {
			$query['author'] = $user_id;
		}
		return $query;
	}
}
