<?php
namespace Academy\Guardian;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Helper;
use WP_Error;

/**
 * Family REST API — academy/v1/family/*.
 *
 * Guardians read their wards' learning and add/assign courses; managers
 * (manage_academy_guardian) can administer any link.
 */
class RestApi {

	private $namespace = ACADEMY_PLUGIN_SLUG . '/v1';

	public static function init() {
		$self = new self();
		add_action( 'rest_api_init', array( $self, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route( $this->namespace, '/family/children', array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_children' ),
				'permission_callback' => array( $this, 'logged_in' ),
			),
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_child' ),
				'permission_callback' => array( $this, 'can_manage_own_family' ),
			),
		) );

		register_rest_route( $this->namespace, '/family/children/(?P<id>\d+)', array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_child' ),
				'permission_callback' => array( $this, 'logged_in' ),
			),
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'unlink_child' ),
				'permission_callback' => array( $this, 'can_manage_own_family' ),
			),
		) );

		register_rest_route( $this->namespace, '/family/assign', array(
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'assign_course' ),
				'permission_callback' => array( $this, 'logged_in' ),
			),
		) );

		// Manager-only: guardians of a given learner.
		register_rest_route( $this->namespace, '/family/guardians', array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_guardians' ),
				'permission_callback' => array( $this, 'is_manager' ),
			),
		) );
	}

	// ── Permissions ──

	/**
	 * Permission callback: any logged-in user.
	 */
	public function logged_in() {
		return is_user_logged_in();
	}

	public function is_manager() {
		return is_user_logged_in() && current_user_can( 'manage_options' );
	}

	/** A guardian acting on their own family, or a manager. */
	public function can_manage_own_family() {
		return is_user_logged_in() && (
			current_user_can( 'manage_academy_guardian' ) ||
			in_array( 'academy_guardian', (array) wp_get_current_user()->roles, true )
		);
	}

	/**
	 * The guardian these actions apply to: an explicit guardian_id (managers
	 * only) or the current user.
	 *
	 * @param \WP_REST_Request $request
	 */
	private function target_guardian_id( $request ) {
		$explicit = (int) $request->get_param( 'guardian_id' );
		if ( $explicit && current_user_can( 'manage_options' ) ) {
			return $explicit;
		}
		return get_current_user_id();
	}

	// ── Handlers ──

	/**
	 * The guardian's linked children, each with a progress snapshot.
	 *
	 * @param \WP_REST_Request $request
	 */
	public function get_children( $request ) {
		$guardian_id = $this->target_guardian_id( $request );
		$children    = array();
		foreach ( Store::get_children( $guardian_id ) as $student_id ) {
			$children[] = Store::child_snapshot( $student_id );
		}
		return rest_ensure_response( $children );
	}

	public function get_child( $request ) {
		$student_id = (int) $request['id'];
		if ( ! Store::can_view( $student_id ) ) {
			return new WP_Error( 'forbidden', __( 'You cannot view this learner.', 'academy' ), array( 'status' => 403 ) );
		}
		return rest_ensure_response( Store::child_snapshot( $student_id ) );
	}

	/**
	 * Add a child: link an existing learner by email, or create a new learner
	 * account and link it to the acting guardian.
	 *
	 * @param \WP_REST_Request $request
	 */
	public function add_child( $request ) {
		$guardian_id = $this->target_guardian_id( $request );
		$email       = sanitize_email( (string) $request->get_param( 'email' ) );
		$name        = sanitize_text_field( (string) $request->get_param( 'name' ) );

		if ( ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', __( 'A valid learner email is required.', 'academy' ), array( 'status' => 400 ) );
		}

		$student_id = email_exists( $email );
		if ( ! $student_id ) {
			$student_id = wp_insert_user( array(
				'user_login'   => $email,
				'user_email'   => $email,
				'display_name' => $name ? $name : $email,
				'user_pass'    => wp_generate_password( 16 ),
				'role'         => 'academy_student',
			) );
			if ( is_wp_error( $student_id ) ) {
				return $student_id;
			}
		} else {
			$student = get_user_by( 'id', $student_id );
			if ( $student && ! in_array( 'academy_student', (array) $student->roles, true ) ) {
				$student->add_role( 'academy_student' );
			}
		}

		$linked = Store::link( $guardian_id, (int) $student_id, array(
			'relationship' => (string) $request->get_param( 'relationship' ),
			'is_primary'   => (bool) $request->get_param( 'is_primary' ),
		) );
		if ( is_wp_error( $linked ) ) {
			return $linked;
		}

		return rest_ensure_response( Store::child_snapshot( (int) $student_id ) );
	}

	public function unlink_child( $request ) {
		$student_id  = (int) $request['id'];
		$guardian_id = $this->target_guardian_id( $request );
		// A guardian may only unlink their own ward (unless manager).
		if ( ! current_user_can( 'manage_options' ) && ! in_array( get_current_user_id(), Store::get_guardians( $student_id ), true ) ) {
			return new WP_Error( 'forbidden', __( 'You cannot unlink this learner.', 'academy' ), array( 'status' => 403 ) );
		}
		Store::unlink( $guardian_id, $student_id );
		return rest_ensure_response( array(
			'unlinked' => true,
			'student_id' => $student_id
		) );
	}

	/**
	 * Enroll a linked child into a course. Free courses (or managers) enroll
	 * directly; paid courses require the Pro gifting/purchase flow.
	 *
	 * @param \WP_REST_Request $request
	 */
	public function assign_course( $request ) {
		$student_id = (int) $request->get_param( 'student_id' );
		$course_id  = (int) $request->get_param( 'course_id' );

		if ( ! $course_id || 'academy_courses' !== get_post_type( $course_id ) ) {
			return new WP_Error( 'invalid_course', __( 'A valid course is required.', 'academy' ), array( 'status' => 400 ) );
		}
		if ( ! Store::can_view( $student_id ) ) {
			return new WP_Error( 'forbidden', __( 'You are not a guardian of this learner.', 'academy' ), array( 'status' => 403 ) );
		}

		$is_manager = current_user_can( 'manage_options' );
		$is_paid    = $this->course_is_paid( $course_id );

		/**
		 * Lets Academy Pro (gifting) take over paid assignments — return true to
		 * signal it handled the purchase/enrollment.
		 */
		$handled = apply_filters( 'academy/guardian/assign_course', false, $course_id, $student_id, get_current_user_id() );
		if ( true === $handled ) {
			// Pro handled it — but a paid gift may be recorded pending payment, so
			// report the true enrollment state rather than a blanket success.
			$enrolled = (bool) Helper::is_enrolled( $course_id, $student_id );
			return rest_ensure_response( array(
				'assigned'   => $enrolled,
				'pending'    => ! $enrolled,
				'course_id'  => $course_id,
				'student_id' => $student_id,
				'via'        => 'pro',
				'message'    => $enrolled ? '' : __( 'Gift recorded — the course unlocks once payment is completed.', 'academy' ),
			) );
		}

		if ( $is_paid && ! $is_manager ) {
			return new WP_Error(
				'purchase_required',
				__( 'This is a paid course. Gifting a paid course to your child is available in Academy Pro.', 'academy' ),
				array( 'status' => 402 )
			);
		}

		if ( Helper::is_enrolled( $course_id, $student_id ) ) {
			return rest_ensure_response( array(
				'assigned' => true,
				'already' => true,
				'course_id' => $course_id,
				'student_id' => $student_id
			) );
		}

		Helper::do_enroll( $course_id, $student_id );
		do_action( 'academy/guardian/course_assigned', $course_id, $student_id, get_current_user_id() );

		return rest_ensure_response( array(
			'assigned' => true,
			'course_id' => $course_id,
			'student_id' => $student_id
		) );
	}

	public function get_guardians( $request ) {
		$student_id = (int) $request->get_param( 'student_id' );
		$out        = array();
		foreach ( Store::get_guardians( $student_id ) as $gid ) {
			$u = get_user_by( 'id', $gid );
			$out[] = array(
				'id' => $gid,
				'name' => $u ? $u->display_name : '',
				'email' => $u ? $u->user_email : ''
			);
		}
		return rest_ensure_response( $out );
	}

	/**
	 * Best-effort "is this course paid?" check across Academy's monetization
	 * options (free vs paid / price meta). Defaults to free when unknown.
	 *
	 * @param int $course_id
	 */
	private function course_is_paid( $course_id ) {
		$type = get_post_meta( $course_id, 'academy_course_type', true ); // Either free or paid.
		if ( 'paid' === $type ) {
			return true;
		}
		$price = (float) get_post_meta( $course_id, 'academy_course_price', true );
		return $price > 0;
	}
}
