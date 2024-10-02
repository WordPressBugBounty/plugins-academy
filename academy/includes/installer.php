<?php
namespace Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Admin\Settings;
use Academy\Classes\Pages;
use Academy\Classes\Role;

class Installer {

	public $academy_version;
	public static function init() {
		$self = new self();
		$self->academy_version = get_option( 'academy_version' );
		$self->save_main_settings();
		Database::create_initial_custom_table();
		$self->add_role();
		// if first time install then run below method
		if ( ! $self->academy_version ) {
			$self->create_initial_pages();
		}
		// Save option table data
		$self->save_option();
	}
	public function save_main_settings() {
		Settings::save_settings();
	}
	public function save_option() {
		if ( ! $this->academy_version ) {
			add_option( 'academy_version', ACADEMY_VERSION );
		}
		if ( ! get_option( 'academy_db_version' ) ) {
			add_option( 'academy_db_version', ACADEMY_DB_VERSION );
		}
		if ( ! get_option( 'academy_first_install_time' ) ) {
			add_option( 'academy_first_install_time', Helper::get_time() );
		}
		update_option( 'academy_required_rewrite_flush', Helper::get_time() );
		update_option( 'academy_flash_role_management', true );
	}
	public function add_role() {
		// student role
		Role::add_student_role();
		// instructor role
		Role::add_instructor_role();
	}
	public function create_initial_pages() {
		Pages::regenerate_necessary_pages();
	}
}
