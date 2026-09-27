<?php
namespace Academy\Guardian;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Helper;

/**
 * Canonical guardian ↔ learner store + learning-progress aggregation.
 *
 * This is the single source of truth for the family relationship. Other plugins
 * (Academy Pro, Academy Digital Campus) link/unlink and read children THROUGH
 * this class, and listen to the `academy/guardian/linked|unlinked` actions to
 * stay in sync — they must not write the table directly.
 */
class Store {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . ACADEMY_PLUGIN_SLUG . '_guardian_map';
	}

	/**
	 * Link a guardian to a learner (idempotent upsert). Ensures the guardian
	 * user carries the guardian role, then fires `academy/guardian/linked`.
	 *
	 * @param int   $guardian_id
	 * @param int   $student_id
	 * @param array $args Optional relationship (string) and is_primary (bool).
	 * @return int|\WP_Error The map row id, or WP_Error on invalid input.
	 */
	public static function link( $guardian_id, $student_id, $args = array() ) {
		global $wpdb;
		$guardian_id = (int) $guardian_id;
		$student_id  = (int) $student_id;

		if ( ! $guardian_id || ! $student_id || $guardian_id === $student_id ) {
			return new \WP_Error( 'invalid_link', __( 'A valid guardian and learner are required.', 'academy' ) );
		}
		if ( ! get_user_by( 'id', $guardian_id ) || ! get_user_by( 'id', $student_id ) ) {
			return new \WP_Error( 'invalid_user', __( 'Guardian or learner not found.', 'academy' ) );
		}

		// Ensure the guardian carries the guardian role/capability.
		$guardian_user = get_user_by( 'id', $guardian_id );
		if ( $guardian_user && ! in_array( 'academy_guardian', (array) $guardian_user->roles, true ) ) {
			$guardian_user->add_role( 'academy_guardian' );
		}

		$data = array(
			'guardian_id'  => $guardian_id,
			'student_id'   => $student_id,
			'relationship' => isset( $args['relationship'] ) ? sanitize_text_field( (string) $args['relationship'] ) : null,
			'is_primary'   => ! empty( $args['is_primary'] ) ? 1 : 0,
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->replace( self::table(), $data );
		$id = (int) $wpdb->insert_id;

		/**
		 * Fires after a guardian is linked to a learner. The sync seam: Digital
		 * Campus / Pro listen here to mirror the relationship into their models.
		 */
		do_action( 'academy/guardian/linked', $guardian_id, $student_id, $data );

		return $id;
	}

	/**
	 * Unlink a guardian from a learner. Fires `academy/guardian/unlinked`.
	 *
	 * @param int $guardian_id
	 * @param int $student_id
	 */
	public static function unlink( $guardian_id, $student_id ) {
		global $wpdb;
		$guardian_id = (int) $guardian_id;
		$student_id  = (int) $student_id;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( self::table(), array(
			'guardian_id' => $guardian_id,
			'student_id' => $student_id
		) );

		do_action( 'academy/guardian/unlinked', $guardian_id, $student_id );
		return true;
	}

	/**
	 * Learner (ward) IDs for a guardian.
	 *
	 * @param int $guardian_id
	 *
	 * @return int[]
	 */
	public static function get_children( $guardian_id ) {
		global $wpdb;
		$table = self::table();
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- only $wpdb-prefixed table names / generated placeholders are interpolated; values are prepared
		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare(
			"SELECT student_id FROM {$table} WHERE guardian_id = %d ORDER BY is_primary DESC, id ASC",
			(int) $guardian_id
		) ) );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Guardian IDs for a learner.
	 *
	 * @param int $student_id
	 *
	 * @return int[]
	 */
	public static function get_guardians( $student_id ) {
		global $wpdb;
		$table = self::table();
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- only $wpdb-prefixed table names / generated placeholders are interpolated; values are prepared
		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare(
			"SELECT guardian_id FROM {$table} WHERE student_id = %d",
			(int) $student_id
		) ) );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Whether $user_id (default current user) may view $student_id's records:
	 * a manager, the learner themselves, or a linked guardian.
	 *
	 * @param int   $student_id
	 * @param mixed $user_id
	 */
	public static function can_view( $student_id, $user_id = null ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}
		if ( $user_id === (int) $student_id ) {
			return true;
		}
		return in_array( $user_id, self::get_guardians( $student_id ), true );
	}

	/**
	 * Basic profile for a learner.
	 *
	 * @param int $student_id
	 */
	public static function child_profile( $student_id ) {
		$user = get_user_by( 'id', (int) $student_id );
		return array(
			'id'     => (int) $student_id,
			'name'   => $user ? $user->display_name : '',
			'email'  => $user ? $user->user_email : '',
			'avatar' => get_avatar_url( (int) $student_id ),
		);
	}

	/**
	 * A learner's enrolled courses with per-course progress %, completion state
	 * and whether a certificate has been earned. Read-only aggregation over the
	 * existing core helpers (enrollment CPT + completion comments + topic meta).
	 *
	 * @param int $student_id
	 *
	 * @return array[]
	 */
	public static function child_courses( $student_id ) {
		$student_id   = (int) $student_id;
		$enrolled_ids = (array) Helper::get_enrolled_courses_ids_by_user( $student_id );
		$completed    = array_map( 'intval', (array) Helper::get_completed_courses_ids_by_user( $student_id ) );
		$cert_active  = Helper::get_addon_active_status( 'certificates' );

		$courses = array();
		foreach ( $enrolled_ids as $course_id ) {
			$course_id  = (int) $course_id;
			$is_done    = in_array( $course_id, $completed, true );
			$total      = (int) Helper::get_total_number_of_course_topics( $course_id );
			$done_meta  = get_user_meta( $student_id, 'academy_course_' . $course_id . '_completed_topics', true );
			$done_count = is_array( $done_meta ) ? count( $done_meta ) : (int) ( $is_done ? $total : 0 );
			$percent    = $is_done ? 100 : (int) Helper::calculate_percentage( $total, $done_count );

			$has_cert = false;
			if ( $cert_active && $is_done ) {
				$has_cert = (bool) get_post_meta( $course_id, 'academy_course_certificate_id', true );
			}

			$courses[] = array(
				'course_id'        => $course_id,
				'title'            => get_the_title( $course_id ),
				'permalink'        => get_permalink( $course_id ),
				'thumbnail'        => get_the_post_thumbnail_url( $course_id, 'medium' ) ? get_the_post_thumbnail_url( $course_id, 'medium' ) : '',
				'total_topics'     => $total,
				'completed_topics' => $done_count,
				'progress'         => $percent,
				'is_completed'     => $is_done,
				'has_certificate'  => $has_cert,
			);
		}//end foreach
		return $courses;
	}

	/**
	 * Full snapshot for one learner: profile + courses + rollup counts.
	 *
	 * @param int $student_id
	 */
	public static function child_snapshot( $student_id ) {
		$courses   = self::child_courses( $student_id );
		$completed = count( array_filter( $courses, static function ( $c ) {
			return ! empty( $c['is_completed'] );
		} ) );
		return array_merge(
			self::child_profile( $student_id ),
			array(
				'courses'          => $courses,
				'enrolled_count'   => count( $courses ),
				'completed_count'  => $completed,
				'certificate_count' => count( array_filter( $courses, static function ( $c ) {
					return ! empty( $c['has_certificate'] );
				} ) ),
			)
		);
	}
}
