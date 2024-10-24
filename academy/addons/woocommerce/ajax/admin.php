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
			'postId'  => 'integer',
			'keyword' => 'string',
		], $payload_data );

		$postId  = isset( $payload['postId'] ) ? $payload['postId'] : 0;
		$keyword = isset( $payload['keyword'] ) ? $payload['keyword'] : '';

		if ( $postId ) {
			$args = array(
				'post_type' => $post_type,
				'p'         => $postId,
			);
		} else {
			$args = array(
				'post_type'      => $post_type,
				'posts_per_page' => 10,
			);
			if ( ! empty( $keyword ) ) {
				$args['s'] = $keyword;
			}

			// fetch all paid course product id
			$paid_course_product_ids = $wpdb->get_results( $wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->postmeta} postmeta  WHERE postmeta.meta_key = 'academy_course_product_id' AND postmeta.meta_value != %d", 0
			), ARRAY_A );
			$paid_course_product_ids = wp_list_pluck( $paid_course_product_ids, 'meta_value', 'meta_value' );
		}

		$results = array();
		$posts   = get_posts( $args );

		if ( is_array( $posts ) ) {
			foreach ( $posts as $post ) {
				if ( $postId !== (int) $post->ID && isset( $paid_course_product_ids[ $post->ID ] ) ) {
					continue;
				}
				$results[] = array(
					'label' => $post->post_title,
					'value' => $post->ID,
				);
			}
		}

		wp_send_json_success( $results );
	}
}
