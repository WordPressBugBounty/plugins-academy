<?php
namespace AcademyMigrationTool\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface MigrationInterface {
	public function run_migration();
	public function get_logs();
	public function migrate_course( $course_id );
	public function migrate_course_author( $author_id, $course_id );
	public function migrate_course_meta( $course_id );
	public function migrate_enrollments( $course_id );
	public function migrate_course_complete( $course_id );
	public function migrate_course_reviews( $course_id );
	public function migrate_course_quiz( $course_id );
	public function migrate_course_lesson( $course_id );
}
