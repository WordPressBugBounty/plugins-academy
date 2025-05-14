<?php
namespace Academy\Ajax\CourseImport\Importers\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Insertable {
	public function insert() : int;
}
