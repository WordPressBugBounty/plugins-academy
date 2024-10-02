<?php
namespace Academy\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CreateLessonMetaTable {
	public static function up( $prefix, $charset_collate ) {
		$table_name = $prefix . ACADEMY_PLUGIN_SLUG . '_lessonmeta';
		$sql        = "CREATE TABLE IF NOT EXISTS $table_name (
            meta_id bigint(20) unsigned NOT NULL auto_increment,
            lesson_id bigint(20) unsigned NOT NULL default '0',
            meta_key varchar(255) NOT NULL,
            meta_value longtext NOT NULL default '',
            PRIMARY KEY  (meta_id),
            KEY lesson_id (lesson_id),
            KEY meta_key (meta_key(191))
        ) $charset_collate;";
		dbDelta( $sql );
	}
}
