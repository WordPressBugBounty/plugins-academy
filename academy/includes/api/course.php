<?php
namespace Academy\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Course extends \WP_REST_Controller {

	public static function init() {
		$self            = new self();
		add_action( 'rest_api_init', array( $self, 'register_routes' ) );
		add_filter( 'rest_prepare_academy_courses', array( $self, 'add_author_name_to_rest_response' ), 10, 3 );
		add_filter( 'rest_prepare_academy_courses_category', array( $self, 'taxonomy_decode_special_character' ), 10, 3 );
		add_filter( 'rest_prepare_academy_courses_tag', array( $self, 'taxonomy_decode_special_character' ), 10, 3 );
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		$this->namespace = ACADEMY_PLUGIN_SLUG . '/v1';
		$obj             = get_post_type_object( 'academy_courses' );
		$this->rest_base = ! empty( $obj->rest_base ) ? $obj->rest_base : $obj->name;

		$schema        = $this->get_item_schema();
		$get_item_args = array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);
		if ( isset( $schema['properties']['password'] ) ) {
			$get_item_args['password'] = array(
				'description' => esc_html__( 'The password for the post if it is password protected.', 'academy' ),
				'type'        => 'string',
			);
		}

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/topics',
			array(
				'args'   => array(
					'id' => array(
						'description' => esc_html__( 'Unique identifier for the object.', 'academy' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item_topics' ),
					'permission_callback' => '__return_true',
					'args'                => $get_item_args,
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/announcements',
			array(
				'args'   => array(
					'id' => array(
						'description' => esc_html__( 'Unique identifier for the object.', 'academy' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item_announcements' ),
					'permission_callback' => array( $this, 'get_announcements_permissions_check' ),
					'args'                => $get_item_args,
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	public function get_item_topics( $request ) {
		$course_id   = $request->get_param( 'id' );
		$curriculums = \Academy\Helper::get_course_curriculum( $course_id );
		return apply_filters( 'academy/api/course/get_item_curriculums', $curriculums, $course_id );
	}

	public function get_item_announcements( $request ) {
		global $wpdb;
		$course_id     = $request['id'];

		$announcement_ids = $wpdb->get_col($wpdb->prepare(
			"SELECT post_id 
				FROM {$wpdb->postmeta} 
					WHERE meta_key = %s 
					AND meta_value LIKE %s",
			'academy_announcements_course_ids',
			'%"value";i:' . $course_id . ';%'
		));

		if ( ! empty( $announcement_ids ) ) {
			$args = array(
				'post_type'         => 'academy_announcement',
				'post_status'       => 'publish',
				'post__in'          => $announcement_ids,
				'posts_per_page'    => -1,
			);
			$announcements = get_posts( $args );
			return $announcements;
		}
		return [];
	}

	/**
	 * Checks if a given request has access to read a post.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has read access for the item, \WP_Error object otherwise.
	 */
	public function get_announcements_permissions_check( $request ) {
		$course_id = $request->get_param( 'id' );
		$user_id   = (int) get_current_user_id();

		$is_administrator = current_user_can( 'administrator' );
		$is_instructor    = \Academy\Helper::is_instructor_of_this_course( $user_id, $course_id );
		$enrolled         = \Academy\Helper::is_enrolled( $course_id, $user_id );
		$is_public = \Academy\Helper::is_public_course( $course_id );
		if ( $is_administrator || $is_instructor || $enrolled || $is_public ) {
			return true;
		}

		return false;
	}

	public function add_author_name_to_rest_response( $item, $post, $request ) {
		$author_data = get_userdata( $item->data['author'] );
		$item->data['author_name'] = $author_data->display_name;
		return $item;
	}

	public function taxonomy_decode_special_character( $item, $post, $request ) {
		$item->data['name'] = html_entity_decode( $item->data['name'] );
		$item->data['description'] = html_entity_decode( $item->data['description'] );
		return $item;
	}
}
