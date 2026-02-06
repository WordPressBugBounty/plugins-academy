<?php
namespace Academy\Lesson\LessonMigration\Migrator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Helper;
use Academy\Lesson\LessonApi\Models\PostLesson;

class FromLessonTable extends Base\Migrator {

	/**
	 * Migrate lesson from legacy lesson table to PostLesson.
	 */
	public function migrate() : void {
		if ( ! empty( $this->from->id() ) && ! $this->is_migrated() ) {
			$data = $this->from->get_data();
			$meta = $data['meta'];

			unset( $data['ID'], $data['meta'] );

			$data['lesson_name'] = Helper::generate_unique_lesson_slug( $data['lesson_name'] );

			$this->to = new PostLesson( $data, $meta, true );

			$this->to->set_meta_data( [
				self::KEY => $this->from->id(),
			] );

			$this->to->save();

			$this->from->set_meta_data( [
				self::KEY => $this->to->id(),
			] );

			$this->from->save();
		}//end if
	}

	/**
	 * Check if the lesson migration has already been performed.
	 *
	 * @return bool
	 */
	protected function is_migrated(): bool {
		// Escape table names
		$posts_table    = esc_sql( $this->wpdb->posts );
		$postmeta_table = esc_sql( $this->wpdb->postmeta );

		// Prepare SQL query using placeholders for dynamic values only
		$sql = "
			SELECT COUNT(p.ID)
			FROM {$posts_table} AS p
			INNER JOIN {$postmeta_table} pm
				ON p.ID = pm.post_id
			WHERE pm.meta_key = %s
			AND pm.meta_value = %d
		";

		// Use $wpdb->prepare() only for values
		$count = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				self::KEY, $this->from->id()// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			)
		);

		return $count > 0;
	}

}
