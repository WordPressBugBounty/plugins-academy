<?php
namespace Academy\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GemSecurity integration.
 *
 * Academy's own login REST endpoint (`includes/api/auth.php`) calls
 * `wp_signon()` directly with no brute-force protection of its own. When
 * GemSecurity (a sibling plugin, same vendor) is active, this class closes
 * that one gap by calling `GemSecurity\Modules\LoginSecurity::is_locked_out()`
 * before Academy attempts the sign-on, reusing GemSecurity's own ban ledger.
 *
 * CAPTCHA on Academy's auth forms is deliberately **not** duplicated here —
 * Academy Pro already ships a complete, independent implementation
 * (`AcademyPro\Shortcode\LoginRegister`, hooked onto the same `before_*`
 * actions, with its own widget rendering and verification). A second
 * verifier here would either be redundant (Pro active) or actively break
 * every login (free-only: no widget is ever rendered, so a required token
 * would never arrive). Configure reCAPTCHA in Academy Pro's own settings,
 * not GemSecurity's.
 *
 * Two-factor: GemSecurity's `TwoFactor` module only challenges interactive
 * `wp-login.php` sessions on its own (`REST_REQUEST` is explicitly excluded
 * from `is_interactive_login()`), so this class runs its own REST-native
 * challenge instead of relying on that. `TwoFactor::user_needs_2fa()` is the
 * single source of truth for whether a user is challenged at all — it
 * already returns `false` whenever 2FA is disabled site-wide, and `false`
 * for a user who hasn't personally enrolled and whose role isn't in the
 * enforced-roles list, so no 2FA interface is ever presented in either case.
 * See `maybe_challenge_2fa()` / `handle_verify_2fa()` below.
 *
 * Content-abuse protection (WAF content scanning, geo-blocking on the public
 * auth routes, enrollment-fraud alerting, AJAX rate limiting) calls into
 * GemSecurity's Pro classes, checked at runtime via
 * `GemSecurity\Helper::is_pro_active()` — no academy-pro dependency, no
 * separate "pro" half of this file.
 *
 * A pure no-op when GemSecurity isn't installed or active — see `is_active()`.
 */
class GemSecurity {

	const ENROLL_WINDOW_TRANSIENT_PREFIX = 'academy_gs_enroll_';
	const AJAX_RATE_TRANSIENT_PREFIX     = 'academy_gs_ajax_';
	const TWO_FACTOR_PENDING_PREFIX      = 'academy_gs_2fa_pending_';
	const TWO_FACTOR_ATTEMPTS_PREFIX     = 'academy_gs_2fa_attempts_';

	/**
	 * Set by capture_login_request_context() during rest_pre_dispatch, read
	 * (and consumed) by maybe_challenge_2fa() when wp_login fires inside the
	 * wp_signon() call Academy's login handler makes moments later. `null`
	 * means "not currently inside an Academy REST login request."
	 *
	 * @var array{remember:bool,redirect:string}|null
	 */
	private static $academy_login_context = null;

	/**
	 * Guards against maybe_challenge_2fa() re-triggering itself when
	 * handle_verify_2fa() re-fires wp_login after a successful second factor
	 * (same pattern GemSecurity's own TwoFactor::$passed uses).
	 *
	 * @var bool
	 */
	private static $suppress_2fa_reentry = false;

	public static function init() {
		if ( ! self::is_active() ) {
			return;
		}
		$self = new self();

		/* -------------------- Auth protection -------------------- */
		add_action( 'academy/api/auth/before_login_signon', [ $self, 'on_before_login_signon' ], 10, 3 );
		add_action( 'academy/api/auth/after_student_registration', [ $self, 'on_after_student_registration' ], 10, 1 );
		add_action( 'academy/api/auth/after_instructor_registration', [ $self, 'on_after_instructor_registration' ], 10, 1 );
		add_action( 'academy/admin/update_instructor_status', [ $self, 'on_instructor_status_change' ], 10, 2 );

		/* -------------------- Two-factor challenge (Free — matches TwoFactor's own tier) -------------------- */
		if ( self::is_two_factor_available() ) {
			add_filter( 'rest_pre_dispatch', [ $self, 'capture_login_request_context' ], 5, 3 );
			add_action( 'wp_login', [ $self, 'maybe_challenge_2fa' ], 10, 2 );
			add_action( 'rest_api_init', [ $self, 'register_verify_2fa_route' ] );
		}

		/* -------------------- Content-abuse protection (GemSecurity Pro) -------------------- */
		if ( self::is_gemsecurity_pro_active() ) {
			add_action( 'academy/frontend/insert_course_qa', [ $self, 'scan_qa_submission' ], 10, 1 );
			add_action( 'academy/frontend/insert_course_qa_answered', [ $self, 'scan_qa_submission' ], 10, 1 );
			add_action( 'academy/lesson_comment/inserted', [ $self, 'scan_lesson_comment' ], 10, 3 );
			add_filter( 'rest_pre_dispatch', [ $self, 'maybe_block_by_country' ], 10, 3 );
			add_action( 'academy/course/after_enroll', [ $self, 'on_after_enroll' ], 10, 3 );
			// GemSecurity's own extension point for exactly this purpose — see
			// AbstractAjaxHandler::handle_ajax_request(): every Academy AJAX
			// action already runs through this filter after its nonce +
			// capability checks pass.
			add_filter( 'academy/request/authorize', [ $self, 'rate_limit_ajax' ], 10, 2 );
		}
	}

