<?php
namespace Academy\Blocks;

use Academy\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Course facts for the course blocks and their editor previews.
 *
 * Facts that need database queries (rating, instructors, lessons, students)
 * are cached per course and cleared when they change. Facts that depend on
 * the visitor or on settings (price, enrolment, links) are worked out on
 * each request.
 */
class CourseData {

	/**
	 * Bump to discard cached facts after their shape changes.
	 */
	const CACHE_VERSION = 2;

	/**
	 * Object cache group.
	 */
	const CACHE_GROUP = 'academy_course_card';

	/**
	 * Gathered data for this request, keyed by course ID.
	 *
	 * @var array
	 */
	private static $request_cache = [];

	/**
	 * Post types shown as courses: courses, plus types added by add-ons such as
	 * course bundles.
	 *
	 * @return string[]
	 */
	public static function post_types() {
		$types = (array) apply_filters( 'academy/get_course_archive_post_types', [ 'academy_courses' ] );
		if ( class_exists( '\AcademyProCourseBundle\Helper' ) && post_type_exists( 'alms_course_bundle' ) ) {
			$types[] = 'alms_course_bundle';
		}

		return array_values( array_unique( array_merge( [ 'academy_courses' ], array_map( 'strval', $types ) ) ) );
	}

	/**
	 * Whether a post is a course (or course bundle).
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_course( $post_id ) {
		return in_array( get_post_type( $post_id ), self::post_types(), true );
	}

	/**
	 * Facts about a course.
	 *
	 * @param int $course_id Course ID.
	 * @return array|null Null when the ID is not a course.
	 */
	public static function get( $course_id ) {
		$course_id = (int) $course_id;
		if ( $course_id <= 0 || ! self::is_course( $course_id ) ) {
			return null;
		}
		if ( isset( self::$request_cache[ $course_id ] ) ) {
			return self::$request_cache[ $course_id ];
		}

		$shared    = self::shared( $course_id );
		$user_id   = get_current_user_id();
		$is_bundle = 'alms_course_bundle' === get_post_type( $course_id );
		$public    = (bool) Helper::get_settings( 'is_show_public_profile' );

		$instructors = [];
		foreach ( $shared['instructors'] as $person ) {
			$instructors[] = [
				'id'     => $person['id'],
				'name'   => $person['name'],
				'avatar' => get_avatar_url( $person['id'], [ 'size' => 96 ] ),
				'url'    => $public ? home_url( '/author/' . $person['nicename'] ) : '',
			];
		}

		if ( $is_bundle ) {
			$is_enrolled = $user_id && \AcademyProCourseBundle\Helper::is_user_enrolled_in_bundle( $course_id, $user_id );
			$is_paid     = 'paid' === get_post_meta( $course_id, 'academy_course_bundle_type', true );
			$is_public   = false;
			$is_complete = false;
		} else {
			$is_enrolled = $user_id && Helper::is_enrolled( $course_id, $user_id );
			$is_paid     = (bool) Helper::is_course_purchasable( $course_id );
			$is_public   = 'public' === Helper::get_course_type( $course_id );
			$is_complete = $is_enrolled && Helper::is_completed_course( $course_id, $user_id );
		}

		$data = [
			'id'              => $course_id,
			'type'            => $is_bundle ? 'bundle' : 'course',
			'url'             => get_permalink( $course_id ),
			'price'           => self::price_html( $course_id ),
			'is_paid'         => $is_paid,
			'is_public'       => $is_public,
			'is_sticky'       => ! $is_bundle && Helper::is_course_sticky( $course_id ),
			'reviews_enabled' => (bool) Helper::get_settings( 'is_enabled_course_review', true ) && ! get_post_meta( $course_id, 'academy_is_disabled_course_review', true ),
			'rating'          => $shared['rating'],
			'instructors'     => $instructors,
			'lessons'         => $shared['lessons'],
			'duration'        => $shared['duration'],
			'level'           => $shared['level'],
			'enrolled'        => $shared['enrolled'],
			'is_enrolled'     => (bool) $is_enrolled,
			'is_complete'     => (bool) $is_complete,
			'continue_url'    => ( $is_enrolled || $is_public ) && ! $is_bundle ? Helper::get_start_course_permalink( $course_id ) : '',
		];

		/**
		 * Filters the course facts used by the Academy LMS blocks.
		 *
		 * @param array $data      Course facts.
		 * @param int   $course_id Course ID.
		 */
		self::$request_cache[ $course_id ] = (array) apply_filters( 'academy/blocks/course_data', $data, $course_id );

		return self::$request_cache[ $course_id ];
	}

