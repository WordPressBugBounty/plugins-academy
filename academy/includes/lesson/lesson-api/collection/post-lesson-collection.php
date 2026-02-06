<?php
namespace Academy\Lesson\LessonApi\Collection;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Exception;
use ArrayIterator;
use WP_Query;
use Academy\Lesson\LessonApi\Models\PostLesson;
class PostLessonCollection extends Base\Collection {
	protected WP_Query $query;
	public function __construct( int $page = 1, int $per_page = 10, ?int $author_id = null, ?string $search = '', ?string $status = 'publish', bool $skip_meta = false, array $by_meta = [] ) {
		parent::__construct();
		$this->page     = absint( $page );
		$this->per_page = $per_page;
		$this->skip_meta = $skip_meta;
		$args = [
			'posts_per_page' => $this->per_page,
			'paged' => $this->page,
			'post_type' => 'academy_lessons',
		];

		if ( ! empty( $author_id ) ) {
			$args['author'] = $author_id;
		}

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		if ( ! empty( $status ) ) {
			$args['post_status'] = $status;
		}

		$by_meta = array_merge( $by_meta, apply_filters( 'academy/lesson/meta_query', [] ) );
		if ( ! empty( $by_meta ) ) {
			$args['meta_query'] = $by_meta;
		}

		$this->query = new WP_Query( $args );

		if ( $this->query->have_posts() ) {
			$this->lessons = (array) $this->query->posts;
		} else {
			$this->lessons = [];
		}

		$this->total = count( $this );
		$this->total_pages = absint( $this->query->max_num_pages );
		$this->offset = 0;
		$this->load_meta();
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
			$this->wpdb->prepare(// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				"SELECT * FROM {$this->wpdb->postmeta} WHERE post_id IN ({$placeholder}) ", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				...$ids, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			),
			ARRAY_A
		) ?? [];
		foreach ( $meta_data as $meta ) {
			$this->meta_data[ $meta['post_id'] ][ $meta['meta_key'] ] = $meta['meta_value'];
		}
		$lessons = [];
		foreach ( $this->lessons as $lesson ) {
			$lesson->meta = $this->meta_data[ $lesson->ID ] ?? [];
			$lessons[] = new PostLesson( (array) $lesson, (array) $lesson->meta );
		}
		$this->lessons = $lessons;
	}
	public function count() : int {
		return absint( $this->query->found_posts );
	}
}