	/**
	 * @return bool
	 */
	private static function is_active() {
		return defined( 'GEMSECURITY_VERSION' ) && class_exists( '\\GemSecurity\\Modules\\LoginSecurity' );
	}

	/**
	 * @return bool
	 */
	private static function is_gemsecurity_pro_active() {
		return class_exists( '\\GemSecurity\\Helper' ) && \GemSecurity\Helper::is_pro_active();
	}

	/**
	 * Whether GemSecurity's TwoFactor class is loadable at all. The actual
	 * "does this site/user need a challenge" decision is left entirely to
	 * `TwoFactor::user_needs_2fa()` — this only confirms the class exists,
	 * matching how the class itself gates everything else behind its own
	 * `two_factor_enabled` setting internally.
	 *
	 * @return bool
	 */
	private static function is_two_factor_available() {
		return class_exists( '\\GemSecurity\\Modules\\TwoFactor' );
	}

	// ── Brute-force lockout on Academy's login endpoint ──

	/**
	 * @param string $username
	 * @param mixed  $unused
	 * @param string $recaptcha
	 */
	public function on_before_login_signon( $username, $unused, $recaptcha ) {
		$this->guard_lockout( is_string( $username ) ? $username : '' );
	}

	/**
	 * Block the request in progress with a ban-ledger-driven lockout
	 * message, reusing the exact ledger GemSecurity's own `wp-login.php`
	 * lockout uses (`LoginSecurity::is_locked_out()`) — one attacker, one
	 * shared ban, regardless of which endpoint they're hitting. Strike
	 * *recording* needs no call here: `wp_signon()` (which Academy's own
	 * login handler calls right after this hook) runs through
	 * `wp_authenticate()`, which unconditionally fires `wp_login_failed` on
	 * failure — GemSecurity's own `LoginSecurity::on_login_failed()` already
	 * listens to that. Only the lockout *check* was actually missing (see
	 * design.md in the GemSecurity companion change for why).
	 *
	 * @param string $username
	 */
	private function guard_lockout( $username ) {
		if ( ! \GemSecurity\Helper::get_setting( 'login_attempts_enabled', true ) ) {
			return;
		}

		$ip = \GemSecurity\Helper::get_ip_address();
		if ( ! \GemSecurity\Modules\LoginSecurity::is_locked_out( $ip ) ) {
			return;
		}

		$minutes = (int) \GemSecurity\Helper::get_setting( 'login_lockout_duration', 30 );

		\GemSecurity\Logger::log(
			'academy_login_blocked',
			sprintf( 'Blocked Academy login for "%s" from locked-out IP %s.', sanitize_user( $username ), $ip ),
			\GemSecurity\Logger::SEVERITY_WARNING,
			[
				'user_id' => 0,
				'ip_address' => $ip
			]
		);

		$this->abort_request(
			sprintf(
				/* translators: %d: lockout duration in minutes. */
				__( 'Access temporarily blocked. Too many failed login attempts. Please try again in %d minutes.', 'academy' ),
				$minutes
			),
			429
		);
	}

	/**
	 * Short-circuit the in-progress Academy REST request with a JSON body
	 * shaped like Academy's own error responses (`{success, message}`).
	 * `before_login_signon` is a plain action with no filterable return
	 * value, so this is the only way to abort before Academy calls
	 * `wp_signon()`.
	 *
	 * @param string $message
	 * @param int    $status
	 * @return never
	 */
	private function abort_request( $message, $status ) {
		wp_send_json(
			[
				'success' => false,
				'message' => $message,
			],
			$status
		);
	}

