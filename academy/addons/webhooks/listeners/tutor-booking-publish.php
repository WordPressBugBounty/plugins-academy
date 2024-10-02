<?php
namespace AcademyWebhooks\Listeners;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyWebhooks\Classes\Payload;
use AcademyWebhooks\Interfaces\ListenersInterface;


class TutorBookingPublish implements ListenersInterface {
	public static function dispatch( $deliver_callback, $webhook ) {
		add_action(
			'rest_after_insert_academy_booking',
			function( $booking ) use ( $deliver_callback, $webhook ) {
				call_user_func_array(
					$deliver_callback,
					array(
						$webhook,
						self::get_payload( $booking )
					)
				);
			}, 10
		);

	}

	public static function get_payload( $booking ) {

		$data = array_merge( Payload::get_tutor_booking_data( $booking ), array(
			'categories'         => self::course_taxonomy( $booking->ID, 'academy_booking_category' ),
			'tags'               => self::course_taxonomy( $booking->ID, 'academy_booking_tag' ),
		) );

		return apply_filters( 'academy_webhooks/booking_publish_payload', $data );
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
		// Get tags
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
