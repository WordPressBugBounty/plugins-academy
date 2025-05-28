<?php
namespace Academy\Lesson\LessonApi\Models\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Academy\Classes\ColorConverter;
trait Sanitizer {
	public function sanitize_id( $id ) {
		return absint( $id );
	}
	public function sanitize_lesson_content( $content ) {
		$content = ColorConverter::rgb_to_hex( $content );
		$allowed_tags = wp_kses_allowed_html( 'post' );
		$allowed_tags['input'] = array(
			'type'              => true,
			'name'              => true,
			'value'             => true,
			'class'             => true,
			'style'             => true,
		);
		$allowed_tags['form'] = array(
			'action'            => true,
			'method'            => true,
			'class'             => true,
			'style'             => true,
		);
		$allowed_tags['iframe'] = array(
			'src'             => true,
			'width'           => true,
			'height'          => true,
			'frameborder'     => true,
			'allow'           => true,
			'allowfullscreen' => true,
			'style'             => true,
		);

		add_filter( 'safe_style_css', function( $styles ) {
			$styles[] = 'display';
			$styles[] = 'align-items';
			$styles[] = 'justify-content';
			return $styles;
		});

		$sanitize_content = wp_kses( $content, $allowed_tags );

		remove_all_filters( 'safe_style_css' );
		return $sanitize_content;
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
