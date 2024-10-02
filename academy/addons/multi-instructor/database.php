<?php
namespace AcademyMultiInstructor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class Database {
	public static function create_initial_custom_table() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		global $wpdb;
		$prefix          = $wpdb->prefix;
		$charset_collate = $wpdb->get_charset_collate();
		Database\CreateEarningsTable::up( $prefix, $charset_collate );
		Database\CreateWithdrawsTable::up( $prefix, $charset_collate );
	}
}
