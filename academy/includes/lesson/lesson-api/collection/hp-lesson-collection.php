<?php
namespace Academy\Lesson\LessonApi\Collection;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Exception;
use ArrayIterator;
use Academy\Lesson\LessonApi\Models\HpLesson;
use Academy\Lesson\LessonApi\Common\HpWhere;
class HpLessonCollection extends Base\Collection {
	protected array $where = [];
	protected array $by_meta = [];
	public function __construct( int $page = 1, int $per_page = 10, ?int $author_id = null, ?string $search = '', ?string $status = 'publish', bool $skip_meta = false, array $by_meta = [] ) {
		parent::__construct();
		$this->page     = absint( $page );
		$this->per_page = $per_page;
		$this->offset = absint( ( $this->page - 1 ) * $this->per_page );
		$this->skip_meta = $skip_meta;
		$this->by_meta = $by_meta;
		if ( ! empty( $author_id ) ) {
			$this->where[] = $this->wpdb->prepare( 'lesson_author = %d', $author_id );
		}

		if ( ! empty( $search ) ) {
			$this->where[] = $this->wpdb->prepare( 'lesson_title LIKE %s', "%{$search}%" );
		}

		if ( ! empty( $status ) ) {
			$this->where[] = $this->wpdb->prepare( 'lesson_status = %s', $status );
		}

		$this->by_meta = array_merge( $by_meta, apply_filters( 'academy/lesson/meta_query', [] ) );
		if ( ! empty( $this->by_meta ) ) {
			$ins = new HpWhere( $this->by_meta, 'AND', 'lm' );
			$this->where[] = $this->wpdb->prepare( $ins->query(), ...$ins->values() );
		}
		$limit_query = $per_page === -1 ? '' : $this->wpdb->prepare( ' LIMIT %d OFFSET %d ', $this->per_page, $this->offset );
		$this->where = array_filter( $this->where );
		$this->lessons = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT l.* FROM {$this->table} l " . ( empty( $this->by_meta ) ? '' : $this->join() ) . ( empty( $this->where ) ? '' : 'WHERE ' . implode( ' AND ', $this->where ) ) . ' GROUP BY (l.ID) ORDER BY l.ID DESC' . $limit_query
			),
			ARRAY_A
		) ?? [];

		$this->load_meta();
		$this->total = count( $this );
		$this->total_pages = ceil( $this->total / $this->per_page );
	}
	public function join() : string {
		return " JOIN {$this->meta_table} lm ON l.ID = lm.lesson_id ";
	}
	public function getIterator() : ArrayIterator {
		return new ArrayIterator( $this->lessons );
	}
	public function load_meta() : void {
		$ids = array_map( 'absint', array_column( $this->lessons, 'ID' ) );
		if ( empty( $ids ) ) {
			$this->lessons = [];
			return;
		}
		$placeholder = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$meta_data = $this->skip_meta ? [] : $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->meta_table} WHERE lesson_id IN ({$placeholder}) ",
				...$ids,
			),
			ARRAY_A
		) ?? [];
		foreach ( $meta_data as $meta ) {
			$this->meta_data[ $meta['lesson_id'] ][ $meta['meta_key'] ] = $meta['meta_value'];
		}

		$lessons = [];
		foreach ( $this->lessons as $lesson ) {
			$lesson['meta'] = $this->meta_data[ $lesson['ID'] ] ?? [];
			$lessons[] = new HpLesson( $lesson, $lesson['meta'] );
		}
		$this->lessons = $lessons;
	}

	public function count() : int {
		return $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(DISTINCT l.ID) FROM {$this->table} l " . ( empty( $this->by_meta ) ? '' : $this->join() ) . ( empty( $this->where ) ? '' : 'WHERE ' . implode( ' AND ', $this->where ) )
			)
		) ?? 0;
	}
}
