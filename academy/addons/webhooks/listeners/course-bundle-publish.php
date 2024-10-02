<?php
namespace AcademyWebhooks\Listeners;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyWebhooks\Interfaces\ListenersInterface;
use AcademyWebhooks\Classes\Payload;

class CourseBundlePublish implements ListenersInterface {
	public static function dispatch( $deliver_callback, $webhook ) {
		add_action(
			'rest_after_insert_alms_course_bundle',
			function( $course_bundle ) use ( $deliver_callback, $webhook ) {
				call_user_func_array(
					$deliver_callback,
					array(
						$webhook,
						self::get_payload( $course_bundle )
					)
				);
			}, 10
		);
	}

	public static function get_payload( $course_bundle ) {

		$product_id = get_post_meta( $course_bundle->ID, 'academy_course_bundle_product_id', true );
		$regular_price = get_post_meta( $product_id, '_regular_price', true );
		$featured_id = get_post_meta( $course_bundle->ID, '_thumbnail_id', true );
		$symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol(), ENT_HTML5, 'UTF-8' ) : '';
		$courses = get_post_meta( $course_bundle->ID, 'academy_course_bundle_courses_ids', true );
		foreach ( $courses as $course ) {
			$course_num = count( $course );
			$total_course[] = Payload::get_course_data( $course['value'] );
		}
		$data = array(
			'ID'                 => (int) $course_bundle->ID,
			'name'               => wp_specialchars_decode( $course_bundle->post_title ),
			'permalink'          => get_permalink( $course_bundle->ID ),
			'status'             => $course_bundle->post_status,
			'short_description'  => $course_bundle->post_excerpt,
			'slug'               => $course_bundle->post_name,
			'description'        => $course_bundle->post_content,
			'preview_permalink'  => get_preview_post_link( $course_bundle ),
			'menu_order'         => (int) $course_bundle->menu_order,
			'author_id'          => (int) $course_bundle->post_author,
			'author_display_name' => (string) get_the_author_meta( 'display_name', $course_bundle->post_author ),
			'author_avatar_url'  => get_avatar_url( $course_bundle->post_author ),
			'date_created'       => $course_bundle->post_date,
			'date_modified'      => $course_bundle->post_modified,
			'regular_price'      => $regular_price,
			'formatted_price'    => $symbol . number_format( $regular_price, 2 ),
			'sale_price'         => get_post_meta( $product_id, '_sale_price', true ),
			'discount_badge'     => get_post_meta( $course_bundle->ID, 'academy_course_bundle_discount_badge', true ),
			'featured_image'     => (int) $featured_id,
			'featured_image_url' => get_permalink( $featured_id ),
			'edit_post_link'     => get_edit_post_link( $course_bundle ),
			'total_course'       => (int) $course_num,
			'_course'            => $total_course
		);

		return apply_filters( 'academy_webhooks/course-bundle_publish_payload', $data );
	}
}
