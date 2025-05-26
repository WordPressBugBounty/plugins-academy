<?php
namespace Academy\Lesson\LessonApi\Models\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
trait Sanitizer {
	public function sanitize_id( $id ) {
		return absint( $id );
	}
	public function sanitize_lesson_content( $content ) {
		return wp_kses_post( $content );
	}
	public function sanitize_post_content( $content ) {
		return wp_kses_post( $content );
	}

	public function sanitize_lesson_author( $id ) {
		return absint( $id );
	}

	public function sanitize_comment_count( $id ) {
		return absint( $id );
	}

	public function sanitize_video_duration( $json ) {
		if ( is_string( $json ) ) {
			return json_decode( $json, true );
		}
		return $json;
	}

	public function sanitize_video_source( $json ) {
		if ( is_string( $json ) ) {
			return json_decode( $json, true );
		}
		return $json;
	}

	public function sanitize_drip_content( $json ) {
		if ( is_string( $json ) ) {
			return json_decode( $json, true );
		}
		return $json;
	}
}
