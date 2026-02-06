<?php
namespace Academy\Lesson\LessonMigration\Migrator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Helper;
use Academy\Lesson\LessonApi\Models\HpLesson;

class FromPostTable extends Base\Migrator {

	/**
	 * Migrate lesson from post table to HpLesson.
	 */
	public function migrate() : void {
		if ( ! empty( $this->from->id() ) && ! $this->is_migrated() ) {
			$data = $this->from->get_data();
			$meta = $data['meta'];

			unset( $data['ID'], $data['meta'] );

			$data['lesson_name'] = Helper::generate_unique_lesson_slug( $data['lesson_name'] );

			$this->to = new HpLesson( $data, $meta, true );

			$this->to->set_meta_data( [
				self::KEY => $this->from->id(),
			] );

			$this->to->save();

			$this->from->set_meta_data( [
				self::KEY => $this->to->id(),
			] );

			$this->from->save_meta_data();
		}//end if
	}

	/**
	 * Check if the migration has already been done.
	 *
	 * @return bool
	 */
	protected function is_migrated(): bool {
		$table      = esc_sql( $this->table );
		$meta_table = esc_sql( $this->meta_table );

		$sql = "
			SELECT COUNT(le.ID)
			FROM {$table} le
			INNER JOIN {$meta_table} lm
			ON le.ID = lm.lesson_id
			WHERE lm.meta_key = %s
			AND lm.meta_value = %d
		";

		$count = intval(
			$this->wpdb->get_var(
				$this->wpdb->prepare(// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$sql, self::KEY, $this->from->id()// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				)
			)
		);

		return $count > 0;
	}

}
