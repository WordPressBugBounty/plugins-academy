<?php
namespace AcademyNotes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Database {
	public static function create_initial_custom_table() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		global $wpdb;
		$prefix          = $wpdb->prefix;
		$charset_collate = $wpdb->get_charset_collate();
		Database\CreateNotesTable::up( $prefix, $charset_collate );
	}

	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . ACADEMY_PLUGIN_SLUG . '_notes';
	}
}
