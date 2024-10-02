<?php
namespace Academy\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CreateLessonsTable {
	public static function up( $prefix, $charset_collate ) {
		$table_name = $prefix . ACADEMY_PLUGIN_SLUG . '_lessons';
		$sql        = "CREATE TABLE IF NOT EXISTS $table_name (
            ID bigint(20) unsigned NOT NULL auto_increment,
            lesson_author bigint(20) unsigned NOT NULL default '0',
            lesson_date datetime NOT NULL default '0000-00-00 00:00:00',
            lesson_date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
            lesson_title text NOT NULL,
            lesson_name varchar(200) NOT NULL,
            lesson_content longtext NULL,
            lesson_excerpt text NULL,
            lesson_status varchar(20) NOT NULL default 'publish',
            comment_status varchar(20) NOT NULL DEFAULT 'closed',
            comment_count bigint(20) NOT NULL DEFAULT '0',
            lesson_password varchar(255) NOT NULL DEFAULT '',
            lesson_modified datetime NOT NULL default '0000-00-00 00:00:00',
            lesson_modified_gmt datetime NOT NULL default '0000-00-00 00:00:00',
            PRIMARY KEY  (ID),
            KEY type_status_date (lesson_status,lesson_date,ID),
            KEY lesson_author (lesson_author)
        ) $charset_collate;";
		dbDelta( $sql );
	}
}
