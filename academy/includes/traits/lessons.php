<?php
namespace Academy\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Lessons {

	public static function get_lessons( $offset = 0, $per_page = -1, $author_id = 0, $search_keyword = '', $lesson_status = '' ) {
		global $wpdb;

		$query = "SELECT * FROM {$wpdb->prefix}academy_lessons";

		if ( $search_keyword || $author_id || $lesson_status ) {
			$query .= ' WHERE';

			if ( ! empty( $search_keyword ) ) {
				$wild = '%';
				$like = $wild . $wpdb->esc_like( $search_keyword ) . $wild;
				$query .= $wpdb->prepare( ' lesson_title LIKE %s', $like );

				if ( ! empty( $lesson_status ) ) {
					$query .= ' AND';
				}
			}

			if ( ! empty( $author_id ) ) {
				if ( ! empty( $lesson_status ) && 'any' !== $lesson_status ) {
					$query .= $wpdb->prepare( ' lesson_author = %d AND lesson_status = %s', $author_id, $lesson_status );
				} else {
					$query .= $wpdb->prepare( ' lesson_author = %d AND lesson_status != %s', $author_id, 'trash' );
				}
			} else {
				if ( ! empty( $lesson_status ) && 'any' !== $lesson_status ) {
					$query .= $wpdb->prepare( ' lesson_status = %s', $lesson_status );
				} else {
					$query .= $wpdb->prepare( ' lesson_status != %s', 'trash' );
				}
			}
		}//end if

		$query .= ' ORDER BY lesson_date DESC';
		if ( -1 !== $per_page ) {
			$query .= $wpdb->prepare( ' LIMIT %d, %d', $offset, $per_page );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $query, OBJECT );
	}

	public static function get_lesson( $ID ) {
		global $wpdb;
		$lesson  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}academy_lessons WHERE ID=%d", $ID ), OBJECT );
		return current( $lesson );
	}
	public static function get_lesson_slug( $ID ) {
		global $wpdb;
		$lesson  = $wpdb->get_results( $wpdb->prepare( "SELECT lesson_name FROM {$wpdb->prefix}academy_lessons WHERE ID=%d", $ID ), OBJECT );
		return current( $lesson )->lesson_name;
	}
	public static function get_lesson_meta_data( $ID ) {
		global $wpdb;
		$meta_data = [];
		$results = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->prefix}academy_lessonmeta WHERE lesson_id=%d", $ID ), OBJECT );
		foreach ( $results as $result ) {
			$meta_data[ $result->meta_key ] = json_decode( $result->meta_value, true );
		}
		return $meta_data;
	}
	public static function get_lesson_meta( $ID, $meta_key ) {
		global $wpdb;
		$lesson_meta = current( $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->prefix}academy_lessonmeta WHERE lesson_id=%d AND meta_key=%s", $ID, $meta_key ), OBJECT ) );
		if ( $lesson_meta ) {
			return json_decode( $lesson_meta->meta_value, true );
		}
		return null;
	}

	public static function get_lesson_video_duration( $lesson_id ) {
		if ( $lesson_id ) {
			$video_duration = \Academy\Helper::get_lesson_meta( $lesson_id, 'video_duration' );
			if ( is_array( $video_duration ) && ( $video_duration['hours'] || $video_duration['minutes'] || $video_duration['seconds'] ) ) {
				$video_duration = array_map(function ( $number ) {
					return sprintf( '%02d', $number );
				}, $video_duration);
				return implode( ':', $video_duration );
			}
			return '';
		}
		return '';
	}

	public static function get_total_number_of_lessons_by_instructor( $instructor_id ) {
		global $wpdb;
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(ID)
			FROM {$wpdb->prefix}academy_lessons lessons 
			WHERE lessons.lesson_author = %d AND lesson_status = %s
			",
				$instructor_id,
				'publish'
			)
		);
		return (int) $count;
	}
	public static function get_total_number_of_lessons( $status = 'any', $user_id = 0 ) {
		global $wpdb;
		$query = "SELECT COUNT(*) FROM {$wpdb->prefix}academy_lessons";
		if ( 'any' !== $status ) {
			$query .= $wpdb->prepare( ' WHERE lesson_status = %s', $status );
		}
		if ( $user_id ) {
			$query .= 'any' === $status ? ' WHERE' : ' AND';
			$query .= $wpdb->prepare( ' lesson_author = %d', $user_id );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_var( $query );
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

	public static function is_lesson_slug_exists( $slug ) {
		global $wpdb;

		$lesson_exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(ID) 
                FROM {$wpdb->prefix}academy_lessons 
                WHERE lesson_name = %s", $slug
			)
		);

		$post_exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(ID) 
                FROM {$wpdb->posts} 
                WHERE post_name = %s", $slug
			)
		);

		return $lesson_exists > 0 || $post_exists > 0;
	}

	public static function get_lesson_by_slug( $slug ) {
		global $wpdb;

		$result = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}academy_lessons 
                WHERE lesson_name = %s", $slug
			)
		);

		return current( $result );
	}
}
