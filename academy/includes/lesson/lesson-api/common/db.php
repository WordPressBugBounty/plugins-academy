<?php
namespace Academy\Lesson\LessonApi\Common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class Db {
	public string $table = 'academy_lessons';
	public string $meta_table = 'academy_lessonmeta';
	public object $wpdb;
	public function __construct() {
		$this->wpdb = $GLOBALS['wpdb'];
		$this->table = $this->wpdb->prefix . $this->table;
		$this->meta_table = $this->wpdb->prefix . $this->meta_table;
	}
}
