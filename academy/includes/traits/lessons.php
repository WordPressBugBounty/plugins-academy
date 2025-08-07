<?php
namespace Academy\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Throwable;
use Academy\Lesson\LessonApi\Lesson as LessonApi;
trait Lessons {

	public static function get_lessons( $offset = 0, $per_page = -1, $author_id = 0, $search_keyword = '', $lesson_status = '' ) {
		$lessons = LessonApi::get( $offset, $per_page, $author_id, $search_keyword, $lesson_status, true );
		$data = [];
		$total = count( $lessons );
		if ( $total > 0 ) {
			foreach ( $lessons as $lesson ) {
				$data[] = (object) $lesson->get_data();
			}
		}

		return $data;
	}

	public static function get_lesson( int $ID, bool $return_array = false ) {
		try {
			$lesson = LessonApi::get_by_id( $ID, true );
			return $return_array ? $lesson->get_data() : (object) $lesson->get_data();
		} catch ( Throwable $e ) {
			return null;
		}
	}
	public static function get_lesson_slug( int $ID ) {
		return LessonApi::get_lesson_slug( $ID );
	}
	public static function get_lesson_meta_data( int $ID ) : array {
		return LessonApi::get_lesson_meta_data( $ID );
	}
	public static function get_lesson_meta( int $ID, string $meta_key ) {
		return LessonApi::get_lesson_meta( $ID, $meta_key );
	}

	public static function get_lesson_video_duration( $lesson_id ) {
		if ( $lesson_id ) {
			$video_duration = self::get_lesson_meta( $lesson_id, 'video_duration' );
			$duration = is_array( $video_duration ) ? $video_duration : (array) json_decode( $video_duration );
			if ( is_array( $duration ) && ( $duration['hours'] || $duration['minutes'] || $duration['seconds'] ) ) {
				$video_duration = array_map(function ( $number ) {
					return sprintf( '%02d', $number );
				}, $duration);
				return implode( ':', $video_duration );
			}
			return '';
		}
		return '';
	}

	public static function get_total_number_of_lessons_by_instructor( int $instructor_id ) : int {
		return (int) self::get_total_number_of_lessons( 'publish', $instructor_id );
	}

	public static function get_total_number_of_lessons( string $status = 'any', int $user_id = 0 ) : int {
		return LessonApi::get_total_number_of_lessons( $status, $user_id );
	}

	public static function generate_unique_lesson_slug( $title ) {
		$slug = sanitize_title( $title );
		$original_slug = $slug;
		$suffix = 2;

		while ( self::is_lesson_slug_exists( $slug ) ) {
			$slug = $original_slug . '-' . $suffix;
			$suffix++;
		}

		return $slug;
	}

	public static function is_lesson_slug_exists( string $slug, ?int $id = null ) : bool {
		try {
			if ( ! empty( $id ) ) {
				$lesson = LessonApi::get_by_id( $id );
				$lesson->set_data([
					'lesson_name' => $slug
				]);
			} else {
				$lesson = LessonApi::create( [
					'lesson_name' => $slug
				], [] );
			}

			return ! $lesson->is_slug_available();
		} catch ( Throwable $e ) {
			return false;
		}
		return false;
	}

	public static function get_lesson_by_slug( string $slug ) : ?array {
		try {
			$lesson = LessonApi::get_by_slug( $slug );
			return $lesson->get_data();
		} catch ( Throwable $e ) {
			return null;
		}
		return null;
	}

	public static function get_lesson_by_title( string $title ) : ?array {
		try {
			$lesson = LessonApi::get_by_title( $title );
			return $lesson->get_data();
		} catch ( Throwable $e ) {
			return null;
		}
		return null;
	}

	public static function get_youtube_video_id( $url ) {
		preg_match( '/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([^"&?\/ ]{11})/', $url, $matches );
		return $matches[1] ?? null;
	}
}
