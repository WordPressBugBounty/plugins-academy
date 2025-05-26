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

class Migrator extends Db {
    protected array $setting = [
        'lesson-to-post' => [
            'collection_class' => HpLessonCollection::class,
            'migrator_class' => Migrator\FromLessonTable::class,
            'lesson_class' => HpLesson::class,
        ],
        'post-to-lesson' => [
            'collection_class' => PostLessonCollection::class,
            'migrator_class' => Migrator\FromPostTable::class,
            'lesson_class' => PostLesson::class,
        ]
    ];
    protected string $flow;
    protected bool $migrator_status = true;
    protected int $batch_size ;
    protected string $migrator_class;
    protected string $collection_class;
    protected string $lesson_class;
    public function __construct() {
        parent::__construct();
        $this->batch_size = 10;
        $this->flow = $GLOBALS['academy_settings']->lesson_migrator_flow ?? '';
        if ( ! array_key_exists( $this->flow, $this->setting ) ||  ! $this->migrator_status ) {
            throw new Exception( __( 'Migration is not activated.', 'academy' ) );
        }
        $this->collection_class = $this->setting[$this->flow]['collection_class'];
        $this->migrator_class = $this->setting[$this->flow]['migrator_class'];
        $this->lesson_class = $this->setting[$this->flow]['lesson_class'];
        
    }
    protected function get_migrator_instance( Lesson $lesson ) : Migrator\Base\Migrator {
        $class =  $this->migrator_class;
        return new $class( $lesson );
    }
    
    public function lessons_to_migtate() : ?array {
        
        if ( $this->collection_class === HpLessonCollection::class ) {
            return $this->wpdb->get_results( 
                $this->wpdb->prepare(
                    "SELECT l.ID
                    FROM {$this->table} l
                    WHERE NOT EXISTS (
                        SELECT 1
                        FROM {$this->wpdb->posts} p
                        INNER JOIN {$this->wpdb->postmeta} pm
                        ON p.ID = pm.post_id
                        WHERE pm.meta_key = 'lesson:migrate:id'
                        AND pm.meta_value = l.ID
                    )
                    LIMIT %d 
                    ",
                    $this->batch_size
                ),
                ARRAY_A
            );
        }
        else if ( $this->collection_class === PostLessonCollection::class ) {
            return $this->wpdb->get_results( 
                $this->wpdb->prepare(
                    "SELECT l.ID
                    FROM {$this->wpdb->posts} l
                    WHERE 
                    l.post_type = 'academy_lessons' AND
                    NOT EXISTS (
                        SELECT 1
                        FROM {$this->table} le
                        INNER JOIN {$this->meta_table} lm
                        ON le.ID = lm.lesson_id
                        WHERE lm.meta_key = 'lesson:migrate:id'
                        AND lm.meta_value = l.ID
                    )
                    LIMIT %d 
                    ",
                    $this->batch_size
                ),
                ARRAY_A
            );
        }
    }
    
    public function migrate() : int {
        $collection = $this->lessons_to_migtate();
        $count = count( $collection );

        if ( $count === 0 ) {
            $GLOBALS['academy_settings']->academy_is_hp_lesson_active = empty( $this->flow ) || $this->flow === 'post-to-lesson' ? true : false;
            update_option( ACADEMY_SETTINGS_NAME, json_encode( $GLOBALS['academy_settings'] ) );
        }
        
        foreach ( $collection as $lesson ) {
            $this->get_migrator_instance( $this->lesson_class::by_id( $lesson['ID'] ) )->migrate();
        }
        return $count;
    }
    
    public function stats() : ?array {
        if ( $this->collection_class === HpLessonCollection::class ) {
            return [
                'left' => intval( $this->wpdb->get_var( 
                        $this->wpdb->prepare(
                            "SELECT COUNT(*)
                            FROM {$this->table} l
                            WHERE NOT EXISTS (
                                SELECT 1
                                FROM {$this->wpdb->posts} p
                                INNER JOIN {$this->wpdb->postmeta} pm
                                ON p.ID = pm.post_id
                                WHERE pm.meta_key = 'lesson:migrate:id'
                                AND pm.meta_value = l.ID
                            )
                            ",
                            $this->batch_size
                        )
                    )
                ),
                'migrated' => intval( $this->wpdb->get_var( 
                        $this->wpdb->prepare(
                            "SELECT COUNT(*)
                            FROM {$this->table} l
                            WHERE EXISTS (
                                SELECT 1
                                FROM {$this->wpdb->posts} p
                                INNER JOIN {$this->wpdb->postmeta} pm
                                ON p.ID = pm.post_id
                                WHERE pm.meta_key = 'lesson:migrate:id'
                                AND pm.meta_value = l.ID
                            )
                            ",
                            $this->batch_size
                        )
                    )
                ),
            ];
        }
        else if ( $this->collection_class === PostLessonCollection::class ) {
            return [
                'left' => intval( $this->wpdb->get_var( 
                    $this->wpdb->prepare(
                        "SELECT COUNT(*)
                        FROM {$this->wpdb->posts} l
                        WHERE 
                        l.post_type = 'academy_lessons' AND
                        NOT EXISTS (
                            SELECT 1
                            FROM {$this->table} le
                            INNER JOIN {$this->meta_table} lm
                            ON le.ID = lm.lesson_id
                            WHERE lm.meta_key = 'lesson:migrate:id'
                            AND lm.meta_value = l.ID
                        )
                        ",
                        $this->batch_size
                    ) )
                ),
                'migrated' => intval( $this->wpdb->get_var( 
                    $this->wpdb->prepare(
                        "SELECT COUNT(*)
                        FROM {$this->wpdb->posts} l
                        WHERE 
                        l.post_type = 'academy_lessons' AND
                        EXISTS (
                            SELECT 1
                            FROM {$this->table} le
                            INNER JOIN {$this->meta_table} lm
                            ON le.ID = lm.lesson_id
                            WHERE lm.meta_key = 'lesson:migrate:id'
                            AND lm.meta_value = l.ID
                        )
                        ",
                        $this->batch_size
                    ) )
                )
            ];
        }
    }
}