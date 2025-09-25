<?php
namespace AcademyChatgpt\CourseImport;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes;
use Academy\Classes\Sanitizer;
use Academy\Classes\AbstractAjaxHandler;
use Exception;

class Ajax extends AbstractAjaxHandler {
	protected $namespace = ACADEMY_PLUGIN_SLUG;
	public function __construct() {
		$this->actions = [
			'course_importer' => [
				'callback' => [ $this, 'course_importer' ],
				'capability' => 'read',
			],
			'quiz_importer' => [
				'callback' => [ $this, 'quiz_importer' ],
				'capability' => 'read',
			],
			'assignment_importer' => [
				'callback' => [ $this, 'assignment_importer' ],
				'capability' => 'read',
			],
		];
	}

	public function course_importer( $payload_data ) {
		$payload = Sanitizer::sanitize_payload( [
			'data' => 'array',
		], [ 'data' => json_decode( $payload_data['data'] ?? '{}', true ) ] );

		$course_id = absint( $payload_data['course_id'] ?? 0 );
		$thumbnail_id = absint( $payload_data['thumbnail_id'] ?? 0 );

		if ( ! $this->authorize( $course_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'academy' ) ], 401 );
		}

		try {
			$id = ( new Importers\Course( $payload['data'] ?? [], $course_id, $thumbnail_id ) )->insert();
			wp_send_json_success( [
				'message' => __( 'Success!', 'academy' ),
				'course_id' => $id,
			] );
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 422 );
		}
	}

	public function quiz_importer( $payload_data ) {
		$payload = Sanitizer::sanitize_payload( [
			'data' => 'array',
		], [ 'data' => json_decode( $payload_data['data'] ?? '{}', true ) ] );

		$quiz_id = absint( $payload_data['quiz_id'] ?? 0 );

		if ( ! $this->authorize( $quiz_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'academy' ) ], 401 );
		}

		try {
			$id = ( new Importers\Quiz(
				$payload['data']['title'] ?? '',
				'',
				$payload['data']['questions'] ?? [],
				$quiz_id
			) )->insert();
			wp_send_json_success( [
				'message' => __( 'Success!', 'academy' ),
				'quiz_id' => $id,
			] );
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 422 );
		}
	}

	public function assignment_importer( $payload_data ) {
		$payload = Sanitizer::sanitize_payload( [
			'data' => 'array',
		], [ 'data' => json_decode( $payload_data['data'] ?? '{}', true ) ] );

		$assignment_id = absint( $payload_data['assignment_id'] ?? 0 );

		if ( ! $this->authorize( $assignment_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'academy' ) ], 401 );
		}

		try {
			$id = ( new Importers\Assignment(
				$payload['data'] ?? [],
				$assignment_id
			) )->insert();
			wp_send_json_success( [
				'message' => __( 'Success!', 'academy' ),
				'assignment_id' => $id,
			] );
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 422 );
		}
	}

	protected function authorize( int $course_id = 0 ) : bool {
		if ( empty( $course_id ) ) {
			$post_type_obj = get_post_type_object( 'academy_courses' );
			return $post_type_obj && current_user_can( $post_type_obj->cap->create_posts );
		}
		return current_user_can( 'edit_post', $course_id );
	}
}
