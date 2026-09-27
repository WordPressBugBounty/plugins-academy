<?php
namespace Academy\Ajax;

use Academy\Classes\AbstractAjaxHandler;
use Academy\Classes\Query;
use Academy\Classes\Sanitizer;
use Academy\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logs a Lesson/Assignment attachment download, then redirects to the real
 * file. Registered as a plain AJAX action so the link itself (a normal
 * `<a href>` navigation, not an XHR call) can be pointed straight at
 * `admin-ajax.php?action=academy/download_attachment&...` — the base class's
 * nonce and capability checks apply the same way for a GET navigation as for
 * a POST XHR call, since both populate $_REQUEST.
 */
class AttachmentDownload extends AbstractAjaxHandler {

	protected $allowed_object_types = array( 'lesson', 'assignment' );

	public function __construct() {
		$this->actions = array(
			'download_attachment' => array(
				'callback'   => array( $this, 'download_attachment' ),
				'capability' => 'read',
			),
		);
	}

	/**
	 * The base class only forwards $_POST to the callback, but this action is
	 * reached via a plain link navigation (GET), so the actual params are
	 * read from $_GET here instead of trusting the (empty) argument.
	 */
	public function download_attachment() {
		$payload = Sanitizer::sanitize_payload(
			array(
				'type'          => 'string',
				'course_id'     => 'integer',
				'object_id'     => 'integer',
				'attachment_id' => 'integer',
			),
			wp_unslash( $_GET ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		);

		$type          = $payload['type'] ?? '';
		$course_id     = $payload['course_id'] ?? 0;
		$object_id     = $payload['object_id'] ?? 0;
		$attachment_id = $payload['attachment_id'] ?? 0;
		$user_id       = get_current_user_id();

		if ( ! in_array( $type, $this->allowed_object_types, true ) || ! $course_id || ! $object_id || ! $attachment_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid download request.', 'academy' ) ), 400 );
		}

		if ( ! Helper::has_permission_to_access_curriculum( $course_id, $user_id, $object_id, $type ) ) {
			wp_send_json_error( array( 'message' => __( 'Access Denied', 'academy' ) ), 403 );
		}

		if ( $attachment_id !== $this->get_object_attachment_id( $type, $object_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Access Denied', 'academy' ) ), 403 );
		}

		Query::attachment_download_insert( $type, $object_id, $attachment_id, $user_id );

		wp_redirect( wp_get_attachment_url( $attachment_id ) ); // phpcs:ignore WordPress.Security.SafeRedirect
		exit;
	}

	/**
	 * The attachment ID actually stored against the lesson/assignment, so a
	 * logged-in student with legitimate access to one lesson/assignment can't
	 * pass an unrelated attachment_id to probe/download arbitrary media.
	 *
	 * @param string $type
	 * @param int    $object_id
	 */
	protected function get_object_attachment_id( string $type, int $object_id ): int {
		if ( 'lesson' === $type ) {
			return (int) Helper::get_lesson_meta( $object_id, 'attachment' );
		}

		return (int) get_post_meta( $object_id, 'academy_assignment_attachment', true );
	}
}
