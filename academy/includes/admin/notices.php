<?php
namespace Academy\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Helper;
class Notices {
	private static $notices = [];

	public static function init() {
		$self = new self();
		$self->dispatch_hooks();
	}

	public static function dispatch_hooks() {
		$self = new self();
		add_action( 'init', array( $self, 'dispatch_notices' ) );
		add_action( 'admin_init', array( $self, 'handle_admin_request' ) );
	}

	public static function dispatch_notices() {
		if ( self::has_upgrade_to_pro_notice() ) {
			self::add_notice('pro_upgrade_discount_offer', [
				'type'      => 'discount_offer',
				'coupon_code'   => 'i9hE7i',
				'message'   => wp_kses_post( __( 'Up to 40% Off', 'academy' ) ),
				'button_text' => __( 'Claim Discount', 'academy' ),
				'button_action' => 'https://academylms.net/pricing/',
				'dismissible' => true
			]);
		}

		if ( current_user_can( 'manage_options' ) && ! get_option( 'users_can_register' ) ) {
			self::add_notice('users_can_register', [
				'type'      => 'info',
				'message'   => wp_kses_post( __( 'Membership option is turned off, students and instructors will not be able to sign up. <strong>Press Enable</strong> or go to <strong>Settings > General > Membership</strong> and enable "Anyone can register".', 'academy' ) ),
				'button_text' => __( 'Enable Registration', 'academy' ),
				'button_action' => esc_url(add_query_arg(array(
					'academy-registration' => 'enable',
					'security' => wp_create_nonce( 'academy_nonce' ),
				)))
			]);
		}

		// Academy Certificate Related notice
		if ( Helper::get_addon_active_status( 'certificates' ) && Helper::is_plugin_active( 'academy-certificates/academy-certificates.php' ) ) {
			self::add_notice('deprecated_certificate_addon', [
				'type'                  => 'danger',
				'message'               => esc_html__( 'To avoid conflicts, please first deactivate the Academy Certificate plugin.', 'academy' ),
				'button_text'           => __( 'Deactivate Academy Certificate Plugin.', 'academy' ),
				'button_action' => esc_url(add_query_arg(array(
					'deactivate-academy-certificates' => true,
					'security' => wp_create_nonce( 'academy_nonce' ),
				)))
			]);
		} elseif ( Helper::is_plugin_active( 'academy-certificates/academy-certificates.php' ) ) {
			self::add_notice('deprecated_certificate_addon', [
				'type'                  => 'danger',
				'message'               => esc_html__( "We've detected that you're using a deprecated certificate addon. We've rebuilt the certificate feature, which is now included in Academy LMS—no need to install an extra plugin.", 'academy' ),
				'button_text'           => __( 'Learn More', 'academy' ),
				'button_action' => esc_url(add_query_arg(array(
					'deactivate-academy-certificates' => true,
					'security' => wp_create_nonce( 'academy_nonce' ),
				)))
			]);
		}//end if

		// Check Academy Pro Minimum version notice
		if ( Helper::is_active_academy_pro() && ! version_compare( implode( '.', array_slice( explode( '.', ACADEMY_VERSION ), 0, 2 ) ), implode( '.', array_slice( explode( '.', ACADEMY_PRO_REQUIRED_CORE_VERSION ), 0, 2 ) ), '=' ) ) {
			self::add_notice('version_conflicts_academy_pro', [
				'type'                  => 'danger',
				'message'               => esc_html__( 'You are using an outdated version of Academy LMS Pro. Please update to the latest version to ensure compatibility and prevent conflicts.', 'academy' ),
				'button_text'           => esc_html__( 'Update Now', 'academy' ),
				'button_action' => esc_url( admin_url( 'options-general.php?page=academy-pro' ) ),
			]);
		}

		self::maybe_add_gemsecurity_notice();
		self::maybe_add_recaptcha_deprecation_notice();
	}

