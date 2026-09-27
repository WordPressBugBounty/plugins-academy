<?php
namespace Academy\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CreateAttachmentDownloadsTable {
	public static function up( $prefix, $charset_collate ) {
		$table_name = $prefix . ACADEMY_PLUGIN_SLUG . '_attachment_downloads';
		$sql        = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL auto_increment,
            object_type varchar(20) NOT NULL,
            object_id bigint(20) unsigned NOT NULL,
            attachment_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            downloaded_at datetime NOT NULL default '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            KEY object_type_id (object_type,object_id),
            KEY attachment_id (attachment_id),
            KEY user_id (user_id)
        ) $charset_collate;";
		dbDelta( $sql );
	}
}
