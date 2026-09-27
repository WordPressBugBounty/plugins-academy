<?php
namespace Academy\LearnPage;

use Academy\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * What the learn page blocks show for the current request: the course, its
 * curriculum with each topic's link and progress, and the topic on screen.
 * Built once per request and shared by every block.
 */
class Data {

	/**
	 * The data for this request.
	 *
	 * @var array|null
	 */
	private static $current = null;

	/**
	 * Topic types and the icon each one uses.
	 */
	const ICONS = [
		'lesson'         => 'lesson',
		'quiz'           => 'quiz',
		'quizpress_quiz' => 'quiz',
		'assignment'     => 'assignment',
		'meeting'        => 'meeting',
		'zoom'           => 'meeting',
		'booking'        => 'booking',
	];

	/**
	 * The data for this request.
	 *
	 * @return array
	 */
	public static function current() {
		if ( null === self::$current ) {
			self::$current = self::build();
		}

		return self::$current;
	}

	/**
	 * Forget the data, so it is read again (tests, previews).
	 *
	 * @return void
	 */
	public static function reset() {
		self::$current = null;
	}

	/**
	 * A stable key for a topic.
	 *
	 * @param string     $type Topic type.
	 * @param int|string $id   Topic ID.
	 * @return string
	 */
	public static function key( $type, $id ) {
		return sanitize_key( $type ) . '-' . absint( $id );
	}

	/**
	 * Read everything the page needs.
	 *
	 * @return array
	 */
	private static function build() {
		self::maybe_preview();
		$course_id = (int) Helper::get_the_current_course_id();
		$type      = sanitize_key( (string) get_query_var( 'curriculum_type' ) );
		$slug      = (string) get_query_var( 'name' );
		$topic_id  = $type && $slug ? (int) Helper::get_topic_id_by_topic_name_and_topic_type( $slug, $type ) : 0;
		$user_id   = get_current_user_id();

		$sections = [];
		$flat     = [];
		if ( $course_id ) {
			foreach ( (array) Helper::get_course_curriculum( $course_id, true ) as $index => $curriculum ) {
				$items = [];
				foreach ( (array) ( $curriculum['topics'] ?? [] ) as $topic ) {
					if ( 'sub-curriculum' === ( $topic['type'] ?? '' ) ) {
						$children = [];
						foreach ( (array) ( $topic['topics'] ?? [] ) as $child ) {
							$item       = self::topic( $child, $course_id );
							$children[] = $item;
							$flat[]     = $item;
						}
						$items[] = [
							'kind'   => 'group',
							'key'    => 'group-' . $index . '-' . count( $items ),
							'title'  => (string) ( $topic['name'] ?? '' ),
							'topics' => $children,
						];
						continue;
					}
					$item    = self::topic( $topic, $course_id );
					$items[] = $item;
					$flat[]  = $item;
				}
				$sections[] = [
					'key'    => 'section-' . $index,
					'title'  => (string) ( $curriculum['title'] ?? '' ),
					'items'  => $items,
				];
			}//end foreach
		}//end if

		$current_key = $topic_id ? self::key( $type, $topic_id ) : '';
		$position    = -1;
		foreach ( $flat as $index => $item ) {
			if ( $item['key'] === $current_key ) {
				$position = $index;
				break;
			}
		}
		$completed = count(
			array_filter(
				$flat,
				static function ( $item ) {
					return $item['completed'];
				}
			)
		);
		$total     = count( $flat );

		return [
			'courseId'    => $course_id,
			'courseTitle' => $course_id ? get_the_title( $course_id ) : '',
			'courseUrl'   => $course_id ? (string) get_permalink( $course_id ) : home_url( '/' ),
			'type'        => $type,
			'topicId'     => $topic_id,
			'currentKey'  => $current_key,
			'current'     => $position >= 0 ? $flat[ $position ] : null,
			'previous'    => $position > 0 ? $flat[ $position - 1 ] : null,
			'next'        => $position >= 0 && $position < $total - 1 ? $flat[ $position + 1 ] : null,
			'sections'    => $sections,
			'topics'      => $flat,
			'total'       => $total,
			'completed'   => $completed,
			'percent'     => $total ? (int) round( $completed / $total * 100 ) : 0,
			'loggedIn'    => (bool) $user_id,
			'enrolled'    => $course_id && $user_id ? (bool) Helper::is_enrolled( $course_id, $user_id ) : false,
			'isPublic'    => $course_id ? (bool) Helper::is_public_course( $course_id ) : false,
		];
	}

