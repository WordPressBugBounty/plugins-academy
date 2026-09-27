<?php
namespace Academy\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes\Query;
use Academy\Helper;

/**
 * Read-only endpoint backing the Course Details modal's Attachment Downloads
 * sections — grouped per student/file download counts for one course's
 * Lessons (and, when academy-pro's assignments addon is active, Assignments
 * — see the `academy.attachment-downloads.assignment-tab` filter on the
 * frontend for that half), scoped via `course_id`.
 */
class AttachmentDownloads extends \WP_REST_Controller {

	public function __construct() {
		$this->namespace = ACADEMY_PLUGIN_SLUG . '/v1';
		$this->rest_base = 'attachment_downloads';
	}

	public static function init() {
		$self = new self();
		add_action( 'rest_api_init', array( $self, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'type'      => array(
							'type'     => 'string',
							'enum'     => array( 'lesson', 'assignment' ),
							'required' => true,
						),
						'course_id' => array(
							'type'     => 'integer',
							'required' => true,
						),
					),
				),
			)
		);
	}

	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_academy_instructor' ) ) {
			return new \WP_Error(
				'rest_forbidden_context',
				esc_html__( 'Sorry, you are not allowed to view attachment downloads.', 'academy' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	public function get_items( $request ) {
		$type      = $request->get_param( 'type' );
		$course_id = (int) $request->get_param( 'course_id' );

		$object_ids = wp_list_pluck(
			array_filter(
				Helper::get_course_curriculum_array( $course_id ),
				function ( $topic ) use ( $type ) {
					return isset( $topic['type'], $topic['id'] ) && $type === $topic['type'];
				}
			),
			'id'
		);

		if ( empty( $object_ids ) ) {
			return rest_ensure_response( array() );
		}

		$rows = Query::get_attachment_downloads( $type, array_map( 'intval', $object_ids ) );

		$data = array_map(
			function ( $row ) {
				return array(
					'user_id'             => (int) $row->user_id,
					'user_name'           => $row->user_name,
					'object_id'           => (int) $row->object_id,
					'object_title'        => $row->object_title,
					'attachment_id'       => (int) $row->attachment_id,
					'attachment_title'    => $row->attachment_title,
					'download_count'      => (int) $row->download_count,
					'last_downloaded_at'  => $row->last_downloaded_at,
				);
			},
			$rows
		);

		return rest_ensure_response( $data );
	}
}
