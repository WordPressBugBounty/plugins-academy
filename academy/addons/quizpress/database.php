<?php
namespace AcademyQuizpress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Database {

	public static function init() {
		// No post type/meta to register here — QuizPress owns the quizpress_quiz
		// CPT and all of its meta. This addon only stores its own curriculum
		// reference table (see create_initial_custom_table()).
	}

	public static function create_initial_custom_table() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		global $wpdb;
		$prefix          = $wpdb->prefix;
		$charset_collate = $wpdb->get_charset_collate();
		Database\CreateQuizpressQuizLinksTable::up( $prefix, $charset_collate );
	}
}