	/**
	 * Cascading GemSecurity compatibility notice: install → activate → enable
	 * Login Security. At most one of the three fires per request, evaluated
	 * in severity order, so admins never see stacked/contradictory warnings.
	 *
	 * Only surfaces for sites that actually use Academy's deprecated Social
	 * Login or reCAPTCHA features — GemSecurity is their unified replacement
	 * (Social Login + reCAPTCHA + brute-force protection for the
	 * `academy/v1/login` endpoint), so the recommendation is irrelevant, and
	 * the notice stays hidden, on installs that use neither.
	 */
	public static function maybe_add_gemsecurity_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Gate: only relevant when Social Login or reCAPTCHA is in use.
		$uses_social_login = Helper::get_addon_active_status( 'social-login', true );
		$uses_recaptcha    = (bool) Helper::get_settings( 'is_enabled_recaptcha', false );
		if ( ! $uses_social_login && ! $uses_recaptcha ) {
			return;
		}

		if ( ! Helper::is_plugin_installed( 'gemsecurity/gemsecurity.php' ) ) {
			self::add_notice( 'academy_gemsecurity_status', [
				'type'          => 'warning',
				'message'       => esc_html__( 'You\'re using Academy\'s Social Login and/or reCAPTCHA. GemSecurity is their all-in-one replacement — it handles Social Login, reCAPTCHA, and adds brute-force protection to your login. Download and install it to get started.', 'academy' ),
				'button_text'   => __( 'Download GemSecurity', 'academy' ),
				'button_action' => 'https://store.kodezen.com/se-download/free/gemsecurity/latest/',
				'dismissible'   => true,
			] );
			return;
		}

		if ( ! Helper::is_active_gemsecurity() ) {
			self::add_notice( 'academy_gemsecurity_status', [
				'type'          => 'warning',
				'message'       => esc_html__( 'GemSecurity is installed but not active. Activate it to replace Academy\'s Social Login and reCAPTCHA with its own modules and protect your login against brute-force attacks.', 'academy' ),
				'button_text'   => __( 'Activate GemSecurity', 'academy' ),
				'button_action' => esc_url( add_query_arg( array(
					'academy-gemsecurity-activate' => true,
					'security'                     => wp_create_nonce( 'academy_nonce' ),
				) ) ),
				'dismissible'   => true,
			] );
			return;
		}

