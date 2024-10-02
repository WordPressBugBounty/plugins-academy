<?php
namespace Academy\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class User {

	public static function init() {
		$self = new self();
		add_action( 'edit_user_profile', array( $self, 'edit_user_profile' ) );
		add_action( 'show_user_profile', array( $self, 'edit_user_profile' ), 10, 1 );
		add_action( 'profile_update', array( $self, 'profile_update' ) );
		add_action( 'set_user_role', array( $self, 'set_user_role' ), 10, 3 );
	}
	public function edit_user_profile( $user ) {
		include ACADEMY_ROOT_DIR_PATH . 'includes/admin/views/user-profile-fields.php';
	}
	public function profile_update( $user_id ) {
		if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'update-user_' . $user_id ) ) {
			if ( ! apply_filters( 'academy/admin/current_user_can_edit_user_meta_fields', current_user_can( 'manage_options' ), $user_id ) ) {
				return;
			}
			$academy_profile_designation = ( isset( $_POST['academy_profile_designation'] ) ? sanitize_text_field( $_POST['academy_profile_designation'] ) : '' );
			$academy_profile_bio         = ( isset( $_POST['academy_profile_bio'] ) ? wp_kses_post( $_POST['academy_profile_bio'] ) : '' );
			$academy_profile_photo       = ( isset( $_POST['academy_profile_photo'] ) ? wp_kses_post( $_POST['academy_profile_photo'] ) : '' );
			update_user_meta( $user_id, 'academy_profile_designation', $academy_profile_designation );
			update_user_meta( $user_id, 'academy_profile_bio', $academy_profile_bio );
			update_user_meta( $user_id, 'academy_profile_photo', $academy_profile_photo );
		}
	}
	public function set_user_role( $user_id, $role, $old_roles ) {
		if ( 'academy_instructor' === $role || in_array( 'academy_instructor', $old_roles, true ) ) {
			\Academy\Helper::set_instructor_role( $user_id );
		}
		if ( 'academy_student' === $role || in_array( 'academy_student', $old_roles, true ) ) {
			\Academy\Helper::set_student_role( $user_id );
		}
	}
}
