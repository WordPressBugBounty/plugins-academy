<?php
namespace AcademyQuizpress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Installer {

	public static function init() {
		$self = new self();
		$self->create_database();
		$self->save_option();
	}
	public function create_database() {
		Database::create_initial_custom_table();
	}
	public function save_option() {
		\Academy\Options::set( \Academy\Options::DB_VERSIONS, 'quizpress', ACADEMY_QUIZPRESS_VERSION );
	}
}
