<?php
namespace Academy\Admin\Playlist\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LessonImport extends AbstractImport {

	public function save() : self {
		$this->id = (int) \Academy\Classes\Query::lesson_insert( $this->data );
		if ( is_int( $this->id ) ) {
			\Academy\Classes\Query::lesson_meta_insert( $this->id, $this->meta_data );
		}
		return $this;
	}

}
