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
		$self->dispatch_notices();
		$self->dispatch_hooks();
	}

	public static function dispatch_hooks() {
		$self = new self();
		add_action( 'admin_init', array( $self, 'handle_admin_request' ) );
	}

	public static function dispatch_notices() {
		if ( self::has_upgrade_to_pro_notice() ) {
			self::add_notice('pro_upgrade_discount_offer', [
				'type'      => 'discount_offer',
				'coupon_code'   => 'i9hE7i',
				'message'   => wp_kses_post( 'Up to 40% Off' ),
				'button_text' => __( 'Claim Discount', 'academy' ),
				'button_action' => 'https://academylms.net/pricing/',
				'dismissible' => true
			]);
		}

		if ( ! current_user_can( 'manage_options' ) || ! get_option( 'users_can_register' ) ) {
			self::add_notice('users_can_register', [
				'type'      => 'info',
				'message'   => wp_kses_post( 'Membership option is turned off, students and instructors will not be able to sign up. <strong>Press Enable</strong> or go to <strong>Settings > General > Membership</strong> and enable "Anyone can register".' ),
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

		self::$notices[ $notice_name ] = $args;
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
		if ( isset( $_GET['security'] ) && wp_verify_nonce( $_GET['security'], 'academy_nonce' ) ) {
			if ( isset( $_GET['academy-registration'] ) && 'enable' === $_GET['academy-registration'] ) {
				$this->enabled_registration_notice();
			} elseif ( isset( $_GET['academy-dismiss-notice'] ) && 'pro_upgrade_discount_offer' === $_GET['academy-dismiss-notice'] ) {
				$this->pro_upgrade_discount_offer();
			} elseif ( isset( $_GET['deactivate-academy-certificates'] ) && (bool) $_GET['deactivate-academy-certificates'] ) {
				$this->deactivated_certificate_addon();
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
}
