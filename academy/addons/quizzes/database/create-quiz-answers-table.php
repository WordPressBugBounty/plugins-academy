<?php
namespace AcademyQuizzes\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CreateQuizAnswersTable {
	public static function table_name( $prefix ) {
		return $prefix . ACADEMY_PLUGIN_SLUG . '_quiz_answers';
	}

	/**
	 * Full table definition. Also the source of truth for the columns
	 * \AcademyQuizzes\Database::sync_schema() adds to an existing table.
	 *
	 * @param string $prefix
	 * @param string $charset_collate
	 */
	public static function schema( $prefix, $charset_collate ) {
		$table_name = self::table_name( $prefix );
		return "CREATE TABLE IF NOT EXISTS $table_name (
            answer_id bigint(20) unsigned NOT NULL auto_increment,
            quiz_id bigint(20) DEFAULT NULL,
            question_id bigint(20) DEFAULT NULL,
            question_type varchar(20) NOT NULL,
			answer_title text NOT NULL,
            answer_content longtext NULL,
			is_correct tinyint(1) NOT NULL,
			image_id bigint(20) DEFAULT NULL,
			view_format varchar(20) DEFAULT NULL,
			answer_order int(11) NOT NULL,
			answer_created_at datetime DEFAULT NULL,
			answer_updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (answer_id)
        ) $charset_collate;";
	}

	public static function up( $prefix, $charset_collate ) {
		dbDelta( self::schema( $prefix, $charset_collate ) );
	}
}
