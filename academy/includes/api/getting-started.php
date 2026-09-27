<?php
namespace Academy\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Getting started card on the Academy dashboard: a few first steps, each
 * ticked off by what the site has actually done, not by a stored flag.
 */
class GettingStarted extends Controller {

	/**
	 * User meta: the card was hidden.
	 */
	const DISMISSED = 'academy_getting_started_dismissed';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		$self = new self();
		add_action( 'rest_api_init', [ $self, 'register_routes' ] );
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/getting-started',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_value' ],
					'permission_callback' => [ $this, 'permissions_check' ],
				],
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_value' ],
					'permission_callback' => [ $this, 'permissions_check' ],
				],
			]
		);
	}

	/**
	 * The steps and where each one stands.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_value( $request ) {
		return rest_ensure_response( self::state() );
	}

	/**
	 * Hide the card for this person.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function update_value( $request ) {
		update_user_meta( get_current_user_id(), self::DISMISSED, 1 );

		return rest_ensure_response( self::state() );
	}

	/**
	 * Not used.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function create_value( $request ) {
		return rest_ensure_response( [] );
	}

	/**
	 * Not used.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function delete_value( $request ) {
		return rest_ensure_response( [] );
	}

	/**
	 * The card's contents.
	 *
	 * @return array
	 */
	public static function state() {
		$courses  = wp_count_posts( 'academy_courses' );
		$students = get_users(
			[
				'role'   => 'academy_student',
				'number' => 1,
				'fields' => 'ID',
			]
		);
		$designed = false !== get_option( \Academy\Design\Settings::OPTION, false )
			|| \Academy\Design\Palette::get() !== \Academy\Design\Palette::defaults();

		$steps = [
			[
				'key'   => 'course',
				'title' => __( 'Create your first course', 'academy' ),
				'text'  => __( 'Add lessons, set a price or make it free, and publish.', 'academy' ),
				'done'  => isset( $courses->publish ) && (int) $courses->publish > 0,
				'url'   => 'admin.php?page=academy-courses&action=new',
				'label' => __( 'Create a course', 'academy' ),
			],
			[
				'key'   => 'design',
				'title' => __( 'Make it look like yours', 'academy' ),
				'text'  => __( 'Pick your brand colours and how course cards and course pages look.', 'academy' ),
				'done'  => $designed,
				'url'   => 'admin.php?page=academy-design',
				'label' => __( 'Open Customize', 'academy' ),
			],
			[
				'key'   => 'payments',
				'title' => __( 'Set up payments', 'academy' ),
				'text'  => __( 'Only needed if you sell courses. Free courses work straight away.', 'academy' ),
				'done'  => '' !== (string) \Academy\Helper::get_settings( 'monetization_engine', '' ),
				'url'   => 'admin.php?page=academy-settings&path=payments&tab=engine',
				'label' => __( 'Choose how to get paid', 'academy' ),
			],
			[
				'key'   => 'student',
				'title' => __( 'Welcome your first student', 'academy' ),
				'text'  => __( 'Share a course link, or enrol someone yourself to see it as they will.', 'academy' ),
				'done'  => ! empty( $students ),
				'url'   => 'admin.php?page=academy-students',
				'label' => __( 'View students', 'academy' ),
			],
		];

		$done = count(
			array_filter(
				$steps,
				static function ( $step ) {
					return $step['done'];
				}
			)
		);

		return [
			'steps'     => $steps,
			'done'      => $done,
			'show'      => $done < count( $steps ) && ! get_user_meta( get_current_user_id(), self::DISMISSED, true ),
			'adminUrl'  => admin_url(),
		];
	}

	/**
	 * Only people who manage the site see it.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return bool
	 */
	public function permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}
}
