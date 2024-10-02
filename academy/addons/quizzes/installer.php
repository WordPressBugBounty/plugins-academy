<?php
namespace AcademyQuizzes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Admin\Settings;
use Academy\Helper;

class Installer {

	public $academy_version;
	public static function init() {
		$self = new self();
		$self->create_database();
		$self->saved_settings();
		$self->save_option();
	}
	public function create_database() {
		Database::create_initial_custom_table();
	}
	public function saved_settings() {
		Settings::save_settings();
	}
	public function save_option() {
		if ( get_option( ACADEMY_QUIZZES_VERSION_NAME ) !== ACADEMY_QUIZZES_VERSION ) {
			update_option( ACADEMY_QUIZZES_VERSION_NAME, ACADEMY_QUIZZES_VERSION );
		}
	}
}