	// ── Two-factor challenge on Academy's login endpoint ──

	/**
	 * Academy's `before_login_signon` action doesn't pass `remember` or
	 * `login_redirect_url`, and the login request body is JSON (never lands
	 * in $_POST), so this is captured from the actual WP_REST_Request object
	 * — the one reliable place both values are already parsed — moments
	 * before Academy's own callback runs. Read (and consumed) once by
	 * maybe_challenge_2fa() when wp_login fires inside the wp_signon() call
	 * that follows.
	 *
	 * @param mixed            $result
	 * @param \WP_REST_Server  $server
	 * @param \WP_REST_Request $request
	 * @return mixed
	 */
	public function capture_login_request_context( $result, $server, $request ) {
		if ( null !== $result ) {
			return $result;
		}
		$route = method_exists( $request, 'get_route' ) ? $request->get_route() : '';
		if ( '/academy/v1/login' === $route ) {
			self::$academy_login_context = [
				'remember' => (bool) $request->get_param( 'remember' ),
				'redirect' => (string) $request->get_param( 'login_redirect_url' ),
			];
		}
		return $result;
	}

	/**
	 * Fires synchronously inside wp_signon() (which Academy's login handler
	 * calls right after `before_login_signon`), before wp_signon() returns
	 * control to Academy. If this user needs a second factor, tear down the
	 * session wp_signon() just started and abort the request with a
	 * `requires_2fa` response instead of letting Academy's handler send back
	 * its normal success response.
	 *
	 * `TwoFactor::user_needs_2fa()` already returns `false` — no challenge,
	 * no interface, login completes normally — whenever 2FA is disabled
	 * site-wide, or the user hasn't personally enrolled and their role isn't
	 * on the enforced-roles list. That single check is the only gate.
	 *
	 * @param string        $user_login
	 * @param \WP_User|null $user
	 */
	public function maybe_challenge_2fa( $user_login, $user = null ) {
		if ( self::$suppress_2fa_reentry ) {
			return; // We just completed 2FA and re-fired wp_login ourselves.
		}
		if ( null === self::$academy_login_context ) {
			return; // Not an Academy REST login — leave interactive challenges to GemSecurity's own TwoFactor::maybe_challenge().
		}
		$context = self::$academy_login_context;
		self::$academy_login_context = null; // Consume once, regardless of outcome below.

		if ( ! $user instanceof \WP_User ) {
			$user = get_user_by( 'login', $user_login );
		}
		if ( ! $user instanceof \WP_User ) {
			return;
		}
		if ( ! \GemSecurity\Modules\TwoFactor::user_needs_2fa( $user ) ) {
			return;
		}

		// Tear down the session wp_signon() just partially established.
		wp_clear_auth_cookie();
		wp_destroy_current_session();

		$method = \GemSecurity\Modules\TwoFactor::user_method( $user );
		$token  = wp_generate_password( 32, false );

		set_transient(
			self::TWO_FACTOR_PENDING_PREFIX . $token,
			[
				'user_id'  => $user->ID,
				'remember' => $context['remember'],
				'redirect' => $context['redirect'],
				'method'   => $method,
			],
			5 * MINUTE_IN_SECONDS
		);

		if ( 'email' === $method ) {
			( new \GemSecurity\Modules\TwoFactor() )->send_email_otp( $user );
		}

		\GemSecurity\Logger::log(
			'academy_2fa_challenge',
			sprintf( 'Second factor required for Academy login "%s" (%s).', $user->user_login, $method ),
			\GemSecurity\Logger::SEVERITY_INFO,
			[ 'user_id' => $user->ID ]
		);

		wp_send_json(
			[
				'success'      => false,
				'requires_2fa' => true,
				'method'       => $method,
				'token'        => $token,
				'message'      => 'email' === $method
					? __( 'Enter the 6-digit code we just emailed you.', 'academy' )
					: __( 'Open your authenticator app and enter the current 6-digit code.', 'academy' ),
			],
			200
		);
	}

