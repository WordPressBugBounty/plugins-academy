<?php
namespace Academy\Lesson\LessonMigration\Migrator\Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Academy\Lesson\LessonApi\Common\Db;
use Academy\Lesson\LessonApi\Models\Base\Lesson;
abstract class Migrator extends Db {
	public const KEY = 'lesson:migrate:id';
	public Lesson $from;
	public Lesson $to;
	public function __construct( Lesson $from ) {
		parent::__construct();
		$this->from = $from;
	}
	abstract public function migrate() : void;
	abstract protected function is_migrated() : bool;
}
