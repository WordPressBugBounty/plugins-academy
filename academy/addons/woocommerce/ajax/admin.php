<?php

namespace AcademyWoocommerce\Ajax;

use Academy\Classes\AbstractAjaxHandler;
use Academy\Classes\Sanitizer;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Admin extends AbstractAjaxHandler {
	protected $namespace = ACADEMY_PLUGIN_SLUG . '_woo';
	public function __construct() {
		$this->actions = array(
			'fetch_products' => array(
				'callback' => array( $this, 'fetch_products' ),
				'capability' => 'manage_academy_instructor',
			)
		);
	}

	public function fetch_products( $payload_data ) {
		global $wpdb;
		$post_type               = 'product';
		$paid_course_product_ids = [];
		$payload                 = Sanitizer::sanitize_payload( [
			'keyword' => 'string',
			'postId'  => 'integer',
		], $payload_data );

		$postId  = isset( $payload['postId'] ) ? $payload['postId'] : 0;
		$keyword = isset( $payload['keyword'] ) ? $payload['keyword'] : '';
		if ( $postId ) {
			$args = array(
				'post_type' => $post_type,
				'p'         => $postId,
				'post_status' => 'publish',
			);
		} else {
			$args = array(
				'post_type'      => $post_type,
				'posts_per_page' => 20,
			);
			if ( ! empty( $keyword ) ) {
				$args['s'] = $keyword;
			}

			// fetch all paid course product id
			$paid_course_product_ids = $wpdb->get_results( $wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta}  WHERE meta_key = %s AND meta_value = %s",
				'_academy_product', 'yes'
			), ARRAY_A );
			$paid_course_product_ids = wp_list_pluck( $paid_course_product_ids, 'post_id', 'post_id' );
		}//end if
		$results = array();
		$posts   = get_posts( $args );

		if ( is_array( $posts ) ) {
			foreach ( $posts as $post ) {
				if ( $postId === (int) $post->ID || isset( $paid_course_product_ids[ $post->ID ] ) ) {
					$results[] = array(
						'label' => $post->post_title,
						'value' => $post->ID,
					);
				}
			}
		}

		wp_send_json_success( $results );
	}

}
