<?php
namespace AcademyWebhooks\Listeners;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyWebhooks\Interfaces\ListenersInterface;
use AcademyWebhooks\Classes\Payload;

class CoursePublished implements ListenersInterface {
	public static function dispatch( $deliver_callback, $webhook ) {
		add_action(
			'rest_after_insert_academy_courses',
			function( $course ) use ( $deliver_callback, $webhook ) {
				call_user_func_array(
					$deliver_callback,
					array(
						$webhook,
						self::get_payload( $course )
					)
				);
			}, 10
		);
	}

	public static function get_payload( $course ) {

		$data = array_merge( Payload::get_course_data( $course->ID ), array(
			'categories'         => self::course_taxonomy( $course->ID, 'academy_courses_category' ),
			'tags'               => self::course_taxonomy( $course->ID, 'academy_courses_tag' ),
		) );

		return apply_filters( 'academy_webhooks/course_published_payload', $data );
	}

	public static function course_taxonomy( $course_id, $taxonomy ) {
		// Get categories
		$categories = wp_get_post_terms( $course_id, $taxonomy );
		if ( $categories ) {
			$taxonomy_data = [];
			foreach ( $categories as $category ) {
				$taxonomy_data[] = array(
					'id'   => $category->term_id,
					'name' => $category->name,
					'slug' => $category->slug,
				);
			}
			return $taxonomy_data;
		}

		$tags = wp_get_post_terms( $course_id, $taxonomy );
		if ( $tags ) {
			$taxonomy_data = [];
			foreach ( $tags as $tag ) {
				$taxonomy_data[] = array(
					'id'   => $tag->term_id,
					'name' => $tag->name,
					'slug' => $tag->slug,
				);
			}
			return $taxonomy_data;
		}
	}
}