	/**
	 * Registers a small REST endpoint alongside Academy's own auth routes —
	 * kept in this integration class rather than added to Academy's own
	 * `includes/api/auth.php` so the whole 2FA feature stays self-contained
	 * and a no-op when GemSecurity is absent.
	 */
	public function register_verify_2fa_route() {
		register_rest_route(
			'academy/v1',
			'/verify-2fa',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'handle_verify_2fa' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'token'  => [
						'required' => true,
						'type' => 'string'
					],
					'code'   => [
						'required' => false,
						'type' => 'string'
					],
					'resend' => [
						'required' => false,
						'type' => 'boolean'
					],
				],
			]
		);
	}

	/**
	 * Verifies the submitted second factor and, on success, completes the
	 * login exactly the way Academy's own `login_form_handler()` would have
	 * — same response shape, so no special-case handling is needed on the
	 * success path once the frontend has this token.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function handle_verify_2fa( \WP_REST_Request $request ) {
		$token   = sanitize_text_field( (string) $request->get_param( 'token' ) );
		$pending = $token ? get_transient( self::TWO_FACTOR_PENDING_PREFIX . $token ) : false;

		if ( ! $pending || empty( $pending['user_id'] ) ) {
			return new \WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'This verification session has expired. Please log in again.', 'academy' )
				],
				400
			);
		}

		$user = get_user_by( 'id', (int) $pending['user_id'] );
		if ( ! $user instanceof \WP_User ) {
			delete_transient( self::TWO_FACTOR_PENDING_PREFIX . $token );
			return new \WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'This verification session has expired. Please log in again.', 'academy' )
				],
				400
			);
		}

		$method = $pending['method'];

		if ( $request->get_param( 'resend' ) && 'email' === $method ) {
			( new \GemSecurity\Modules\TwoFactor() )->send_email_otp( $user );
			return new \WP_REST_Response(
				[
					'success' => true,
					'resent' => true,
					'message' => __( 'A new code has been sent to your email.', 'academy' )
				],
				200
			);
		}

		// Rate-limit verification attempts against this specific token —
		// GemSecurity's own wp-login.php challenge relies on the short-lived
		// single-use token alone; REST is more easily scripted, so this adds
		// a small additional safety net.
		$attempts_key = self::TWO_FACTOR_ATTEMPTS_PREFIX . $token;
		$attempts     = (int) get_transient( $attempts_key );
		if ( $attempts >= 5 ) {
			delete_transient( self::TWO_FACTOR_PENDING_PREFIX . $token );
			delete_transient( $attempts_key );
			return new \WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Too many attempts. Please log in again.', 'academy' )
				],
				429
			);
		}

		$code = (string) $request->get_param( 'code' );
		$ok   = ( new \GemSecurity\Modules\TwoFactor() )->verify_factor( $user, $method, $code );

		if ( ! $ok ) {
			set_transient( $attempts_key, $attempts + 1, 5 * MINUTE_IN_SECONDS );
			\GemSecurity\Logger::log(
				'academy_2fa_failed',
				sprintf( 'Failed second factor for Academy login "%s".', $user->user_login ),
				\GemSecurity\Logger::SEVERITY_WARNING,
				[ 'user_id' => $user->ID ]
			);
			return new \WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Invalid code. Please try again.', 'academy' )
				],
				400
			);
		}

		// Success — complete the login.
		delete_transient( self::TWO_FACTOR_PENDING_PREFIX . $token );
		delete_transient( $attempts_key );

		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, ! empty( $pending['remember'] ) );
		do_action( 'set_current_user' );

		\GemSecurity\Logger::log(
			'academy_2fa_success',
			sprintf( 'Second factor verified for Academy login "%s".', $user->user_login ),
			\GemSecurity\Logger::SEVERITY_INFO,
			[ 'user_id' => $user->ID ]
		);

		// Re-fire wp_login so other modules (session stamp, login alert) run,
		// same as GemSecurity's own wp-login.php challenge does on success.
		self::$suppress_2fa_reentry = true;
		do_action( 'wp_login', $user->user_login, $user );
		self::$suppress_2fa_reentry = false;

		$redirect_url = ! empty( $pending['redirect'] )
			? esc_url_raw( $pending['redirect'] )
			: \Academy\Helper::get_page_permalink( 'frontend_dashboard_page' );

		return new \WP_REST_Response(
			[
				'success'      => true,
				'message'      => __( 'You have logged in successfully. Redirecting...', 'academy' ),
				'redirect_url' => esc_url( $redirect_url ),
			],
			200
		);
	}

	// ── Audit logging (into GemSecurity's activity log) ──

	/**
	 * @param int $user_id
	 */
	public function on_after_student_registration( $user_id ) {
		$this->log_registration( (int) $user_id, 'student' );
	}

	/**
	 * @param int $user_id
	 */
	public function on_after_instructor_registration( $user_id ) {
		$this->log_registration( (int) $user_id, 'instructor' );
	}

	/**
	 * @param int    $user_id
	 * @param string $role
	 */
	private function log_registration( $user_id, $role ) {
		$user = get_user_by( 'id', $user_id );
		\GemSecurity\Logger::log(
			'academy_registration',
			sprintf(
				'New Academy %s registered: "%s".',
				$role,
				$user instanceof \WP_User ? $user->user_login : (string) $user_id
			),
			\GemSecurity\Logger::SEVERITY_INFO,
			[ 'user_id' => $user_id ]
		);
	}

	/**
	 * @param int    $user_id
	 * @param string $status
	 */
	public function on_instructor_status_change( $user_id, $status ) {
		$user  = get_user_by( 'id', (int) $user_id );
		$actor = wp_get_current_user();

		\GemSecurity\Logger::log(
			'academy_instructor_status',
			sprintf(
				'Instructor "%s" status changed to "%s" by "%s".',
				$user instanceof \WP_User ? $user->user_login : (string) $user_id,
				sanitize_text_field( (string) $status ),
				( $actor instanceof \WP_User && $actor->exists() ) ? $actor->user_login : 'system'
			),
			\GemSecurity\Logger::SEVERITY_NOTICE,
			[ 'user_id' => (int) $user_id ]
		);
	}

	// ── WAF content scanning on Q&A / lesson comments (GemSecurity Pro) ──

	/**
	 * `academy/frontend/insert_course_qa` and `_answered` both fire with a
	 * single prepared-comment array (see `Academy\API\QuestionAnswer::
	 * prepare_comment_for_response()`) — `id` and `content.rendered`.
	 *
	 * @param array $comment
	 */
	public function scan_qa_submission( $comment ) {
		if ( ! is_array( $comment ) ) {
			return;
		}
		$comment_id = isset( $comment['id'] ) ? (int) $comment['id'] : 0;
		$content    = isset( $comment['content']['rendered'] ) ? (string) $comment['content']['rendered'] : '';
		$this->scan_and_flag_comment( $comment_id, $content, 'Q&A submission' );
	}

	/**
	 * `academy/lesson_comment/inserted` fires with IDs only, not content.
	 *
	 * @param int $comment_id
	 * @param int $course_id
	 * @param int $lesson_id
	 */
	public function scan_lesson_comment( $comment_id, $course_id, $lesson_id ) {
		$comment = get_comment( (int) $comment_id );
		$content = $comment ? $comment->comment_content : '';
		$this->scan_and_flag_comment( (int) $comment_id, (string) $content, 'lesson comment' );
	}

	/**
	 * @param int    $comment_id
	 * @param string $content
	 * @param string $label
	 */
	private function scan_and_flag_comment( $comment_id, $content, $label ) {
		if ( ! $comment_id || '' === trim( $content ) ) {
			return;
		}

		$hit = ( new \GemSecurity\Modules\Waf() )->scan_string( $content );
		if ( ! $hit ) {
			return;
		}

		wp_spam_comment( $comment_id );

		\GemSecurity\Logger::log(
			'academy_content_blocked',
			sprintf( 'Blocked %s attempt in Academy %s (comment #%d).', strtoupper( $hit['type'] ), $label, $comment_id ),
			\GemSecurity\Logger::SEVERITY_CRITICAL,
			[
				'user_id' => 0,
				'context' => [
					'rule' => $hit['type'],
					'excerpt' => substr( $hit['value'], 0, 200 )
				],
			]
		);
	}

	// ── Geo-blocking on Academy's public REST auth routes (GemSecurity Pro) ──

	/**
	 * @param mixed            $result
	 * @param \WP_REST_Server  $server
	 * @param \WP_REST_Request $request
	 * @return mixed
	 */
	public function maybe_block_by_country( $result, $server, $request ) {
		if ( null !== $result ) {
			return $result; // Something else already short-circuited.
		}
		// Mirrors GemSecurity's own Geolocation::init() gate so this tracks
		// the existing "block login by country" setting automatically — no
		// separate toggle inside Academy.
		if (
			! \GemSecurity\Helper::get_setting( 'geo_enabled', false ) ||
			! \GemSecurity\Helper::get_setting( 'geo_whitelist_login_enabled', false ) ||
			\GemSecurity\Security\Geo::is_bypassed()
		) {
			return $result;
		}

		$route = method_exists( $request, 'get_route' ) ? $request->get_route() : '';
		if ( ! preg_match( '#^/academy/v1/(login|register|password-reset)$#', (string) $route ) ) {
			return $result;
		}

		$country = \GemSecurity\Security\Geo::current_country();
		$allowed = array_map( 'strtoupper', (array) \GemSecurity\Helper::get_setting( 'geo_allowed_countries', [] ) );

		if ( '' === $country ) {
			if ( ! \GemSecurity\Helper::get_setting( 'geo_block_unknown', false ) ) {
				return $result;
			}
		} elseif ( empty( $allowed ) || in_array( strtoupper( $country ), $allowed, true ) ) {
			return $result;
		}

		\GemSecurity\Logger::log(
			'academy_geo_blocked',
			sprintf( 'Blocked Academy request to %s from %s.', $route, $country ? $country : 'unknown' ),
			\GemSecurity\Logger::SEVERITY_WARNING,
			[
				'user_id' => 0,
				'context' => [
					'country' => $country,
					'route' => $route
				]
			]
		);

		return new \WP_REST_Response(
			[
				'success' => false,
				'message' => __( 'Access from your location is not permitted.', 'academy' ),
			],
			403
		);
	}

	// ── Enrollment-fraud alerting (GemSecurity Pro) ──

	/**
	 * @param int $course_id
	 * @param int $enroll_id
	 * @param int $user_id
	 */
	public function on_after_enroll( $course_id, $enroll_id, $user_id ) {
		$ip  = \GemSecurity\Helper::get_ip_address();
		$key = self::ENROLL_WINDOW_TRANSIENT_PREFIX . md5( $ip );

		$count = (int) get_transient( $key ) + 1;
		set_transient( $key, $count, 10 * MINUTE_IN_SECONDS );

		/**
		 * Enrollments from a single IP within the 10-minute window before an
		 * abuse alert fires.
		 *
		 * @param int $threshold
		 */
		$threshold = max( 3, (int) apply_filters( 'academy/gemsecurity_enrollment_abuse_threshold', 10 ) );
		if ( $count < $threshold ) {
			return;
		}

		\GemSecurity\Logger::log(
			'academy_enrollment_abuse',
			sprintf( 'Unusual enrollment activity from %s (%d enrollments in 10 minutes).', $ip, $count ),
			\GemSecurity\Logger::SEVERITY_WARNING,
			[
				'user_id' => (int) $user_id,
				'ip_address' => $ip,
				'context' => [ 'course_id' => (int) $course_id ]
			]
		);
	}

	// ── Rate limiting on Academy's AJAX authorization chokepoint (GemSecurity Pro) ──

	/**
	 * @param true|\WP_Error $authorized
	 * @param array          $context
	 * @return true|\WP_Error
	 */
	public function rate_limit_ajax( $authorized, $context ) {
		if ( is_wp_error( $authorized ) || true !== $authorized ) {
			return $authorized; // Already denied by something else.
		}

		$ip  = \GemSecurity\Helper::get_ip_address();
		$key = self::AJAX_RATE_TRANSIENT_PREFIX . md5( $ip );

		/**
		 * Academy AJAX requests allowed per IP per minute before rate limiting.
		 *
		 * @param int $limit
		 */
		$max    = max( 10, (int) apply_filters( 'academy/gemsecurity_ajax_rate_limit', 120 ) );
		$window = MINUTE_IN_SECONDS;

		$count = (int) get_transient( $key ) + 1;
		set_transient( $key, $count, $window );

		if ( $count <= $max ) {
			return $authorized;
		}

		\GemSecurity\Logger::log(
			'academy_ajax_rate_limited',
			sprintf(
				'Rate-limited Academy AJAX request "%s" from %s.',
				is_array( $context ) && isset( $context['action'] ) ? sanitize_text_field( (string) $context['action'] ) : '',
				$ip
			),
			\GemSecurity\Logger::SEVERITY_WARNING,
			[
				'user_id' => 0,
				'ip_address' => $ip
			]
		);

		return new \WP_Error( 'academy_gemsecurity_rate_limited', __( 'Too many requests. Please slow down.', 'academy' ) );
	}
}
