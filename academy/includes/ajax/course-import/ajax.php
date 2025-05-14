<?php
namespace Academy\Ajax\CourseImport;

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
			],
		];
	}

	public function course_importer( $payload_data ) {
		$payload = Sanitizer::sanitize_payload( [
			'data' => 'array',
		], [ 'data' => json_decode( $payload_data['data'] ?? '{}', true ) ] );
		$course_id = absint( $payload_data['course_id'] ?? 0 );
		$thumbnail_id = absint( $payload_data['thumbnail_id'] ?? 0 );

		try {
			$id = ( new Importers\Course( $payload['data'] ?? [], $course_id, $thumbnail_id ) )->insert();
			wp_send_json_success( [
				'message' => __( 'Success!', 'academy-pro' ),
				'course_id' => $id,
			] );
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 422 );
		}
	}

}
