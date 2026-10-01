<?php

namespace AcademyStoreEngine\hooks;

use Academy\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bridges StoreEngine Membership access to Academy course enrollment.
 *
 * A StoreEngine membership only gates whether a visitor can SEE the course
 * page; Academy still gates the lessons, progress tracking and completion on
 * its own `academy_enrolled` records (see Helper::is_enrolled). Without this
 * bridge a paying member could open the course page but not actually take the
 * course (no "continue learning", and marking a lesson complete dies with
 * "You must be enrolled in this course").
 *
 * When a user joins a membership group we enroll them in every Academy course
 * the group grants — free or paid alike, since the membership is the access
 * grant — and when they leave (removed or expired) we cancel those enrollments.
 */
class Membership {

	const COURSE_MEMBERSHIP_META = 'academy_store_membership';

	public static function init() {
		$self = new self();
		add_action( 'storeengine/membership/user_added_to_group', [ $self, 'enroll_group_courses' ], 10, 2 );
		add_action( 'storeengine/membership/user_removed_from_group', [ $self, 'cancel_group_courses' ], 10, 2 );
	}

	/**
	 * Enroll a user in every course the membership group grants.
	 *
	 * @param int $user_id
	 * @param int $group_id
	 */
	public function enroll_group_courses( $user_id, $group_id ) {
		$user_id = (int) $user_id;
		if ( ! $user_id ) {
			return;
		}

		foreach ( $this->get_group_course_ids( (int) $group_id ) as $course_id ) {
			// do_enroll() is a no-op when the user is already actively enrolled.
			Helper::do_enroll( $course_id, $user_id );
		}
	}

	/**
	 * Cancel the enrollments the membership granted when the user leaves it.
	 *
	 * Enrollment is cancelled (not deleted) so the student's progress survives
	 * if they rejoin — mirroring how Academy handles a cancelled enrollment.
	 *
	 * @param int $user_id
	 * @param int $group_id
	 */
	public function cancel_group_courses( $user_id, $group_id ) {
		$user_id = (int) $user_id;
		if ( ! $user_id ) {
			return;
		}

		foreach ( $this->get_group_course_ids( (int) $group_id ) as $course_id ) {
			$enrolled = Helper::is_enrolled( $course_id, $user_id, 'any' );
			if ( ! empty( $enrolled ) && 'cancel' !== $enrolled->enrolled_status ) {
				Helper::update_enrollment_status( $course_id, $enrolled->ID, $user_id, 'cancel' );
			}
		}
	}

	/**
	 * Academy course ids whose access is granted by a membership group.
	 *
	 * A course stores its linked group in the `academy_store_membership` meta
	 * (see Ajax\Membership::save_membership), so the grant is a reverse lookup.
	 *
	 * @param int $group_id
	 *
	 * @return int[]
	 */
	protected function get_group_course_ids( int $group_id ): array {
		if ( ! $group_id ) {
			return [];
		}

		$courses = get_posts( [
			'post_type'      => 'academy_courses',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_key'       => self::COURSE_MEMBERSHIP_META,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'meta_value'     => $group_id,
		] );

		return is_array( $courses ) ? array_map( 'intval', $courses ) : [];
	}
}
