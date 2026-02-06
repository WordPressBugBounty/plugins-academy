<?php
namespace Academy\Lesson\LessonMigration;

use Academy\Lesson\LessonApi\Collection\Base\Collection;
use Academy\Lesson\LessonApi\Collection\{
	HpLessonCollection,
	PostLessonCollection
};
use Academy\Lesson\LessonApi\Common\Db;
use Academy\Lesson\LessonApi\Lesson as LessonApi;
use Academy\Lesson\LessonApi\Models\Base\Lesson;
use Academy\Lesson\LessonApi\Models\{
	HpLesson,
	PostLesson
};
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Migrator extends Db {

	protected array $setting = [
		'lesson-to-post' => [
			'collection_class' => HpLessonCollection::class,
			'migrator_class'   => Migrator\FromLessonTable::class,
			'lesson_class'     => HpLesson::class,
		],
		'post-to-lesson' => [
			'collection_class' => PostLessonCollection::class,
			'migrator_class'   => Migrator\FromPostTable::class,
			'lesson_class'     => PostLesson::class,
		],
	];

	protected string $flow;
	protected bool $migrator_status = true;
	protected int $batch_size;
	protected string $migrator_class;
	protected string $collection_class;
	protected string $lesson_class;

	public function __construct() {
		parent::__construct();

		$this->batch_size = 10;
		$this->flow       = $GLOBALS['academy_settings']->lesson_migrator_flow ?? '';

		if ( ! array_key_exists( $this->flow, $this->setting ) || ! $this->migrator_status ) {
			throw new Exception( __( 'Migration is not activated.', 'academy' ) );
		}

		$this->collection_class = $this->setting[ $this->flow ]['collection_class'];
		$this->migrator_class   = $this->setting[ $this->flow ]['migrator_class'];
		$this->lesson_class     = $this->setting[ $this->flow ]['lesson_class'];
	}

	protected function get_migrator_instance( Lesson $lesson ) : Migrator\Base\Migrator {
		$class = $this->migrator_class;
		return new $class( $lesson );
	}

	public function lessons_to_migrate(): ?array {
		if ( HpLessonCollection::class === $this->collection_class ) {

			$table = esc_sql( $this->table ); // safe literal table
			$limit = absint( $this->batch_size );

			$sql = "
                SELECT l.ID
                FROM {$table} l
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM {$wpdb->posts} p
                    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                    WHERE pm.meta_key = %s
                    AND pm.meta_value = l.ID
                )
                LIMIT %d
            ";

			return $this->wpdb->get_results(
				$this->wpdb->prepare(// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$sql, 'lesson:migrate:id', $limit// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				),
				ARRAY_A
			);

		} elseif ( PostLessonCollection::class === $this->collection_class ) {

			$posts_table = esc_sql( $this->wpdb->posts );
			$lesson_table = esc_sql( $this->table );
			$meta_table   = esc_sql( $this->meta_table );
			$limit        = absint( $this->batch_size );

			$sql = "
                SELECT l.ID
                FROM {$posts_table} l
                WHERE l.post_type = %s
                AND NOT EXISTS (
                    SELECT 1
                    FROM {$lesson_table} le
                    INNER JOIN {$meta_table} lm ON le.ID = lm.lesson_id
                    WHERE lm.meta_key = %s
                    AND lm.meta_value = l.ID
                )
                LIMIT %d
            ";

			return $this->wpdb->get_results(
				$this->wpdb->prepare(// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$sql, 'academy_lessons', 'lesson:migrate:id', $limit// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				),
				ARRAY_A
			);
		}//end if

		return null;
	}

	public function migrate(): int {
		$collection = $this->lessons_to_migrate(); // fixed typo in method name
		$count      = count( $collection );

		if ( 0 === $count ) {
			$academy_settings = $GLOBALS['academy_settings'] ?? new stdClass();

			$academy_settings->academy_is_hp_lesson_active =
				empty( $this->flow ) || 'post-to-lesson' === $this->flow;

			update_option(
				ACADEMY_SETTINGS_NAME,
				wp_json_encode( $academy_settings )
			);
		}

		if ( ! empty( $collection ) && is_array( $collection ) ) {
			foreach ( $collection as $lesson ) {
				$lesson_id = absint( $lesson['ID'] ?? 0 );
				if ( $lesson_id > 0 ) {
					$lesson_instance = $this->lesson_class::by_id( $lesson_id );
					$this->get_migrator_instance( $lesson_instance )->migrate();
				}
			}
		}

		return $count;
	}

	public function stats(): ?array {
		global $wpdb;

		$table          = esc_sql( $this->table );
		$meta_table     = esc_sql( $this->meta_table );
		$posts_table    = esc_sql( $wpdb->posts );
		$postmeta_table = esc_sql( $wpdb->postmeta );

		if ( HpLessonCollection::class === $this->collection_class ) {

			$sql_left = "
                SELECT COUNT(*)
                FROM {$table} l
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM {$posts_table} p
                    INNER JOIN {$postmeta_table} pm
                        ON p.ID = pm.post_id
                    WHERE pm.meta_key = %s
                        AND pm.meta_value = l.ID
                )
            ";

			$left = (int) $wpdb->get_var(
				$wpdb->prepare(// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$sql_left, 'lesson:migrate:id'// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				)
			);

			$sql_migrated = "
                SELECT COUNT(*)
                FROM {$table} l
                WHERE EXISTS (
                    SELECT 1
                    FROM {$posts_table} p
                    INNER JOIN {$postmeta_table} pm
                        ON p.ID = pm.post_id
                    WHERE pm.meta_key = %s
                        AND pm.meta_value = l.ID
                )
            ";

			$migrated = (int) $wpdb->get_var(
				$wpdb->prepare(// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$sql_migrated, 'lesson:migrate:id'// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				)
			);

			return [
				'left'     => $left,
				'migrated' => $migrated,
			];

		} elseif ( PostLessonCollection::class === $this->collection_class ) {

			$sql_left = "
                SELECT COUNT(*)
                FROM {$posts_table} l
                WHERE l.post_type = %s
                AND NOT EXISTS (
                    SELECT 1
                    FROM {$table} le
                    INNER JOIN {$meta_table} lm
                        ON le.ID = lm.lesson_id
                    WHERE lm.meta_key = %s
                        AND lm.meta_value = l.ID
                )
            ";

			$left = (int) $wpdb->get_var(
				$wpdb->prepare(// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$sql_left, 'academy_lessons', 'lesson:migrate:id'// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				)
			);

			$sql_migrated = "
                SELECT COUNT(*)
                FROM {$posts_table} l
                WHERE l.post_type = %s
                AND EXISTS (
                    SELECT 1
                    FROM {$table} le
                    INNER JOIN {$meta_table} lm
                        ON le.ID = lm.lesson_id
                    WHERE lm.meta_key = %s
                        AND lm.meta_value = l.ID
                )
            ";

			$migrated = (int) $wpdb->get_var(
				$wpdb->prepare(// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$sql_migrated, 'academy_lessons', 'lesson:migrate:id'// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				)
			);

			return [
				'left'     => $left,
				'migrated' => $migrated,
			];
		}//end if

		return null;
	}

}