	/**
	 * Whether the blocks are being drawn for the block editor.
	 *
	 * @return bool
	 */
	public static function is_editor_preview() {
		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST || ! current_user_can( 'edit_posts' ) ) {
			return false;
		}
		$route = isset( $GLOBALS['wp']->query_vars['rest_route'] ) ? (string) $GLOBALS['wp']->query_vars['rest_route'] : '';

		return false !== strpos( $route, '/block-renderer/academy/learn-' );
	}

	/**
	 * In the block editor there is no course in the address, so show one: the
	 * course picked in the editor, or the latest course with topics, on its
	 * first topic. Everything that reads the address then sees that topic.
	 *
	 * @return void
	 */
	private static function maybe_preview() {
		if ( ! self::is_editor_preview() || get_query_var( 'course_name' ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only preview, capability checked.
		$picked = isset( $_GET['academy_preview_course'] ) ? absint( $_GET['academy_preview_course'] ) : 0;
		$ids    = $picked ? [ $picked ] : get_posts(
			[
				'post_type'      => 'academy_courses',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'fields'         => 'ids',
			]
		);
		foreach ( $ids as $course_id ) {
			$course = get_post( $course_id );
			if ( ! $course || 'academy_courses' !== $course->post_type ) {
				continue;
			}
			foreach ( (array) Helper::get_course_curriculum( $course_id, false ) as $section ) {
				foreach ( (array) ( $section['topics'] ?? [] ) as $topic ) {
					if ( 'sub-curriculum' === ( $topic['type'] ?? '' ) ) {
						$topic = current( (array) ( $topic['topics'] ?? [] ) );
					}
					if ( ! is_array( $topic ) || empty( $topic['id'] ) || empty( $topic['type'] ) ) {
						continue;
					}
					$slug = isset( $topic['slug'] ) && '' !== $topic['slug'] ? $topic['slug'] : Helper::get_topics_post_slug( $topic['id'], $topic['type'] );
					set_query_var( 'course_name', $course->post_name );
					set_query_var( 'curriculum_type', $topic['type'] );
					set_query_var( 'name', $slug );
					return;
				}
			}
		}//end foreach
	}

	/**
	 * One topic, as the blocks use it.
	 *
	 * @param array $topic     Topic from the course curriculum.
	 * @param int   $course_id Course ID.
	 * @return array
	 */
	private static function topic( array $topic, $course_id ) {
		$type = sanitize_key( (string) ( $topic['type'] ?? '' ) );
		$id   = absint( $topic['id'] ?? 0 );

		return [
			'kind'       => 'topic',
			'key'        => self::key( $type, $id ),
			'id'         => $id,
			'type'       => $type,
			'title'      => (string) ( $topic['name'] ?? '' ),
			'url'        => self::link( $topic, $course_id ),
			'icon'       => self::ICONS[ $type ] ?? 'lesson',
			'duration'   => (string) ( $topic['duration'] ?? '' ),
			'completed'  => ! empty( $topic['is_completed'] ),
			'accessible' => ! empty( $topic['is_accessible'] ),
		];
	}

	/**
	 * The page a topic is on. The block learn page always uses the topic's own
	 * URL, whatever the other learn pages link to.
	 *
	 * @param array $topic     Topic.
	 * @param int   $course_id Course ID.
	 * @return string
	 */
	public static function link( array $topic, $course_id ) {
		$type = (string) ( $topic['type'] ?? '' );
		$id   = absint( $topic['id'] ?? 0 );
		$slug = isset( $topic['slug'] ) && '' !== $topic['slug'] ? (string) $topic['slug'] : (string) Helper::get_topics_post_slug( $id, $type );
		$post = get_post( $course_id );
		if ( ! $post || '' === $slug ) {
			return '';
		}
		$permalinks = Helper::get_permalink_structure();
		$base       = str_replace( '/', '', $permalinks['course_rewrite_slug'] );

		return home_url( user_trailingslashit( "/{$base}/{$post->post_name}/{$type}/{$slug}" ) );
	}
}
