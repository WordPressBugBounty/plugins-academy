<?php
namespace Academy\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Role {

	public static function add_existing_administrator_instructor_role(){
		$admins = get_users(
			array(
				'role'   => 'administrator',
				'fields' => 'ID',
			)
		);

		if ( empty( $admins ) ) {
			return;
		}

		foreach ( $admins as $admin_id ) {
			self::add_admin_caps( (int) $admin_id );
		}
	}

	public static function administrator_role_change_handler( $user_id, $new_role, $old_roles ){

		$user_id   = (int) $user_id;
		$old_roles = (array) $old_roles;

		if ( 'administrator' === $new_role ) {
			self::add_admin_caps( $user_id );
			return;
		}

		if ( in_array( 'administrator', $old_roles, true ) ) {
			self::remove_admin_caps( $user_id );
		}
	}

	public static function add_student_role() {
		remove_role( 'academy_student' );
		add_role( 'academy_student', esc_html__( 'Academy Student', 'academy' ), array() );
		$role_permission = array(
			'read',
			'edit_posts',
			'read_academy_course'
		);
		$student = get_role( 'academy_student' );
		if ( $student ) {
			$can_upload_files = (bool) \Academy\Helper::get_settings( 'is_student_can_upload_files' );
			if ( $can_upload_files ) {
				$role_permission[] = 'upload_files';
			}
			foreach ( $role_permission as $cap ) {
				$student->add_cap( $cap );
			}
		}
	}

	public static function add_instructor_role() {
		remove_role( 'academy_instructor' );

		add_role( 'academy_instructor', esc_html__( 'Academy Instructor', 'academy' ), array() );
		
		$role_permission = self::get_instructor_caps();
		$instructor = get_role( 'academy_instructor' );
		if ( $instructor ) {
			$can_publish_course = (bool) \Academy\Helper::get_settings( 'is_instructor_can_publish_course' );
			if ( $can_publish_course ) {
				$role_permission[] = 'publish_academy_courses';
			}
			foreach ( $role_permission as $cap ) {
				$instructor->add_cap( $cap );
			}
		}
	}

	protected static function get_instructor_caps() {
		return array(
			'manage_academy_instructor',
			// course
			'edit_academy_course',
			'read_academy_course',
			'delete_academy_course',
			'read_private_academy_courses',
			'edit_academy_courses',
			// quizzes
			'edit_academy_quiz',
			'read_academy_quiz',
			'delete_academy_quiz',
			'edit_others_academy_quizzes',
			'publish_academy_quizzes',
			'read_private_academy_quizzes',
			'edit_academy_quizzes',
			// assignment
			'edit_academy_assignment',
			'read_academy_assignment',
			'delete_academy_assignment',
			'edit_others_academy_assignments',
			'publish_academy_assignments',
			'read_private_academy_assignments',
			'edit_academy_assignments',
			// tutor booking
			'edit_academy_booking',
			'read_academy_booking',
			'delete_academy_booking',
			'edit_others_academy_bookings',
			'publish_academy_bookings',
			'read_private_academy_bookings',
			'edit_academy_bookings',
			// Announcement
			'edit_academy_announcement',
			'read_academy_announcement',
			'delete_academy_announcement',
			'edit_others_academy_announcements',
			'publish_academy_announcements',
			'read_private_academy_announcements',
			'edit_academy_announcements',
			// course bundle
			'edit_academy_course_bundle',
			'read_academy_course_bundle',
			'delete_academy_course_bundle',
			'edit_others_academy_course_bundles',
			'publish_academy_course_bundles',
			'read_private_academy_course_bundles',
			'edit_academy_course_bundles',
			// webhook
			'edit_academy_webhook',
			'read_academy_webhook',
			'delete_academy_webhook',
			'edit_others_academy_webhooks',
			'publish_academy_webhooks',
			'read_private_academy_webhooks',
			'edit_academy_webhooks',
			// certificate
			'edit_academy_certificate',
			'read_academy_certificate',
			'delete_academy_certificate',
			'delete_academy_certificates',
			'edit_academy_certificates',
			'edit_others_academy_certificates',
			'publish_academy_certificates',
			'read_private_academy_certificates',
			'edit_academy_certificates',
			// lesson
			'publish_academy_lessons',
			'edit_academy_lesson',
			'read_academy_lesson',
			'delete_academy_lesson',
			'edit_academy_lessons',
			'edit_others_academy_lessons',
			// Meeting
			'edit_academy_meeting',
			'read_academy_meeting',
			'delete_academy_meeting',
			'edit_others_academy_meetings',
			'publish_academy_meetings',
			'read_private_academy_meetings',
			'edit_academy_meetings',
			// Attendance
			'edit_academy_attendance',
			'read_academy_attendance',
			'delete_academy_attendance',
			'edit_others_academy_attendances',
			'publish_academy_attendances',
			'read_private_academy_attendances',
			'edit_academy_attendances',
			// common
			'edit_post',
			'edit_post_meta',
			'assign_terms',
			'assign_term',
			'read',
			'upload_files',
			'edit_posts',
		);
	}

	protected static function get_administrator_caps() {
		return [
			'edit_posts',
			'edit_others_posts',
			'manage_academy_instructor',
			'publish_academy_courses',
			'delete_academy_courses',
			'edit_others_academy_courses',
			'delete_academy_quizzes',
			'delete_academy_zooms',
			'delete_academy_assignments',
			'delete_academy_attendances',
			'delete_academy_bookings',
			'delete_academy_announcements',
			'delete_academy_course_bundles',
			'delete_academy_webhooks',
			'delete_academy_lessons',
			'delete_academy_meetings',
		];
	}

	/**
	 * Add custom administrator capabilities.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return void
	 */
	private static function add_admin_caps( $user_id ) {

		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return;
		}

		foreach ( self::get_administrator_caps() as $cap ) {
			$user->add_cap( $cap );
		}

		\Academy\Helper::set_instructor_role( $user_id );
	}

	/**
	 * Remove custom administrator capabilities.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return void
	 */
	private static function remove_admin_caps( $user_id ) {

		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return;
		}

		foreach ( self::get_administrator_caps() as $cap ) {
			$user->remove_cap( $cap );
		}

		\Academy\Helper::remove_instructor_role( $user_id );
	}
}
