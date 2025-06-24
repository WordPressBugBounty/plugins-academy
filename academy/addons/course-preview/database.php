<?php
namespace AcademyCoursePreview;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class Database {

	public static function init() {
		$self = new self();
		add_filter( 'academy/api/lesson/public_item_schema', [ $self, 'course_preview_public_item_schema' ] );
		add_filter( 'academy/api/lesson/item_schema', [ $self, 'course_preview_item_schema' ] );
		add_filter( 'academy/api/lesson/rest_pre_insert_lesson_meta', [ $self, 'course_preview_rest_pre_insert_lesson_meta' ], 10, 3 );
		add_filter( 'academy/api/lesson/rest_prepare_meta_item', [ $self, 'course_preview_rest_prepare_meta_item' ], 10, 4 );
	}


	public function course_preview_public_item_schema( $schema ) {
		if ( ! isset( $schema['properties']['meta']['properties'] ) ) {
			return $schema;
		}
		$meta = $schema['properties']['meta']['properties'];
		$meta['is_previewable'] = [
			'type'          => 'boolean',
		];
		$schema['properties']['meta']['properties'] = $meta;
		return $schema;
	}

	public function course_preview_item_schema( $schema ) {
		if ( ! isset( $schema['meta']['properties'] ) ) {
			return $schema;
		}
		$meta = $schema['meta'];
		$meta['properties']['is_previewable'] = [
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
		];
		$schema['meta'] = $meta;
		return $schema;
	}

	public function course_preview_rest_pre_insert_lesson_meta( $lesson_meta, $request, $schema ) {
		if ( ! empty( $schema['meta']['properties']['is_previewable'] ) && isset( $request['meta']['is_previewable'] ) ) {
			$lesson_meta->is_previewable = (bool) $request['meta']['is_previewable'];
		}

		return $lesson_meta;
	}

	public function course_preview_rest_prepare_meta_item( $data, $lesson_meta, $request, $schema ) {
		if ( isset( $schema['meta']['properties']['is_previewable'] ) && isset( $lesson_meta['is_previewable'] ) ) {
			$data['is_previewable'] = (bool) $lesson_meta['is_previewable'];
		}
		return $data;
	}
}
