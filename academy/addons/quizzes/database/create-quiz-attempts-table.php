<?php
namespace AcademyQuizzes\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CreateQuizAttemptsTable {

	public static function up( $prefix, $charset_collate ) {
		$table_name = $prefix . ACADEMY_PLUGIN_SLUG . '_quiz_attempts';
		$sql        = "CREATE TABLE IF NOT EXISTS $table_name (
            attempt_id bigint(20) unsigned NOT NULL auto_increment,
            course_id bigint(20) DEFAULT NULL,
            quiz_id bigint(20) DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            total_questions int(11) DEFAULT NULL,
            total_answered_questions int(11) DEFAULT NULL,
            total_marks decimal(9,2) DEFAULT NULL,
            earned_marks decimal(9,2) DEFAULT NULL,
			attempt_info text NOT NULL,
			attempt_status varchar(50) NOT NULL,
			attempt_ip varchar(250) NOT NULL,
			attempt_started_at datetime DEFAULT NULL,
			attempt_ended_at datetime DEFAULT NULL,
			is_manually_reviewed tinyint(1) DEFAULT NULL,
			manually_reviewed_at datetime DEFAULT NULL,
			PRIMARY KEY  (attempt_id)
        ) $charset_collate;";
		dbDelta( $sql );
	}
}
