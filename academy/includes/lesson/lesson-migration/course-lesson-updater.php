<?php
namespace Academy\Lesson\LessonMigration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Exception;
use Academy\Lesson\LessonApi\Collection\Base\Collection;
use Academy\Lesson\LessonApi\Collection\{
    HpLessonCollection,
    PostLessonCollection
};
use Academy\Lesson\LessonApi\Models\{ 
    HpLesson,
    PostLesson
};
use Academy\Lesson\LessonApi\Models\Base\Lesson;
use Academy\Lesson\LessonApi\Lesson as LessonApi;
use Academy\Lesson\LessonApi\Common\Db;

class CourseLessonUpdater extends Db {
    protected string $id;
    protected int $batch_size = 10;
    protected bool $is_hp;

    public function __construct() {
        parent::__construct();
        $this->is_hp = $GLOBALS['academy_settings']->lesson_migrator_flow === 'lesson-to-post';
        $this->id = $GLOBALS['academy_settings']->lesson_migrator_id ?? '';
    }
    
    protected function delete() : void {
        $this->wpdb->delete( $this->wpdb->postmeta, [
            'meta_key' => 'lesson:migrate:course:update'
        ]);
        if ( $this->is_hp ) {
            $this->wpdb->query( "TRUNCATE TABLE {$this->table}" );
            $this->wpdb->query( "TRUNCATE TABLE {$this->meta_table}" );
        }
        else {
            $meta_key = 'lesson:migrate:course:update'; // Your meta key

            $post_ids = $this->wpdb->get_col(
                $this->wpdb->prepare(
                    "SELECT p.ID
                    FROM {$this->wpdb->posts} p
                    WHERE p.post_type = %s",
                    'academy_lessons',
                )
            );

            if ( ! empty( $post_ids ) ) {
                $this->wpdb->delete( $this->wpdb->posts, [
                    'post_type' => 'academy_lessons'
                ]);
                $ids_placeholder = implode( ',', array_map( 'absint', $post_ids ) );
                $this->wpdb->query( "DELETE FROM {$this->wpdb->postmeta} WHERE post_id IN ($ids_placeholder)" );
            }
        }
    }

    public function left() : int {
        return intval( $this->wpdb->get_var(
            $this->wpdb->prepare( 
                "SELECT COUNT(p.ID)
                FROM {$this->wpdb->posts} p
                LEFT JOIN {$this->wpdb->postmeta} pm
                    ON p.ID = pm.post_id AND pm.meta_key = %s
                WHERE p.post_type = %s AND pm.meta_key IS NULL;", 
                'lesson:migrate:course:update',
                'academy_courses'
            ) 
        ));
    }

    public function updated() : int {
        return intval( $this->wpdb->get_var(
                $this->wpdb->prepare( 
                    "SELECT COUNT(p.ID)
                    FROM {$this->wpdb->posts} p
                    INNER JOIN {$this->wpdb->postmeta} pm
                        ON p.ID = pm.post_id
                    WHERE pm.meta_key = %s;", 
                    'lesson:migrate:course:update' 
                ) 
            )
        );
    }

    public function update( callable $cb_updated, callable $cb_complete ) : int {
        if ( ! empty( $data = $this->get_course_to_update() ) ) {
            foreach ( $data as [ 'ID' => $id, 'curriculum' => $curriculum ] ) {
                $curriculum = unserialize( $curriculum );
                \update_post_meta( $id, 'academy_course_curriculum', $cur = $this->update_new_lession_ids( 
                        $curriculum, 
                        $ids = $this->get_new_lesson_id(
                            $this->get_lesson_ids( $curriculum )
                        )
                    )
                );
                \update_post_meta( $id, 'lesson:migrate:course:update', $this->id );
                echo $cb_updated( $this, $ids, $cur );
            }
        }
        else {
            $this->delete();
            echo $cb_complete( $this );
        }
        return count( $data );
    }

    public function update_new_lession_ids( array $data, array $ids ) : array {
        foreach ( $data as &$item ) {
            if ( isset( $item['topics'] ) && is_array( $item['topics'] ) ) {
                $item['topics'] = $this->update_lesson_ids( $item['topics'], $ids );
            }
        }
        return $data;
    }

    public function update_lesson_ids( array $topics, array $ids ): array {
        foreach ( $topics as &$topic ) {
            if ( isset( $topic['type'] ) && $topic['type'] === 'lesson' && isset( $topic['id'] ) ) {
                if ( isset( $ids[$topic['id']] ) ) {
                    $topic['id'] = $ids[$topic['id']];
                }
            }
            
            if ( isset( $topic['topics']) && is_array( $topic['topics'] ) ) {
                $topic['topics'] = $this->update_lesson_ids( $topic['topics'], $ids );
            }
        }
        return $topics;
    }
    public function get_lesson_ids( array $data ) : array {
        $ids = [];
        foreach ( $data as $item ) {
            if ( isset( $item['topics'] ) && is_array( $item['topics'] ) ) {
                $ids = array_merge( $ids, $this->extract_lesson_id_from_topics( $item['topics'] ) );
            }
        }
        return $ids;
    }

    public function extract_lesson_id_from_topics( array $topics ) : array {
        $ids = [];
        foreach ( $topics as $topic ) {
            if ( isset( $topic['type'] ) && $topic['type'] === 'lesson' && isset( $topic['id'] ) ) {
                $ids[] = $topic['id'];
            }
    
            if ( isset( $topic['topics'] ) && is_array( $topic['topics'] ) ) {
                $ids = array_merge( $ids, $this->extract_lesson_id_from_topics( $topic['topics'] ) );
            }
        }
        return $ids;
    }

    protected function get_course_to_update()  {
        return $this->wpdb->get_results( 
            $this->wpdb->prepare(
                "SELECT c.ID, cm.meta_value as curriculum
                FROM {$this->wpdb->posts} c
                    INNER JOIN {$this->wpdb->postmeta} cm
                        ON c.ID = cm.post_id
                WHERE 
                    c.post_type = %s AND 
                    cm.meta_key = %s AND 
                    NOT EXISTS (
                        SELECT 1
                            FROM {$this->wpdb->posts} p
                            INNER JOIN {$this->wpdb->postmeta} pm
                                ON p.ID = pm.post_id
                        WHERE pm.meta_key = %s
                    )
                LIMIT %d;
                ",
                'academy_courses',
                'academy_course_curriculum',
                'lesson:migrate:course:update',
                $this->batch_size
            ),
            ARRAY_A
        );
    }
    
    public function get_new_lesson_id( array $ids ) : ?array {
        if ( empty( $ids ) ) return [];
        $placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
        if ( ! $this->is_hp ) {
            return array_column( $this->wpdb->get_results( 
                $this->wpdb->prepare(
                    "SELECT meta_value as old_id, lesson_id as new_id
                        FROM {$this->meta_table}
                        WHERE meta_key = %s
                            AND meta_value IN ({$placeholders});
                    ",
                    'lesson:migrate:id',
                    ...$ids
                ),
                ARRAY_A
            ) ?? [], 'new_id', 'old_id' );
        }
        else {
            return array_column( $this->wpdb->get_results( 
                $this->wpdb->prepare(
                    "SELECT meta_value as old_id, post_id as new_id
                        FROM {$this->wpdb->postmeta}
                        WHERE meta_key = %s
                            AND meta_value IN ({$placeholders});
                    ",
                    'lesson:migrate:id',
                    ...$ids
                ),
                ARRAY_A
            ) ?? [], 'new_id', 'old_id' );
        }
        return null;
    }
    
}