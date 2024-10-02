<?php
namespace AcademyQuizzes\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CreateQuizAttemptAnswersTable {

	public static function up( $prefix, $charset_collate ) {
		$table_name = $prefix . ACADEMY_PLUGIN_SLUG . '_quiz_attempt_answers';
		$sql        = "CREATE TABLE IF NOT EXISTS $table_name (
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
			PRIMARY KEY  (attempt_answer_id)
        ) $charset_collate;";
		dbDelta( $sql );
	}
}
