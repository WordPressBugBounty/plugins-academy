<?php
namespace Academy\Lesson\LessonMigration\Migrator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Helper;
use Academy\Lesson\LessonApi\Models\PostLesson;
class FromLessonTable extends Base\Migrator {
	public function migrate() : void {
		if ( ! empty( $this->from->id() ) && ! $this->is_migrated() ) {
			$data = $this->from->get_data();
			$meta = $data['meta'];
			unset( $data['ID'], $data['meta'] );
			$data['lesson_name'] = Helper::generate_unique_lesson_slug( $data['lesson_name'] );
			$this->to = new PostLesson( $data, $meta, true );
			$this->to->set_meta_data([
				self::KEY => $this->from->id(),
			]);
			$this->to->save();

			$this->from->set_meta_data([
				self::KEY => $this->to->id(),
			]);
			$this->from->save();
		}
	}
	protected function is_migrated() : bool {
		return intval( $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(p.ID)
                        FROM {$this->wpdb->posts} p
                        INNER JOIN {$this->wpdb->postmeta} pm
                        ON p.ID = pm.post_id
                        	WHERE pm.meta_key = %s
                        		AND pm.meta_value = %d",
				// "SELECT COUNT(*) FROM {$this->wpdb->postmeta} WHERE meta_key = %s AND meta_value = %d",
				self::KEY,
				$this->from->id()
			)
		) ) > 0;
	}
}
