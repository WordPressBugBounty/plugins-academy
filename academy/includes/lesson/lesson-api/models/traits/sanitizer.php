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
		// Null while saving (the current user's capabilities decide); the
		// lesson author's ID while loading a stored row.
		$author_id = property_exists( $this, 'stored_content_author' ) ? $this->stored_content_author : null;
		return apply_filters( 'academy/allowed_learnpage_content_tags', $content, $author_id );
	}
	public function sanitize_post_content( $content ) {
		return $this->sanitize_lesson_content( $content );
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
