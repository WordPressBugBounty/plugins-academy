<?php
namespace Academy\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notice {

	public static function init() {
		$self = new self();
		add_action( 'admin_notices', array( $self, 'registration_notice' ) );
		add_action( 'admin_init', array( $self, 'enabled_registration_notice' ) );
	}

	public function registration_notice() {
		if ( ! current_user_can( 'manage_options' ) || get_option( 'users_can_register' ) || ! \Academy\Helper::is_academy_admin_page() ) {
			return;
		}
		?>
		<div class="academy-notice academy-notice---disabled-registration notice notice-warning">
			<div class="academy-notice__icon">
				<span class="academy-icon academy-icon--notification"></span>
			</div>
			<div class="academy-notice__content">
				<p><?php echo wp_kses_post( 'Membership option is turned off, students and instructors will not be able to sign up. <strong>Press Enable</strong> or go to <strong>Settings > General > Membership</strong> and enable "Anyone can register".' ); ?></p>
			</div>
			<div class="academy-notice__control">
				<a class="academy-btn academy-btn--bg-light-purple" href="<?php echo esc_url( add_query_arg( 'academy-registration', 'enable' ) ); ?>"><?php esc_html_e( 'Enable', 'academy' ); ?></a>
			</div>
		</div>
		<?php
	}

	public function enabled_registration_notice() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['academy-registration'] ) && 'enable' === $_GET['academy-registration'] && current_user_can( 'manage_options' ) ) {
			update_option( 'users_can_register', true );
		}
	}
}
