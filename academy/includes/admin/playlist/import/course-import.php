<?php
namespace Academy\Admin\Playlist\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CourseImport extends AbstractImport {

	public function save() : self {
		$this->data['post_type'] = 'academy_courses';
		$this->id = wp_insert_post( $this->data );
		if ( ! is_wp_error( $this->id ) ) {
			foreach ( $this->meta_data as $key => $value ) {
				update_post_meta( $this->id, $key, $value );
			}
		}
		return $this;
	}

}
