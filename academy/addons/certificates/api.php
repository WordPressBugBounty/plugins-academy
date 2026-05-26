<?php
namespace AcademyCertificates;

use Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class API extends \WP_REST_Controller {

	public static function init() {
		$self            = new self();
		$self->namespace = ACADEMY_PLUGIN_SLUG . '/v1';
		$self->rest_base = 'student/certificates';

		add_filter( 'rest_prepare_academy_certificate', array( $self, 'add_author_name_to_rest_response' ), 10, 3 );
		add_filter( 'rest_prepare_academy_certificate', array( $self, 'decode_title_special_characters' ), 10, 3 );
		add_action( 'rest_api_init', array( $self, 'register_routes' ) );
	}

	public function register_routes() {
		// GET /academy/v1/student/certificates — list all earned certificates for the current student
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_student_certificates' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);

		// GET /academy/v1/student/certificates/{course_id} — single earned certificate
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<course_id>[\d]+)',
			array(
				'args' => array(
					'course_id' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_student_certificate' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);
	}

	public function check_permissions( $request ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				esc_html__( 'You must be logged in to access certificates.', 'academy' ),
				array( 'status' => 401 )
			);
		}
		return true;
	}

	public function get_student_certificates( $request ) {
		$user_id      = get_current_user_id();
		$course_ids   = \Academy\Helper::get_completed_courses_ids_by_user( $user_id );
		$certificates = array();

		foreach ( $course_ids as $course_id ) {
			$cert = $this->build_certificate_data( (int) $course_id, $user_id );
			if ( $cert ) {
				$certificates[] = $cert;
			}
		}

		return rest_ensure_response( $certificates );
	}

	public function get_student_certificate( $request ) {
		$user_id   = get_current_user_id();
		$course_id = (int) $request->get_param( 'course_id' );

		if ( $course_id <= 0 ) {
			return new \WP_Error(
				'rest_bad_request',
				esc_html__( 'Invalid course ID.', 'academy' ),
				array( 'status' => 400 )
			);
		}

		// Fetch the full completion object once and reuse it in build_certificate_data.
		$completion = \Academy\Helper::is_completed_course( $course_id, $user_id, true );

		if ( ! $completion ) {
			return new \WP_Error(
				'rest_not_found',
				esc_html__( 'Certificate not found or course not completed.', 'academy' ),
				array( 'status' => 404 )
			);
		}

		$cert = $this->build_certificate_data( $course_id, $user_id, $completion );

		if ( ! $cert ) {
			return new \WP_Error(
				'rest_not_found',
				esc_html__( 'This course does not have a certificate enabled.', 'academy' ),
				array( 'status' => 404 )
			);
		}

		return rest_ensure_response( $cert );
	}

	/**
	 * Build the certificate data array for a given course and student.
	 *
	 * @param int        $course_id
	 * @param int        $user_id
	 * @param mixed|null $completion Pre-fetched completion object; fetched internally when null.
	 * @return array|null Null when the course has no certificate enabled.
	 */
	private function build_certificate_data( int $course_id, int $user_id, $completion = null ) {
		$certificate_id     = get_post_meta( $course_id, 'academy_course_certificate_id', true )
			?: \Academy\Helper::get_settings( 'academy_primary_certificate_id' );
		$enable_certificate = (bool) get_post_meta( $course_id, 'academy_course_enable_certificate', true );

		if ( ! $certificate_id || ! $enable_certificate ) {
			return null;
		}

		if ( $completion === null ) {
			$completion = \Academy\Helper::is_completed_course( $course_id, $user_id, true );
		}

		$completion_date = ( $completion && ! empty( $completion->completion_date ) )
			? $completion->completion_date
			: null;

		$data = array(
			'course_id'       => $course_id,
			'course_title'    => get_the_title( $course_id ),
			'thumbnail'       => \Academy\Helper::get_the_course_thumbnail_url_by_id( $course_id ),
			'completion_date' => $completion_date,
			'download_url'    => add_query_arg( array( 'source' => 'certificate' ), get_permalink( $course_id ) ),
			'verify_url'      => null,
		);

		// Attach verification hash if Academy Pro certificates addon is available.
		if ( class_exists( '\AcademyProCertificates\Helper' ) ) {
			$hash = \AcademyProCertificates\Helper::get_certificate_verification_hash_by_course_and_student_id( $user_id, $course_id );
			if ( $hash ) {
				$data['verify_url'] = add_query_arg(
					array( 'source' => 'certificate', 'verify' => $hash ),
					get_permalink( $course_id )
				);
			}
		}

		return $data;
	}

	public function add_author_name_to_rest_response( $item, $post, $request ) {
		$author_data = get_userdata( $item->data['author'] );
		$item->data['author_name'] = $author_data ? $author_data->display_name : '';
		return $item;
	}

	public function decode_title_special_characters( $item, $post, $request ) {
		$item->data['title']['rendered'] = html_entity_decode( $item->data['title']['rendered'] );
		return $item;
	}
}
