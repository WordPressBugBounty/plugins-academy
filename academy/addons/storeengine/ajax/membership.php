<?php

namespace AcademyStoreEngine\Ajax;

use Academy\Classes\AbstractAjaxHandler;
use StoreEngine\Utils\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Membership extends AbstractAjaxHandler {
	protected $namespace = ACADEMY_PLUGIN_SLUG . '_storeengine';

	public function __construct() {
		$this->actions = array(
			'get_membership'  => array(
				'callback'   => array( $this, 'get_membership' ),
				'capability' => 'manage_academy_instructor',
			),
			'save_membership' => array(
				'callback'   => array( $this, 'save_membership' ),
				'capability' => 'manage_academy_instructor',
			),
		);
	}

	public function get_membership( array $args ) {
		if ( ! $args['course_id'] ) {
			wp_send_json_error( array(
				'message' => esc_html__( 'Course ID is required.', 'academy' ),
			) );
		}

		$membership_id = get_post_meta( $args['course_id'], 'academy_store_membership', true );

		if ( ! $membership_id ) {
			wp_send_json_error( array(
				'message' => esc_html__( 'No membership found.', 'academy' ),
			) );
		}

		$group_content = maybe_unserialize( get_post_meta( $membership_id, '_storeengine_membership_content_protect_types', true ) );

		$current_rule = 'post-' . $args['course_id'] . '-|';

		if ( ! in_array( $current_rule, $group_content['specifics'] ) ) {
			wp_send_json_error( array(
				'message' => esc_html__( 'No integration rules found.', 'academy' ),
			) );
		}

		wp_send_json_success( array(
			'type' => 'membership',
			'label' => get_the_title( $membership_id ),
			'value' => $membership_id,
		) );
	}

	public function save_membership( array $args ) {
		if ( ! $args['course_id'] ) {
			wp_send_json_error( [
				'message' => esc_html__( 'Course is required.', 'academy' ),
			] );
		}

		if ( 'academy_courses' !== get_post_type( $args['course_id'] ) ) {
			wp_send_json_error( [
				'message' => esc_html__( 'Course does not exist.', 'academy' ),
			] );
		}

		if ( $args['course_id'] && empty( $args['access_group'] ) ) {
			$this->handle_remove_request( $args['course_id'] );
		}

		if ( ! $args['access_group'] ) {
			wp_send_json_error( [
				'message' => esc_html__( 'Access group is required.', 'academy' ),
			] );
		}

		if ( ! $args['course_id'] ) {
			wp_send_json_error( [
				'message' => esc_html__( 'Course is required.', 'academy' ),
			] );
		}

		$access_group = json_decode( $args['access_group'] );

		$integration = Helper::get_integration_repository_by_id( 'storeengine/membership-addon', $access_group->value );

		if ( empty( $integration ) ) {
			wp_send_json_error( [
				'message' => esc_html__( 'Access group is not integrated with any product.', 'academy' ),
			] );
		}

		$group_content = get_post_meta( $access_group->value, '_storeengine_membership_content_protect_types', true );

		if ( ! $group_content ) {
			wp_send_json_error( [
				'message' => esc_html__( 'Access group is not found.', 'academy' ),
			] );
		}

		$group_content = maybe_unserialize( $group_content );

		if ( ! in_array( 'specifics', $group_content['rules'], true ) ) {
			$group_content['rules'][] = 'specifics';
		}

		$is_already_specified = $this->handle_specifics_duplicate( $args['course_id'], $group_content['specifics'] ?? [] );

		if ( true === $is_already_specified ) {
			wp_send_json_error( [
				'message' => esc_html__( 'Access group already added to this course.', 'academy' ),
			] );
		}

		$group_content['specifics'][] = 'post-' . $args['course_id'] . '-|';

		update_post_meta( $access_group->value, '_storeengine_membership_content_protect_types', $group_content );

		update_post_meta( $args['course_id'], 'academy_store_membership', $access_group->value );

		$access_group->type = 'membership';

		wp_send_json_success( [
			'message' => esc_html__( 'Access group updated successfully.', 'academy' ),
			'data'    => $access_group,
		] );
	}

	public function handle_remove_request( $course_id ) {
		$membership_id = get_post_meta( $course_id, 'academy_store_membership', true );

		if ( ! $membership_id ) {
			wp_send_json_error( array(
				[
					'message' => esc_html__( 'No membership founds.', 'academy' ),
				]
			) );
		}

		$group_content = maybe_unserialize( get_post_meta( $membership_id, '_storeengine_membership_content_protect_types', true ) );

		$group_content['specifics'] = $this->handle_specifics_duplicate( $course_id, $group_content['specifics'] ?? [], true );

		update_post_meta( $membership_id, '_storeengine_membership_content_protect_types', $group_content );

		delete_post_meta( $course_id, 'academy_store_membership' );

		wp_send_json_success( array(
			'message' => esc_html__( 'Membership removed successfully.', 'academy' ),
		) );
	}

	public function handle_specifics_duplicate( $course_id, $specifics, $is_remove = false ) {
		$current_membership = 'post-' . $course_id . '-|';

		foreach ( $specifics as $key => $specific ) {
			if ( $specific === $current_membership ) {
				if ( $is_remove ) {
					unset( $specifics[ $key ] );
				} else {
					return true;
				}
			}
		}

		return $specifics;
	}
}