	/**
	 * Cached facts that do not depend on the visitor.
	 *
	 * @param int $course_id Course ID.
	 * @return array
	 */
	private static function shared( $course_id ) {
		$cached = wp_cache_get( $course_id, self::CACHE_GROUP );
		if ( false === $cached ) {
			$cached = get_transient( self::transient_key( $course_id ) );
		}
		if ( is_array( $cached ) && isset( $cached['v'] ) && self::CACHE_VERSION === $cached['v'] ) {
			wp_cache_set( $course_id, $cached, self::CACHE_GROUP );
			return $cached;
		}

		$is_bundle = 'alms_course_bundle' === get_post_type( $course_id );
		$rating    = (object) Helper::get_course_rating( $course_id );

		$ids = [];
		if ( ! $is_bundle ) {
			foreach ( (array) Helper::get_instructors_by_course_id( $course_id ) as $row ) {
				if ( is_object( $row ) && isset( $row->ID ) ) {
					$ids[] = (int) $row->ID;
				}
			}
		}
		if ( ! $ids ) {
			$ids[] = (int) get_post_field( 'post_author', $course_id );
		}
		$people = [];
		foreach ( array_unique( array_filter( $ids ) ) as $id ) {
			$user = get_userdata( $id );
			if ( $user ) {
				$people[] = [
					'id'       => $id,
					'name'     => $user->display_name,
					'nicename' => $user->user_nicename,
				];
			}
		}

		if ( $is_bundle ) {
			$lessons  = (int) \AcademyProCourseBundle\Helper::get_bundle_lessons( $course_id );
			$duration = self::format_clock_duration( (string) \AcademyProCourseBundle\Helper::get_bundle_duration( $course_id ) );
			$level    = '';
			$enrolled = (int) \AcademyProCourseBundle\Helper::get_bundle_enrolled( $course_id );
		} else {
			$lessons  = self::lesson_count( $course_id );
			$duration = (string) Helper::get_course_duration( $course_id );
			$level    = (string) Helper::get_course_difficulty_level( $course_id );
			$enrolled = (int) Helper::count_course_enrolled( $course_id );
		}

		$cached = [
			'v'           => self::CACHE_VERSION,
			'rating'      => [
				'average' => isset( $rating->rating_avg ) ? (float) $rating->rating_avg : 0.0,
				'count'   => isset( $rating->rating_count ) ? (int) $rating->rating_count : 0,
			],
			'instructors' => $people,
			'lessons'     => $lessons,
			'duration'    => $duration,
			'level'       => $level,
			'enrolled'    => $enrolled,
		];

		set_transient( self::transient_key( $course_id ), $cached, DAY_IN_SECONDS );
		wp_cache_set( $course_id, $cached, self::CACHE_GROUP );

		return $cached;
	}

	/**
	 * Transient name for a course's cached facts.
	 *
	 * @param int $course_id Course ID.
	 * @return string
	 */
	private static function transient_key( $course_id ) {
		return 'academy_course_card_' . (int) $course_id;
	}

	/**
	 * Clear a course's cached facts.
	 *
	 * @param int $course_id Course ID.
	 * @return void
	 */
	public static function flush( $course_id ) {
		$course_id = (int) $course_id;
		if ( $course_id <= 0 ) {
			return;
		}
		delete_transient( self::transient_key( $course_id ) );
		wp_cache_delete( $course_id, self::CACHE_GROUP );
		unset( self::$request_cache[ $course_id ] );
	}

	/**
	 * Clear cached facts when a course, its reviews, enrolments or instructors change.
	 *
	 * @return void
	 */
	public static function register_cache_hooks() {
		add_action(
			'save_post',
			function ( $post_id, $post ) {
				if ( in_array( $post->post_type, self::post_types(), true ) ) {
					self::flush( $post_id );
				} elseif ( 'academy_enrolled' === $post->post_type ) {
					self::flush( $post->post_parent );
				}
			},
			10,
			2
		);
		add_action(
			'deleted_post',
			function ( $post_id, $post ) {
				if ( $post instanceof \WP_Post && 'academy_enrolled' === $post->post_type ) {
					self::flush( $post->post_parent );
				}
			},
			10,
			2
		);
		add_action(
			'transition_post_status',
			function ( $new_status, $old_status, $post ) {
				if ( $new_status !== $old_status && 'academy_enrolled' === $post->post_type ) {
					self::flush( $post->post_parent );
				}
			},
			10,
			3
		);
		// Fires whenever a review is added, approved, unapproved or deleted.
		add_action(
			'wp_update_comment_count',
			function ( $post_id ) {
				if ( self::is_course( $post_id ) ) {
					self::flush( $post_id );
				}
			}
		);
		$instructor_change = function ( $meta_id, $user_id, $meta_key, $meta_value ) {
			if ( 'academy_instructor_course_id' === $meta_key ) {
				self::flush( (int) $meta_value );
			}
		};
		add_action( 'added_user_meta', $instructor_change, 10, 4 );
		add_action( 'deleted_user_meta', $instructor_change, 10, 4 );
		add_action(
			'profile_update',
			function ( $user_id ) {
				foreach ( (array) get_user_meta( $user_id, 'academy_instructor_course_id', false ) as $course_id ) {
					self::flush( (int) $course_id );
				}
			}
		);
	}

