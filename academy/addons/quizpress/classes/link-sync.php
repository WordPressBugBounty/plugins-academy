<?php
namespace AcademyQuizpress\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps the {prefix}academy_quizpress_quiz_links table in sync with each
 * course's `academy_course_curriculum` meta. Deliberately hooks the generic
 * core WP meta hooks (rather than an Academy-specific hook) so this stays
 * entirely self-contained inside the addon — no core-file edit required.
 */
class LinkSync {

	const META_KEY = 'academy_course_curriculum';

	public static function init() {
		$self = new self();
		add_action( 'added_post_meta', array( $self, 'maybe_sync' ), 10, 4 );
		add_action( 'updated_post_meta', array( $self, 'maybe_sync' ), 10, 4 );
		add_action( 'deleted_post_meta', array( $self, 'maybe_sync_on_delete' ), 10, 4 );
		add_action( 'before_delete_post', array( $self, 'cleanup_course' ) );
	}

	public function maybe_sync( $meta_id, $course_id, $meta_key, $meta_value ) {
		if ( self::META_KEY !== $meta_key ) {
			return;
		}
		self::sync( $course_id, is_array( $meta_value ) ? $meta_value : array() );
	}

	public function maybe_sync_on_delete( $meta_ids, $course_id, $meta_key, $meta_value ) {
		if ( self::META_KEY !== $meta_key ) {
			return;
		}
		self::sync( $course_id, array() );
	}

	public function cleanup_course( $post_id ) {
		if ( 'academy_courses' !== get_post_type( $post_id ) ) {
			return;
		}
		self::sync( $post_id, array() );
	}

	/**
	 * Rebuilds this course's quiz links from scratch — delete then re-insert
	 * whatever quizpress_quiz topics are currently in the curriculum. Simplest
	 * way to guarantee no stale rows survive a curriculum edit.
	 *
	 * @param int   $course_id
	 * @param mixed $curriculum
	 */
	public static function sync( $course_id, $curriculum ) {
		global $wpdb;
		$table = $wpdb->prefix . ACADEMY_PLUGIN_SLUG . '_quizpress_quiz_links';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $table, array( 'course_id' => (int) $course_id ), array( '%d' ) );

		foreach ( self::extract_quizpress_topics( $curriculum ) as $topic ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert(
				$table,
				array(
					'quiz_id'   => (int) $topic['id'],
					'course_id' => (int) $course_id,
					'topic_id'  => (int) $topic['id'],
				),
				array( '%d', '%d', '%d' )
			);
		}
	}

	/**
	 * Walks both top-level and nested (sub-curriculum) topics for anything of
	 * type quizpress_quiz.
	 *
	 * @param mixed $curriculum
	 */
	private static function extract_quizpress_topics( $curriculum ) {
		$found = array();
		if ( ! is_array( $curriculum ) ) {
			return $found;
		}

		foreach ( $curriculum as $group ) {
			if ( empty( $group['topics'] ) || ! is_array( $group['topics'] ) ) {
				continue;
			}
			foreach ( $group['topics'] as $topic ) {
				if ( isset( $topic['type'], $topic['id'] ) && 'quizpress_quiz' === $topic['type'] ) {
					$found[] = $topic;
				}
				if ( ! empty( $topic['topics'] ) && is_array( $topic['topics'] ) ) {
					foreach ( $topic['topics'] as $child_topic ) {
						if ( isset( $child_topic['type'], $child_topic['id'] ) && 'quizpress_quiz' === $child_topic['type'] ) {
							$found[] = $child_topic;
						}
					}
				}
			}
		}

		return $found;
	}
}
