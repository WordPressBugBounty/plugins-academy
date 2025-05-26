<?php
namespace Academy\Lesson\LessonApi\Collection\Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Exception;
use Countable;
use IteratorAggregate;
use ArrayIterator;
use Academy\Lesson\LessonApi\Common\Db;
abstract class Collection extends Db implements Countable, IteratorAggregate {
	protected int $page;
	protected int $per_page;
	protected int $total;
	protected int $total_pages;
	protected int $offset;
	protected int $author_id;
	protected string $search;
	protected string $status;
	protected array $lessons = [];
	protected array $meta_data = [];
	protected bool $skip_meta = false;

	public function get_page() : int {
		return $this->pagel;
	}
	public function get_per_page() : int {
		return $this->per_page;
	}
	public function get_total() : int {
		return $this->total;
	}
	public function get_total_pages() : int {
		return $this->total_pages;
	}
	public function get_offset() : int {
		return $this->offset;
	}
	abstract public function getIterator() : ArrayIterator;
	abstract public function load_meta() : void;
	abstract public function count() : int;
}
