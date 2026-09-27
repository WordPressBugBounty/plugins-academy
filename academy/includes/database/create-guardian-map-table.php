<?php
namespace Academy\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical guardian ↔ learner link. One row per (guardian, ward) pair.
 * This is the single source of truth for the family relationship; the Digital
 * Campus SIS consumes it (its own map only adds school-specific attributes).
 */
class CreateGuardianMapTable {
	public static function up( $prefix, $charset_collate ) {
		$table_name = $prefix . ACADEMY_PLUGIN_SLUG . '_guardian_map';
		$sql        = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) unsigned NOT NULL auto_increment,
			guardian_id bigint(20) unsigned NOT NULL,
			student_id bigint(20) unsigned NOT NULL,
			relationship varchar(50) DEFAULT NULL,
			is_primary tinyint(1) NOT NULL DEFAULT 0,
			created_at timestamp DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY guardian_student (guardian_id, student_id),
			KEY student_id (student_id)
		) $charset_collate;";
		dbDelta( $sql );
	}
}
