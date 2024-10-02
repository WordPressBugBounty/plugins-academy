<?php
namespace AcademyWebhooks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Database {

	public static function init() {
		$self = new self();
		add_action( 'init', [ $self, 'create_academy_webhook_post_type' ] );
		add_action( 'rest_api_init', [ $self, 'register_academy_webhook_meta' ] );
	}

	public function create_academy_webhook_post_type() {
		$post_type = 'academy_webhook';

		register_post_type(
			$post_type,
			array(
				'labels'                => array(
					'name'                  => esc_html__( 'Webhooks', 'academy' ),
					'singular_name'         => esc_html__( 'Webhook', 'academy' ),
					'search_items'          => esc_html__( 'Search webhooks', 'academy' ),
					'parent_item_colon'     => esc_html__( 'Parent webhooks:', 'academy' ),
					'not_found'             => esc_html__( 'No webhooks found.', 'academy' ),
					'not_found_in_trash'    => esc_html__( 'No webhooks found in Trash.', 'academy' ),
					'archives'              => esc_html__( 'webhook archives', 'academy' ),
				),
				'public'                => true,
				'publicly_queryable'    => true,
				'show_ui'               => false,
				'show_in_menu'          => false,
				'hierarchical'          => true,
				'rewrite'               => array( 'slug' => 'webhook' ),
				'query_var'             => true,
				'has_archive'           => true,
				'delete_with_user'      => false,
				'supports'              => array( 'title', 'custom-fields' ),
				'show_in_rest'          => true,
				'rest_base'             => $post_type,
				'rest_namespace'        => ACADEMY_PLUGIN_SLUG . '/v1',
				'rest_controller_class' => 'WP_REST_Posts_Controller',
				'capability_type'           => 'post',
				'capabilities'              => array(
					'edit_post'             => 'edit_academy_webhook',
					'read_post'             => 'read_academy_webhook',
					'delete_post'           => 'delete_academy_webhook',
					'delete_posts'          => 'delete_academy_webhooks',
					'edit_posts'            => 'edit_academy_webhooks',
					'edit_others_posts'     => 'edit_others_academy_webhooks',
					'publish_posts'         => 'publish_academy_webhooks',
					'read_private_posts'    => 'read_private_academy_webhooks',
					'create_posts'          => 'edit_academy_webhooks',
				),
			)
		);
	}

	public function register_academy_webhook_meta() {
		$course_meta = [
			'_academy_webhook_delivery_url'                     => 'string',
			'_academy_webhook_secret'                           => 'string',
		];

		foreach ( $course_meta as $meta_key => $meta_value_type ) {
			register_meta(
				'post',
				$meta_key,
				array(
					'object_subtype' => 'academy_webhook',
					'type'           => $meta_value_type,
					'single'         => true,
					'show_in_rest'   => true,
				)
			);
		}
		register_meta(
			'post',
			'_academy_webhook_events',
			array(
				'object_subtype' => 'academy_webhook',
				'type'           => 'array',
				'single'         => true,
				'show_in_rest'   => [
					'schema' => array(
						'items' => array(
							'type'       => 'object',
							'properties' => [
								'value'   => array(
									'type' => 'string',
								),
								'label' => array(
									'type' => 'string',
								),
							],
						),
					),
				],
			)
		);
	}
}
