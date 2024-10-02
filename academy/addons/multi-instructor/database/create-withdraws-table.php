<?php
namespace AcademyMultiInstructor\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CreateWithdrawsTable {

	public static function up( $prefix, $charset_collate ) {
		$table_name = $prefix . ACADEMY_PLUGIN_SLUG . '_withdraws';
		$sql        = "CREATE TABLE IF NOT EXISTS $table_name (
            ID bigint(20) unsigned NOT NULL auto_increment,
            user_id int(11) DEFAULT NULL,
			amount decimal(16,2) DEFAULT NULL,
			method_data text DEFAULT NULL,
			status varchar(20) DEFAULT NULL,
			updated_at datetime DEFAULT NULL,
			created_at datetime DEFAULT NULL,
            PRIMARY KEY  (ID)
        ) $charset_collate;";
		dbDelta( $sql );
	}
}
