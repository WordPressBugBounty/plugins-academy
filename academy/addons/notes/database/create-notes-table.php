<?php
namespace AcademyNotes\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CreateNotesTable {

	public static function up( $prefix, $charset_collate ) {
		$table_name = $prefix . ACADEMY_PLUGIN_SLUG . '_notes';
		$sql        = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) unsigned NOT NULL auto_increment,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			course_id bigint(20) unsigned NOT NULL DEFAULT 0,
			topic_id bigint(20) unsigned NOT NULL DEFAULT 0,
			topic_type varchar(20) DEFAULT '',
			note_type varchar(20) NOT NULL DEFAULT 'text',
			video_time int(11) DEFAULT NULL,
			content longtext,
			created_at datetime DEFAULT NULL,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY user_course (user_id, course_id),
			KEY topic (topic_id)
		) $charset_collate;";
		dbDelta( $sql );
	}
}
