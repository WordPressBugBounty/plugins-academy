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

class CourseLessonUpdater extends Db {

	protected string $id;
	protected int $batch_size = 10;
	protected bool $is_hp;

	public function __construct() {
		parent::__construct();

		$this->is_hp = ( $GLOBALS['academy_settings']->lesson_migrator_flow ?? '' ) === 'lesson-to-post';
		$this->id    = $GLOBALS['academy_settings']->lesson_migrator_id ?? '';
	}

	/**
	 * Delete previous migration markers and optionally truncate lesson tables.
	 */
	protected function delete(): void {
		global $wpdb;

		// Delete migration meta key
		$wpdb->delete(
			$wpdb->postmeta,
			[ 'meta_key' => 'lesson:migrate:course:update' ]
		);

		if ( $this->is_hp ) {
			// Escape table names
			$table      = esc_sql( $this->table );
			$meta_table = esc_sql( $this->meta_table );

			$wpdb->query( "TRUNCATE TABLE {$table}" );// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "TRUNCATE TABLE {$meta_table}" );// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			return;
		}

		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
				'academy_lessons'
			)
		);

		if ( empty( $post_ids ) ) {
			return;
		}

		// Delete posts
		$wpdb->delete(
			$wpdb->posts,
			[ 'post_type' => 'academy_lessons' ]
		);

		// Delete post meta safely
		$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
		$prepared_sql = $wpdb->prepare(
			"DELETE FROM {$wpdb->postmeta} WHERE post_id IN ($placeholders)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			...array_map( 'absint', $post_ids )
		);

		$wpdb->query( $prepared_sql );// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Count courses left to update.
	 */
	public function left() : int {
		$sql = "
            SELECT COUNT(p.ID)
            FROM {$this->wpdb->posts} p
            LEFT JOIN {$this->wpdb->postmeta} pm
                ON p.ID = pm.post_id
                AND pm.meta_key = %s
            WHERE p.post_type = %s
            AND pm.meta_key IS NULL
        ";

		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				'lesson:migrate:course:update',
				'academy_courses'
			)
		);
	}

	/**
	 * Count courses already updated.
	 */
	public function updated() : int {
		$sql = "
            SELECT COUNT(p.ID)
            FROM {$this->wpdb->posts} p
            INNER JOIN {$this->wpdb->postmeta} pm
                ON p.ID = pm.post_id
            WHERE pm.meta_key = %s
        ";

		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				'lesson:migrate:course:update'
			)
		);
	}

	/**
	 * Update courses by mapping old lesson IDs to new ones.
	 *
	 * @param callable $cb_updated Callback for per-course update output.
	 * @param callable $cb_complete Callback when migration completes.
	 *
	 * @return int Number of courses updated.
	 */
	public function update( callable $cb_updated, callable $cb_complete ) : int {

		$data = $this->get_course_to_update();

		if ( ! empty( $data ) ) {

			foreach ( $data as $row ) {
				$curriculum = maybe_unserialize( $row['curriculum'] );

				$ids     = $this->get_new_lesson_id( $this->get_lesson_ids( $curriculum ) );
				$updated = $this->update_new_lesson_ids( $curriculum, $ids );

				update_post_meta( $row['ID'], 'academy_course_curriculum', $updated );
				update_post_meta( $row['ID'], 'lesson:migrate:course:update', $this->id );

				echo $cb_updated( $this, $ids, $updated );// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		} else {
			$this->delete();
			echo $cb_complete( $this );// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		return count( $data );
	}

	public function update_new_lesson_ids( array $data, array $ids ) : array {
		foreach ( $data as &$item ) {
			if ( isset( $item['topics'] ) && is_array( $item['topics'] ) ) {
				$item['topics'] = $this->update_lesson_ids( $item['topics'], $ids );
			}
		}
		return $data;
	}

	public function update_lesson_ids( array $topics, array $ids ) : array {

		foreach ( $topics as &$topic ) {

			if (
				isset( $topic['type'], $topic['id'] ) &&
				'lesson' === $topic['type'] &&
				isset( $ids[ $topic['id'] ] )
			) {
				$topic['id'] = $ids[ $topic['id'] ];
			}

			if ( isset( $topic['topics'] ) && is_array( $topic['topics'] ) ) {
				$topic['topics'] = $this->update_lesson_ids( $topic['topics'], $ids );
			}
		}

		return $topics;
	}

	public function get_lesson_ids( array $data ) : array {
		$ids = [];

		foreach ( $data as $item ) {
			if ( isset( $item['topics'] ) && is_array( $item['topics'] ) ) {
				$ids = array_merge(
					$ids,
					$this->extract_lesson_id_from_topics( $item['topics'] )
				);
			}
		}

		return $ids;
	}

	public function extract_lesson_id_from_topics( array $topics ) : array {
		$ids = [];

		foreach ( $topics as $topic ) {

			if ( isset( $topic['type'], $topic['id'] ) && 'lesson' === $topic['type'] ) {
				$ids[] = $topic['id'];
			}

			if ( isset( $topic['topics'] ) && is_array( $topic['topics'] ) ) {
				$ids = array_merge(
					$ids,
					$this->extract_lesson_id_from_topics( $topic['topics'] )
				);
			}
		}

		return $ids;
	}

	protected function get_course_to_update() : array {
		global $wpdb;

		$posts_table    = esc_sql( $wpdb->posts );
		$postmeta_table = esc_sql( $wpdb->postmeta );

		$sql = "
            SELECT c.ID, cm.meta_value AS curriculum
            FROM {$posts_table} c
            INNER JOIN {$postmeta_table} cm
                ON c.ID = cm.post_id
            WHERE c.post_type = %s
                AND cm.meta_key = %s
                AND NOT EXISTS (
                    SELECT 1
                    FROM {$postmeta_table} pm
                    WHERE pm.meta_key = %s
                        AND pm.post_id = c.ID
                )
            LIMIT %d
        ";

		return $wpdb->get_results(
			$wpdb->prepare(// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql, 'academy_courses', 'academy_course_curriculum', 'lesson:migrate:course:update', $this->batch_size// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			),
			ARRAY_A
		);
	}

	public function get_new_lesson_id( array $ids ) : array {

		if ( empty( $ids ) ) {
			return [];
		}

		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

		if ( ! $this->is_hp ) {
			$table = esc_sql( $this->meta_table );
			$sql = "
                SELECT meta_value AS old_id, lesson_id AS new_id
                FROM {$table}
                WHERE meta_key = %s
                AND meta_value IN ($placeholders)
            ";
		} else {
			$postmeta = esc_sql( $this->wpdb->postmeta );
			$sql = "
                SELECT meta_value AS old_id, post_id AS new_id
                FROM {$postmeta}
                WHERE meta_key = %s
                AND meta_value IN ($placeholders)
            ";
		}

		return array_column(
			$this->wpdb->get_results(
				$this->wpdb->prepare(// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$sql, 'lesson:migrate:id', ...array_map( 'absint', $ids )// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				),
				ARRAY_A
			),
			'new_id',
			'old_id'
		);
	}
}