	/**
	 * Price shown on course cards: the store price, "Free", "Public" or "Paid".
	 *
	 * @param int $course_id Course ID.
	 * @return string Sanitized HTML.
	 */
	public static function price_html( $course_id ) {
		$price = '';

		if ( 'alms_course_bundle' === get_post_type( $course_id ) ) {
			$is_paid     = 'paid' === get_post_meta( $course_id, 'academy_course_bundle_type', true );
			$course_type = $is_paid ? 'paid' : 'free';
			$product_id  = $is_paid ? \AcademyProCourseBundle\Helper::get_bundle_product_id( $course_id ) : 0;
		} else {
			$course_type = Helper::get_course_type( $course_id );
			$is_paid     = Helper::is_course_purchasable( $course_id );
			$product_id  = $is_paid ? Helper::get_course_product_id( $course_id ) : 0;
		}

		if ( $product_id && Helper::is_active_woocommerce() && function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$price = $product->get_price_html();
			}
		}
		if ( $is_paid && empty( $price ) && 'alms_course_bundle' !== get_post_type( $course_id ) ) {
			$price = Helper::get_plain_course_price_html( $course_id );
		}

		if ( $is_paid && $price ) {
			$label = $price;
		} elseif ( $is_paid ) {
			$label = esc_html__( 'Paid', 'academy' );
		} elseif ( 'public' === $course_type ) {
			$label = esc_html__( 'Public', 'academy' );
		} else {
			$label = esc_html__( 'Free', 'academy' );
		}

		return wp_kses_post( apply_filters( 'academy/templates/loop/price', $label, $course_id ) );
	}

	/**
	 * Number of lessons in the course curriculum.
	 *
	 * @param int $course_id Course ID.
	 * @return int
	 */
	private static function lesson_count( $course_id ) {
		$count = Helper::get_total_number_of_course_lesson( $course_id );
		if ( is_array( $count ) ) {
			$count = isset( $count['total_lessons'] ) ? $count['total_lessons'] : reset( $count );
		}

		return (int) $count;
	}

	/**
	 * "02:30:00" to "2 hr 30 mins", matching Helper::get_course_duration().
	 *
	 * @param string $clock Duration as HH:MM:SS.
	 * @return string
	 */
	private static function format_clock_duration( $clock ) {
		$parts = array_map( 'intval', explode( ':', $clock ) );
		if ( 3 !== count( $parts ) || 0 === array_sum( $parts ) ) {
			return '';
		}

		$out = [];
		if ( $parts[0] > 0 ) {
			$out[] = $parts[0] . ' hr';
		}
		if ( $parts[1] > 0 ) {
			$out[] = $parts[1] . ' mins';
		}
		if ( $parts[2] > 0 ) {
			$out[] = $parts[2] . ' sec';
		}

		return implode( ' ', $out );
	}

	/**
	 * Inline SVG icon for course facts.
	 *
	 * @param string $name lessons|duration|level|enrolled.
	 * @return string
	 */
	public static function icon( $name ) {
		$paths = [
			'lessons'  => '<path d="M2 4h7a3 3 0 0 1 3 3v13a2 2 0 0 0-2-2H2zM22 4h-7a3 3 0 0 0-3 3v13a2 2 0 0 1 2-2h8z"/>',
			'duration' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
			'level'    => '<path d="M6 20v-6M12 20V9M18 20V4"/>',
			'enrolled' => '<path d="M16 20v-1a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v1"/><circle cx="9" cy="8" r="3.5"/><path d="M22 20v-1a4 4 0 0 0-3-3.87M16 4.13a3.5 3.5 0 0 1 0 6.75"/>',
		];
		if ( ! isset( $paths[ $name ] ) ) {
			return '';
		}

		return '<svg class="academy-course-meta__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
	}
}
