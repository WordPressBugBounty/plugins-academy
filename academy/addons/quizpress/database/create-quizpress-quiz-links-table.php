<?php
namespace AcademyQuizpress\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CreateQuizpressQuizLinksTable {

	/**
	 * Maps a QuizPress quiz to the Academy course/topic that references it as
	 * a curriculum item. This table only ever stores Academy's own curriculum
	 * structure — never a copy of QuizPress's quiz content or attempt data.
	 * It exists solely so the enrollment gate (see API::gate_attempt_start())
	 * can resolve which course a quiz belongs to, since QuizPress's own
	 * attempt-start request only carries a quiz_id.
	 *
	 * @param string $prefix
	 * @param string $charset_collate
	 */
	public static function up( $prefix, $charset_collate ) {
		$table_name = $prefix . ACADEMY_PLUGIN_SLUG . '_quizpress_quiz_links';
		$sql        = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) unsigned NOT NULL auto_increment,
			quiz_id bigint(20) unsigned NOT NULL,
			course_id bigint(20) unsigned NOT NULL,
			topic_id bigint(20) unsigned NOT NULL,
			PRIMARY KEY  (id),
			KEY quiz_id (quiz_id),
			UNIQUE KEY quiz_course_topic (quiz_id, course_id, topic_id)
		) $charset_collate;";
		dbDelta( $sql );
	}
}