		// GemSecurity is active — check whether Login Security (the one
		// module Academy's integration actually relies on) is on. Guarded
		// with has_filter() and a `true` default so an older GemSecurity
		// version without this filter, or an unrecognized module id, never
		// falsely triggers the notice (fail silent, not fail nagging).
		if ( has_filter( 'gemsecurity/module_active' ) && ! apply_filters( 'gemsecurity/module_active', true, 'login-security' ) ) {
			self::add_notice( 'academy_gemsecurity_status', [
				'type'          => 'warning',
				'message'       => esc_html__( 'GemSecurity\'s Login Security module is disabled. Enable it to protect Academy\'s login against brute-force attacks.', 'academy' ),
				'button_text'   => __( 'Enable Protection', 'academy' ),
				'button_action' => esc_url( admin_url( 'admin.php?page=gemsecurity-modules' ) ),
				'dismissible'   => true,
			] );
		}
	}

	/**
	 * The reCAPTCHA option is deprecated ahead of GemSecurity's own replacement, which
	 * does not exist yet for Academy — so this notice never says "switch
	 * now" or escalates; the message is constant until v4.1.0 actually ships
	 * a working alternative.
	 */
	public static function maybe_add_recaptcha_deprecation_notice() {
		if ( ! \Academy\Helper::get_settings( 'is_enabled_recaptcha', false ) ) {
			return;
		}

		self::add_notice( 'deprecated_recaptcha_feature', [
			'type'          => 'warning',
			'message'       => esc_html__( 'Academy\'s built-in reCAPTCHA is deprecated and will be replaced by GemSecurity\'s own reCAPTCHA integration in v4.1.0.', 'academy' ),
			'button_text'   => __( 'Learn More', 'academy' ),
			'button_action' => 'https://academylms.net/docs/how-to-use-google-recaptcha-with-academy-lms/',
			'dismissible'   => true,
		] );
	}


	public static function add_notice( $notice_name, $args ) {
		$defaults = array(
			'type' => 'info',
			'message' => '',
			'button_text' => 'Click Here',
			'button_action' => '#',
			'dismissible' => false,
			'notice_key' => $notice_name,
		);

		$args = wp_parse_args( $args, $defaults );

		// A dismissible notice the current user already closed (see
		// Ajax\Miscellaneous::dismiss_admin_notice()) never gets added in the
		// first place — filtered here, once, for every call site, so it can't
		// flash on render and any future notice added through this same
		// method automatically gets persistent per-user dismissal for free.
		// `pro_upgrade_discount_offer` keeps its own pre-existing site-wide
		// dismissal (gated earlier in has_upgrade_to_pro_notice()), untouched.
		if ( ! empty( $args['dismissible'] ) && in_array( $notice_name, self::get_dismissed_notices(), true ) ) {
			return;
		}

		self::$notices[ $notice_name ] = $args;
	}

	/**
	 * Notice keys the current user has dismissed (per-user, via user meta —
	 * see Ajax\Miscellaneous::dismiss_admin_notice()).
	 *
	 * @return string[]
	 */
	private static function get_dismissed_notices() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return array();
		}
		$dismissed = get_user_meta( $user_id, 'academy_dismissed_admin_notices', true );
		return is_array( $dismissed ) ? $dismissed : array();
	}

	public static function get_notices() {
		return self::$notices;
	}

	public static function has_upgrade_to_pro_notice() {
		if ( \Academy\Helper::is_plugin_installed( 'academy-pro/academy-pro.php' ) || get_option( 'academy_pro_version' ) || get_option( 'academy_disabled_pro_upgrade_discount_offer' ) ) {
			return false;
		}

		$saved_time = get_option( 'academy_first_install_time' );
		$three_days_ago = Helper::get_time() - ( 3 * DAY_IN_SECONDS );

		if ( $saved_time < $three_days_ago ) {
			return true;
		}

		return false;
	}

	public function handle_admin_request() {
		if ( isset( $_GET['security'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['security'] ) ), 'academy_nonce' ) ) {
			if ( isset( $_GET['academy-registration'] ) && 'enable' === $_GET['academy-registration'] ) {
				$this->enabled_registration_notice();
			} elseif ( isset( $_GET['academy-dismiss-notice'] ) && 'pro_upgrade_discount_offer' === $_GET['academy-dismiss-notice'] ) {
				$this->pro_upgrade_discount_offer();
			} elseif ( isset( $_GET['deactivate-academy-certificates'] ) && (bool) $_GET['deactivate-academy-certificates'] ) {
				$this->deactivated_certificate_addon();
			} elseif ( isset( $_GET['academy-gemsecurity-activate'] ) && (bool) $_GET['academy-gemsecurity-activate'] ) {
				$this->activate_gemsecurity();
			}
		}
	}
	public function enabled_registration_notice() {
		if ( current_user_can( 'manage_options' ) ) {
			update_option( 'users_can_register', true );
			wp_safe_redirect( admin_url( 'admin.php?page=academy' ) );
			exit;
		}
	}
	public function pro_upgrade_discount_offer() {
		if ( current_user_can( 'manage_academy_instructor' ) ) {
			add_option( 'academy_disabled_pro_upgrade_discount_offer', true, '', 'no' );
			wp_safe_redirect( admin_url( 'admin.php?page=academy' ) );
			exit;
		}
	}

	public function deactivated_certificate_addon() {
		if ( current_user_can( 'activate_plugins' ) ) {
			deactivate_plugins( 'academy-certificates/academy-certificates.php' );
			wp_safe_redirect( admin_url( 'admin.php?page=academy' ) );
			exit;
		}
	}

	public function activate_gemsecurity() {
		if ( current_user_can( 'activate_plugins' ) ) {
			if ( ! function_exists( 'activate_plugin' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			activate_plugin( 'gemsecurity/gemsecurity.php' );
			wp_safe_redirect( admin_url( 'admin.php?page=academy' ) );
			exit;
		}
	}
}
