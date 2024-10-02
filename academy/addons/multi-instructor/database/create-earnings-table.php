<?php
namespace AcademyMultiInstructor\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CreateEarningsTable {

	public static function up( $prefix, $charset_collate ) {
		$table_name = $prefix . ACADEMY_PLUGIN_SLUG . '_earnings';
		$sql        = "CREATE TABLE IF NOT EXISTS $table_name (
            ID bigint(20) unsigned NOT NULL auto_increment,
            user_id int(11) DEFAULT NULL,
			course_id int(11) DEFAULT NULL,
			order_id int(11) DEFAULT NULL,
			order_status varchar(20) DEFAULT NULL,
			course_price_total decimal(16,2) DEFAULT NULL,
			course_price_grand_total decimal(16,2) DEFAULT NULL,
			instructor_amount decimal(16,2) DEFAULT NULL,
			instructor_rate decimal(16,2) DEFAULT NULL,
			admin_amount decimal(16,2) DEFAULT NULL,
			admin_rate decimal(16,2) DEFAULT NULL,
			commission_type varchar(20) DEFAULT NULL,
			deduct_fees_amount decimal(16,2) DEFAULT NULL,
			deduct_fees_name varchar(250) DEFAULT NULL,
			deduct_fees_type varchar(20) DEFAULT NULL,
			process_by varchar(20) DEFAULT NULL,
			created_at datetime DEFAULT NULL,
            PRIMARY KEY  (ID)
        ) $charset_collate;";
		dbDelta( $sql );
	}
}
