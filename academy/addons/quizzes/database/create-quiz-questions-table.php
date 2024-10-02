<?php
namespace AcademyQuizzes\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CreateQuizQuestionsTable {

	public static function up( $prefix, $charset_collate ) {
		$table_name = $prefix . ACADEMY_PLUGIN_SLUG . '_quiz_questions';
		$sql        = "CREATE TABLE IF NOT EXISTS $table_name (
            question_id bigint(20) unsigned NOT NULL auto_increment,
            quiz_id bigint(20) DEFAULT NULL,
			question_title text NOT NULL,
            question_name varchar(200) NOT NULL,
            question_content longtext NULL,
			question_status varchar(20) NOT NULL default 'publish',
			question_level varchar(20) NOT NULL,
			question_type varchar(20) NOT NULL,
			question_score decimal(9,2) NOT NULL,
			question_settings longtext NULL,
			question_order int(11) NOT NULL,
			question_created_at datetime DEFAULT NULL,
			question_updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (question_id)
        ) $charset_collate;";
		dbDelta( $sql );
	}
}
