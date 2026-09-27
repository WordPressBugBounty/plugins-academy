<?php
namespace AcademyQuizzes\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CreateQuizAttemptAnswersTable {

	public static function table_name( $prefix ) {
		return $prefix . ACADEMY_PLUGIN_SLUG . '_quiz_attempt_answers';
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
            attempt_answer_id bigint(20) unsigned NOT NULL auto_increment,
			user_id bigint(20) DEFAULT NULL,
			quiz_id bigint(20) DEFAULT NULL,
			question_id bigint(20) DEFAULT NULL,
			attempt_id bigint(20) DEFAULT NULL,
			answer text NOT NULL,
            question_mark decimal(9,2) DEFAULT NULL,
            achieved_mark decimal(9,2) DEFAULT NULL,
            minus_mark decimal(9,2) DEFAULT NULL,
            is_correct tinyint(1) DEFAULT NULL,
            ai_feedback longtext DEFAULT NULL,
			PRIMARY KEY  (attempt_answer_id),
			UNIQUE KEY attempt_question (attempt_id, question_id)
        ) $charset_collate;";
	}

	public static function up( $prefix, $charset_collate ) {
		dbDelta( self::schema( $prefix, $charset_collate ) );
	}
}
