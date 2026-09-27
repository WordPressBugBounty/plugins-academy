<?php
namespace AcademyQuizzes\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CreateQuizQuestionsTable {

	public static function table_name( $prefix ) {
		return $prefix . ACADEMY_PLUGIN_SLUG . '_quiz_questions';
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
            question_id bigint(20) unsigned NOT NULL auto_increment,
            quiz_id bigint(20) DEFAULT NULL,
			question_title text NOT NULL,
			question_title_type varchar(10) NOT NULL default 'plain',
            question_name varchar(200) NOT NULL,
            question_content longtext NULL,
			question_explanation longtext NULL,
			question_status varchar(20) NOT NULL default 'publish',
			question_level varchar(20) NOT NULL,
			question_type varchar(20) NOT NULL,
			question_score decimal(9,2) NOT NULL,
			question_negative_score decimal(9,2) NOT NULL,
			question_image_id bigint(20) DEFAULT NULL,
			question_audio_id bigint(20) DEFAULT NULL,
			question_settings longtext NULL,
			question_order int(11) NOT NULL,
			question_created_at datetime DEFAULT NULL,
			question_updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (question_id)
        ) $charset_collate;";
	}

	public static function up( $prefix, $charset_collate ) {
		dbDelta( self::schema( $prefix, $charset_collate ) );
	}
}
