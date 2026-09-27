<?php
namespace AcademyNotes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Installer {

	public static function init() {
		$self = new self();
		$self->create_database();
		$self->migrate_legacy_notes();
		$self->save_option();
	}

	public function create_database() {
		Database::create_initial_custom_table();
	}

	/**
	 * Move legacy notebook notes into the notes table (first batch now, the
	 * rest in the background). See LegacyMigration.
	 */
	public function migrate_legacy_notes() {
		LegacyMigration::run();
	}

	public function save_option() {
		\Academy\Options::set( \Academy\Options::DB_VERSIONS, 'notes', ACADEMY_NOTES_VERSION );
		// The "My Notes" dashboard page adds a rewrite rule — flush so its URL
		// resolves right after activation.
		\Academy\Helper::flush_rewrite_rules();
	}
}
