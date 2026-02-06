<?php
namespace Academy\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Throwable;
use Academy\API\Schema\LessonSchema;
use Academy\Classes\Query;
use Academy\Lesson\LessonApi\Lesson as LessonApi;
class Lessons extends \WP_REST_Controller {

	use LessonSchema;

	public static function init() {
		$self            = new self();
		$self->namespace = ACADEMY_PLUGIN_SLUG . '/v1';
		$self->rest_base = 'lessons';
		add_action( 'rest_api_init', array( $self, 'register_routes' ) );
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => $this->get_item_schema(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

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
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => esc_html__( 'Unique identifier for the object.', 'academy' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => $get_item_args,
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => $this->get_item_schema(),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'force' => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => esc_html__( 'Whether to bypass Trash and force deletion.', 'academy' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}


	public function permissions_check( $request ) {
		if ( ! current_user_can( 'manage_academy_instructor' ) ) {
			return new \WP_Error(
				'rest_forbidden_context',
				esc_html__( 'Sorry, you are not allowed to edit posts in this post type.', 'academy' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}


	/**
	 * Retrieves a collection of posts.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or \WP_Error object on failure.
	 */
	public function get_items( $request ) {
		global $wpdb;
		$author_id = current_user_can( 'manage_options' ) ? $request->get_param( 'author' ) : get_current_user_id();
		$page = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );
		$search_keyword = $request->get_param( 'search' );
		$lesson_status = $request->get_param( 'lesson_status' );
		$offset = ( $page - 1 ) * $per_page;

		$data = [];
		$lessons = LessonApi::get( $page, $per_page, $author_id, $search_keyword, $lesson_status );
		$total = count( $lessons );
		if ( $total > 0 ) {
			foreach ( $lessons as $lesson ) {
				$data[] = $this->rest_prepare_item( $lesson->get_data(), $request );
			}
		}
		rest_ensure_response( $data );
		$response = rest_ensure_response( $data );
		$response->header( 'x-wp-total', $total );
		return $response;
	}

	public function get_item( $request ) {
		$ID = (int) $request->get_param( 'id' );
		$author_id = current_user_can( 'manage_options' ) ? null : get_current_user_id();
		try {
			$lesson = LessonApi::get_by_id( $ID, false, $author_id );
			$response = $this->rest_prepare_item( $lesson->get_data(), $request );
			return rest_ensure_response( $response );
		} catch ( Throwable $e ) {
			return new \WP_Error(
				'academy_lesson_rest_error',
				$e->getMessage(),
				[ 'status' => 404 ]
			);
		}
	}

	/**
	 * Creates a single post.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return \WP_Error Response object on success, or \WP_Error object on failure.
	 */
	public function create_item( $request ) {
		$prepared_lesson = $this->prepare_item_for_database( $request );
		$lesson_meta     = (array) $this->prepare_item_meta_for_database( $request );

		try {
			$lesson = LessonApi::create(
				wp_slash( (array) $prepared_lesson ),
				(array) $lesson_meta
			);
			$lesson->save();
			$response = $this->rest_prepare_item( $lesson->get_data(), $request );
			do_action( 'academy_new_lesson_published', $response );
			return rest_ensure_response( $response );
		} catch ( Throwable $e ) {
			return new \WP_Error(
				'academy_lesson_rest_error',
				$e->getMessage(),
				[ 'status' => 422 ]
			);
		}
	}

	public function update_item( $request ) {
		$prepared_lesson = $this->prepare_item_for_database( $request );
		$lesson_meta     = (array) $this->prepare_item_meta_for_database( $request );
		$ID = (int) $request->get_param( 'id' );
		$author_id = current_user_can( 'manage_options' ) ? null : get_current_user_id();

		try {
			$lesson = LessonApi::get_by_id( $ID, false, $author_id );
			$lesson->ignore_slug_check = true;
			$lesson->set_data( (array) $prepared_lesson );
			$lesson->set_meta_data( (array) $lesson_meta );
			$response = $this->rest_prepare_item( $lesson->save()->get_data(), $request );
			do_action( 'academy_new_lesson_published', $response );
			return rest_ensure_response( $response );
		} catch ( Throwable $e ) {
			return new \WP_Error(
				'academy_lesson_rest_error',
				$e->getMessage(),
				[ 'status' => 422 ]
			);
		}
	}
	public function delete_item( $request ) {
		$ID = (int) $request->get_param( 'id' );
		$author_id = current_user_can( 'manage_options' ) ? null : get_current_user_id();

		try {
			$lesson = LessonApi::get_by_id( $ID, false, $author_id );
			$lesson->delete();
			return rest_ensure_response( true );
		} catch ( Throwable $e ) {
			return new \WP_Error(
				'academy_lesson_rest_error',
				$e->getMessage(),
				[ 'status' => 422 ]
			);
		}
	}

	protected function rest_prepare_item( $lesson, $request ) {
		$data = array();
		$schema = $this->get_public_item_schema();
		if ( isset( $schema['properties']['ID'] ) && isset( $lesson['ID'] ) ) {
			$data['ID'] = (int) $lesson['ID'];
		}
		if ( isset( $schema['properties']['lesson_author'] ) ) {
			$data['lesson_author'] = $lesson['lesson_author'];
			$data['author_name'] = get_the_author_meta( 'display_name', $lesson['lesson_author'] );
		}

		if ( isset( $schema['properties']['lesson_date'] ) ) {
			$data['lesson_date'] = $lesson['lesson_date'];
		}

		if ( isset( $schema['properties']['lesson_date_gmt'] ) ) {
			$data['lesson_date_gmt'] = $lesson['lesson_date_gmt'];
		}

		if ( isset( $schema['properties']['lesson_title'] ) ) {
			$data['lesson_title'] = stripslashes( $lesson['lesson_title'] );
		}

		if ( isset( $schema['properties']['lesson_name'] ) ) {
			$data['lesson_name'] = stripslashes( $lesson['lesson_name'] );
		}

		if ( isset( $schema['properties']['lesson_content'] ) ) {
			$data['lesson_content'] = [
				'raw' => stripslashes( $lesson['lesson_content'] ),
				'rendered' => \Academy\Helper::get_content_html( stripslashes( $lesson['lesson_content'] ) ),
			];
		}

		if ( isset( $schema['properties']['lesson_excerpt'] ) ) {
			$data['lesson_excerpt'] = $lesson['lesson_excerpt'];
		}

		if ( isset( $schema['properties']['lesson_status'] ) ) {
			$data['lesson_status'] = $lesson['lesson_status'];
		}

		if ( isset( $schema['properties']['comment_status'] ) ) {
			$data['comment_status'] = $lesson['comment_status'];
		}
		if ( isset( $schema['properties']['comment_count'] ) ) {
			$data['comment_status'] = $lesson['comment_count'];
		}

		if ( isset( $schema['properties']['lesson_modified'] ) ) {
			$data['lesson_modified'] = $lesson['lesson_modified'];
		}

		if ( isset( $schema['properties']['lesson_modified_gmt'] ) ) {
			$data['lesson_modified_gmt'] = $lesson['lesson_modified_gmt'];
		}

		if ( isset( $schema['properties']['meta'] ) ) {
			$data['meta'] = $this->rest_prepare_meta_item( $lesson['meta'], $request );

		}

		return apply_filters( 'academy/api/lesson/rest_prepare_item', (array) $data, $request, $schema );
	}

	protected function rest_prepare_meta_item( $lesson_meta, $request ) {
		$data = new \stdClass();
		$schema = $this->get_public_item_schema();
		$schema = $schema['properties'];

		if ( isset( $schema['meta']['properties']['featured_media'] ) ) {
			$data->featured_media = intval( $lesson_meta['featured_media'] ?? 0 );
		}

		if ( isset( $schema['meta']['properties']['attachment'] ) ) {
			$data->attachment = intval( $lesson_meta['attachment'] ?? 0 );
		}

		if ( isset( $schema['meta']['properties']['video_duration'] ) ) {
			$data->video_duration = $lesson_meta['video_duration'] ?? null;
		}

		if ( isset( $schema['meta']['properties']['video_source'] ) ) {
			$data->video_source = $lesson_meta['video_source'] ?? null;
		}

		return apply_filters( 'academy/api/lesson/rest_prepare_meta_item', (array) $data, $lesson_meta, $request, $schema );
	}

	protected function prepare_item_for_database( $request ) {
		$lesson  = new \stdClass();

		$schema = $this->get_item_schema();

		// ID.
		if ( ! empty( $schema['ID'] ) && isset( $request['ID'] ) ) {
			if ( is_numeric( $request['ID'] ) ) {
				$lesson->ID = (int) $request['ID'];
			}
		}

		// Author.
		if ( ! empty( $schema['lesson_author'] ) && isset( $request['lesson_author'] ) ) {
			if ( is_string( $request['lesson_author'] ) ) {
				$lesson->lesson_author = $request['lesson_author'];
			}
		}

		// Date.
		if ( ! empty( $schema['lesson_date'] ) && isset( $request['lesson_date'] ) ) {
			if ( is_string( $request['lesson_date'] ) ) {
				$lesson->lesson_date = $request['lesson_date'];
			}
		}

		// Date GMT.
		if ( ! empty( $schema['lesson_date_gmt'] ) && isset( $request['lesson_date_gmt'] ) ) {
			if ( is_string( $request['lesson_date_gmt'] ) ) {
				$lesson->lesson_date_gmt = $request['lesson_date_gmt'];
			}
		}

		// Title.
		if ( ! empty( $schema['lesson_title'] ) && isset( $request['lesson_title'] ) ) {
			if ( is_string( $request['lesson_title'] ) ) {
				$lesson->lesson_title = $request['lesson_title'];
			}
		}

		// Name
		if ( ! empty( $schema['lesson_name'] ) && isset( $request['lesson_name'] ) ) {
			if ( is_string( $request['lesson_name'] ) ) {
				$lesson->lesson_name = $request['lesson_name'];
			}
		}

		// Content.
		if ( ! empty( $schema['lesson_content'] ) && isset( $request['lesson_content'] ) ) {
			if ( is_string( $request['lesson_content'] ) ) {
				$lesson->lesson_content = $request['lesson_content'];
			}
		}

		// Status.
		if ( ! empty( $schema['lesson_status'] ) && isset( $request['lesson_status'] ) ) {
			if ( is_string( $request['lesson_status'] ) ) {
				$lesson->lesson_status = $request['lesson_status'];
			}
		}

		// Excerpt.
		if ( ! empty( $schema['lesson_excerpt'] ) && isset( $request['lesson_excerpt'] ) ) {
			if ( is_string( $request['lesson_excerpt'] ) ) {
				$lesson->lesson_excerpt = $request['lesson_excerpt'];
			}
		}

		// Comment Status.
		if ( ! empty( $schema['comment_status'] ) && isset( $request['comment_status'] ) ) {
			if ( is_string( $request['comment_status'] ) ) {
				$lesson->comment_status = $request['comment_status'];
			}
		}

		// Comment Count.
		if ( ! empty( $schema['comment_count'] ) && isset( $request['comment_count'] ) ) {
			if ( is_numeric( $request['comment_count'] ) ) {
				$lesson->comment_count = (int) $request['comment_count'];
			}
		}

		// Date Modified.
		if ( ! empty( $schema['lesson_modified'] ) && isset( $request['lesson_modified'] ) ) {
			if ( is_string( $request['lesson_modified'] ) ) {
				$lesson->lesson_modified = $request['lesson_modified'];
			}
		}

		// Date Modified GMT.
		if ( ! empty( $schema['lesson_modified_gmt'] ) && isset( $request['lesson_modified_gmt'] ) ) {
			if ( is_string( $request['lesson_modified_gmt'] ) ) {
				$lesson->lesson_modified_gmt = $request['lesson_modified_gmt'];
			}
		}

		return apply_filters( 'academy/api/lesson/rest_pre_insert_lesson', $lesson, $request, $schema );
	}

	protected function prepare_item_meta_for_database( $request ) {
		$lesson_meta  = new \stdClass();
		$schema = $this->get_item_schema();

		if ( ! empty( $schema['meta']['properties']['featured_media'] ) && isset( $request['meta']['featured_media'] ) ) {
			if ( is_numeric( $request['meta']['featured_media'] ) ) {
				$lesson_meta->featured_media = $request['meta']['featured_media'];
			}
		}

		if ( ! empty( $schema['meta']['properties']['attachment'] ) && isset( $request['meta']['attachment'] ) ) {
			if ( is_numeric( $request['meta']['attachment'] ) ) {
				$lesson_meta->attachment = $request['meta']['attachment'];
			}
		}

		if ( ! empty( $schema['meta']['properties']['video_duration'] ) && isset( $request['meta']['video_duration'] ) ) {
			if ( is_array( $request['meta']['video_duration'] ) ) {
				$lesson_meta->video_duration = $request['meta']['video_duration'];
			}
		}

		if ( ! empty( $schema['meta']['properties']['video_source'] ) && isset( $request['meta']['video_source'] ) ) {
			if ( is_array( $request['meta']['video_source'] ) ) {
				$lesson_meta->video_source = $request['meta']['video_source'];
			}
		}

		return apply_filters( 'academy/api/lesson/rest_pre_insert_lesson_meta', $lesson_meta, $request, $schema );
	}
}
