<?php
namespace Academy\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Role {

	public static function add_existing_administrator_instructor_role() {
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

	public static function administrator_role_change_handler( $user_id, $new_role, $old_roles ) {

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
		self::create_role( 'academy_student', esc_html__( 'Academy Student', 'academy' ) );
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

	/**
	 * Guardian / parent role — a family account linked to one or more learners.
	 * Read-only over their wards' learning; `manage_academy_guardian` gates the
	 * guardian dashboard + the family REST endpoints.
	 */
	public static function add_guardian_role() {
		remove_role( 'academy_guardian' );
		self::create_role( 'academy_guardian', esc_html__( 'Academy Guardian', 'academy' ) );
		$role_permission = array(
			'read',
			'read_academy_course',
			'manage_academy_guardian',
		);
		$guardian = get_role( 'academy_guardian' );
		if ( $guardian ) {
			foreach ( $role_permission as $cap ) {
				$guardian->add_cap( $cap );
			}
		}
		// Administrators manage guardians too.
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( 'manage_academy_guardian' );
		}
	}

	/**
	 * Manager role — a real, wp-admin-visible label for anyone Manage Roles
	 * has granted at least one permission to (mirrors becoming an Instructor,
	 * per the explicit product decision this shape went through several
	 * revisions to land on). Deliberately carries no capabilities beyond the
	 * `manage_academy_manager` marker: actual access is 100% governed by the
	 * dotted-slug permissions Manage Roles grants, enforced by the existing
	 * `role-permission` addon's own capability/menu/write-gating machinery
	 * (Caps/RestGuard/AjaxGuard/Menu) — unchanged by, and applying normally
	 * to, anyone holding this role. A role with its own broad capability
	 * bundle was tried first and reverted: granting only "Courses" made every
	 * area visible and readable, since the role's bundle doesn't know which
	 * specific permission was actually picked.
	 */
	public static function add_manager_role() {
		remove_role( 'academy_manager' );
		self::create_role( 'academy_manager', esc_html__( 'Academy Manager', 'academy' ) );

		$manager = get_role( 'academy_manager' );
		if ( $manager ) {
			foreach ( self::get_manager_caps() as $cap ) {
				$manager->add_cap( $cap );
			}
		}
		// Administrators are managers too.
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( 'manage_academy_manager' );
		}
	}

	public static function add_instructor_role() {
		remove_role( 'academy_instructor' );

		self::create_role( 'academy_instructor', esc_html__( 'Academy Instructor', 'academy' ) );

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
			// NOTE: 'edit_others_academy_announcements' is deliberately NOT
			// granted here. AnnouncementController::check_academy_announcement_action()
			// uses that exact capability as an "act on any announcement, not
			// just your own" bypass (for the Role & Permission pro addon's
			// native-capability bridge) — granting it to every Instructor here
			// would let any Instructor edit/delete any OTHER Instructor's
			// announcements, defeating the per-instructor ownership check this
			// controller exists to enforce. Confirmed unused elsewhere before
			// this note was added (grep across both plugins turned up only the
			// capability's own definition, this grant, and the bypass).
			'edit_academy_announcement',
			'read_academy_announcement',
			'delete_academy_announcement',
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
		return array_merge( [
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
		], self::get_instructor_caps() );
	}

	/**
	 * Manager caps = every cross-user capability an administrator has for
	 * Academy's resources (via get_administrator_caps(), which already merges
	 * get_instructor_caps() with the edit_others_ and delete_academy_ (plural)
	 * caps), plus the manager-specific gating capability. Deliberately
	 * excludes real WordPress admin-only capabilities (manage_options and
	 * friends) — a manager is never a real administrator.
	 */
	protected static function get_manager_caps() {
		return array( 'manage_academy_manager' );
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

	/**
	 * Create an (empty) role. On WordPress VIP roles must go through
	 * wpcom_vip_add_role(), which also keeps them in sync across the network.
	 *
	 * @param string $role         Role slug.
	 * @param string $display_name Role label.
	 */
	private static function create_role( $role, $display_name ) {
		if ( function_exists( 'wpcom_vip_add_role' ) ) {
			wpcom_vip_add_role( $role, $display_name, array() );
			return;
		}
		add_role( $role, $display_name, array() ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.custom_role_add_role -- non-VIP fallback.
	}
}
