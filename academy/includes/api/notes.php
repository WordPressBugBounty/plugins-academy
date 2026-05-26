<?php
namespace Academy\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notes extends \WP_REST_Controller {

	public static function init() {
		$self            = new self();
		$self->namespace = ACADEMY_PLUGIN_SLUG . '/v1';
		$self->rest_base = 'notes';
		add_action( 'rest_api_init', array( $self, 'register_routes' ) );
	}

	public function register_routes() {
		// GET /academy/v1/notes/user — all non-empty notes for the current user
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/user',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_user_notes' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'course_id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'description'       => esc_html__( 'Course ID to retrieve the note for.', 'academy' ),
						),
						'lesson_id' => array(
							'required'          => false,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'description'       => esc_html__( 'Lesson ID to retrieve the note for (per-lesson scope).', 'academy' ),
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'course_id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'description'       => esc_html__( 'Course ID to save the note for.', 'academy' ),
						),
						'lesson_id' => array(
							'required'          => false,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'description'       => esc_html__( 'Lesson ID to save the note for (per-lesson scope).', 'academy' ),
						),
						'note' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'wp_kses_post',
							'description'       => esc_html__( 'Note content.', 'academy' ),
						),
					),
				),
			)
		);
	}

	private function build_meta_key( $course_id, $lesson_id, $user_id ) {
		if ( $lesson_id ) {
			return "academy_{$course_id}_lesson_{$lesson_id}_note_{$user_id}";
		}
		return "academy_{$course_id}lesson_note_{$user_id}";
	}

	public function permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				esc_html__( 'You must be logged in to access notes.', 'academy' ),
				array( 'status' => 401 )
			);
		}
		return true;
	}

	public function get_user_notes( $request ) {
		global $wpdb;

		$user_id = get_current_user_id();

		// Match both per-course (academy_NNNlesson_note_UID) and per-lesson (academy_NNN_lesson_NNN_note_UID)
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_key, meta_value FROM {$wpdb->usermeta}
				 WHERE user_id = %d
				   AND meta_key LIKE %s
				   AND meta_value <> ''",
				$user_id,
				$wpdb->esc_like( 'academy_' ) . '%' . $wpdb->esc_like( 'note_' . $user_id )
			)
		);

		$notes = array();

		foreach ( $rows as $row ) {
			$course_id = null;
			$lesson_id = null;

			// Per-lesson: academy_{course_id}_lesson_{lesson_id}_note_{user_id}
			if ( preg_match( '/^academy_(\d+)_lesson_(\d+)_note_\d+$/', $row->meta_key, $m ) ) {
				$course_id = (int) $m[1];
				$lesson_id = (int) $m[2];
			} elseif ( preg_match( '/^academy_(\d+)lesson_note_\d+$/', $row->meta_key, $m ) ) {
				// Per-course: academy_{course_id}lesson_note_{user_id}
				$course_id = (int) $m[1];
			} else {
				continue;
			}

			$notes[] = array(
				'course_id'    => $course_id,
				'course_title' => get_the_title( $course_id ) ?: '',
				'lesson_id'    => $lesson_id,
				'lesson_title' => $lesson_id ? ( get_the_title( $lesson_id ) ?: '' ) : '',
				'note'         => $row->meta_value,
			);
		}

		return rest_ensure_response( $notes );
	}

	public function get_item( $request ) {
		$user_id   = get_current_user_id();
		$course_id = (int) $request->get_param( 'course_id' );
		$lesson_id = (int) $request->get_param( 'lesson_id' );

		if ( ! $course_id || 'academy_courses' !== get_post_type( $course_id ) ) {
			return new \WP_Error(
				'rest_invalid_param',
				esc_html__( 'Invalid course ID.', 'academy' ),
				array( 'status' => 400 )
			);
		}

		if ( ! $this->user_can_access_course( $course_id, $user_id ) ) {
			return new \WP_Error(
				'rest_forbidden',
				esc_html__( 'You do not have access to this course.', 'academy' ),
				array( 'status' => 403 )
			);
		}

		$meta_key = $this->build_meta_key( $course_id, $lesson_id, $user_id );
		$note     = get_user_meta( $user_id, $meta_key, true );

		return rest_ensure_response( array(
			'course_id' => $course_id,
			'lesson_id' => $lesson_id ?: null,
			'note'      => $note ?: '',
		) );
	}

	public function save_item( $request ) {
		$user_id   = get_current_user_id();
		$course_id = (int) $request->get_param( 'course_id' );
		$lesson_id = (int) $request->get_param( 'lesson_id' );
		$note      = $request->get_param( 'note' ) ?? '';

		if ( ! $course_id || 'academy_courses' !== get_post_type( $course_id ) ) {
			return new \WP_Error(
				'rest_invalid_param',
				esc_html__( 'Invalid course ID.', 'academy' ),
				array( 'status' => 400 )
			);
		}

		if ( ! $this->user_can_access_course( $course_id, $user_id ) ) {
			return new \WP_Error(
				'rest_forbidden',
				esc_html__( 'You do not have access to this course.', 'academy' ),
				array( 'status' => 403 )
			);
		}

		$meta_key = $this->build_meta_key( $course_id, $lesson_id, $user_id );
		update_user_meta( $user_id, $meta_key, $note );

		return rest_ensure_response( array(
			'course_id' => $course_id,
			'lesson_id' => $lesson_id ?: null,
			'note'      => $note,
			'message'   => esc_html__( 'Note saved successfully.', 'academy' ),
		) );
	}

	private function user_can_access_course( int $course_id, int $user_id ): bool {
		return current_user_can( 'administrator' )
			|| \Academy\Helper::is_instructor_of_this_course( $user_id, $course_id )
			|| \Academy\Helper::is_enrolled( $course_id, $user_id )
			|| \Academy\Helper::is_public_course( $course_id );
	}
}
