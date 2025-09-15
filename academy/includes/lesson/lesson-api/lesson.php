<?php
namespace Academy\Lesson\LessonApi;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Lesson\LessonApi\Models\Base\Lesson as LessonBaseModel;
class Lesson {
	protected static ?bool $is_hp = null;

	public static function is_hp( ?bool $is_hp = null ) {
		if ( is_bool( $is_hp ) ) {
			self::$is_hp = $is_hp;
		} elseif ( is_null( $is_hp ) && is_null( self::$is_hp ) ) {
			self::$is_hp = boolval( $GLOBALS['academy_settings']->academy_is_hp_lesson_active ?? true );
		}
		return self::$is_hp;
	}

	public static function get(
		int $page = 1,
		int $per_page = 10,
		?int $author_id = null,
		?string $search = '',
		?string $status = 'publish',
		bool $skip_meta = false,
		array $by_meta = []
	) : Collection\Base\Collection {
		$class = self::is_hp() ? Collection\HpLessonCollection::class : Collection\PostLessonCollection::class;
		$status = $status === 'any' ? null : $status;
		return new $class( $page, $per_page, $author_id, $search, $status, $skip_meta, $by_meta );
	}

	public static function get_by_id( int $id, bool $skip_meta = false, int $author = null, $status = null ) : LessonBaseModel {
		$class = self::is_hp() ? Models\HpLesson::class : Models\PostLesson::class;
		return $class::by_id( $id, $skip_meta, $author, $status );
	}

	public static function get_by_slug( string $slug, bool $skip_meta = false, int $author = null ) : LessonBaseModel {
		$class = self::is_hp() ? Models\HpLesson::class : Models\PostLesson::class;
		return $class::by_slug( $slug, $skip_meta, $author );
	}
	public static function get_by_title( string $title, bool $skip_meta = false, int $author = null ) : LessonBaseModel {
		$class = self::is_hp() ? Models\HpLesson::class : Models\PostLesson::class;
		return $class::by_title( $title, $skip_meta, $author );
	}

	public static function create( array $data = [], array $meta_data = [] ) : LessonBaseModel {
		$class = self::is_hp() ? Models\HpLesson::class : Models\PostLesson::class;
		$lesson = new $class( $data, $meta_data );
		return $lesson;
	}

	public static function get_total_number_of_lessons( string $status = 'any', int $user_id = 0 ) : int {
		$class = self::is_hp() ? Models\HpLesson::class : Models\PostLesson::class;
		return $class::get_total_number_of_lessons( $status, $user_id );
	}

	public static function get_lesson_slug( int $id ) : ?string {
		$class = self::is_hp() ? Models\HpLesson::class : Models\PostLesson::class;
		return $class::get_slug_by_id( $id );
	}
	public static function get_lesson_meta_data( int $id ) : array {
		$class = self::is_hp() ? Models\HpLesson::class : Models\PostLesson::class;
		return $class::get_lesson_meta_data( $id );
	}
	public static function get_lesson_meta( int $id, string $key ) {
		$class = self::is_hp() ? Models\HpLesson::class : Models\PostLesson::class;
		return $class::get_lesson_meta( $id, $key );
	}
}
