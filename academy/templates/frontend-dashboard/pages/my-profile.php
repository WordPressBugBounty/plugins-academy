<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Academy\Helper;
?>

<a class="academy-edit-btn" href="<?php echo esc_url( Helper::get_frontend_dashboard_endpoint_url( 'settings' ) ); ?>">
	<span class="academy-icon academy-icon--edit"></span>
</a>

<div class="academy-dashboard-profile-info academy-dashboard__content">
	<div class="academy-dashboard-profile-info__details">
			<?php foreach ( $user_data as $data ) : ?>
				<div class="academy-dashboard-profile-details">
					<span class="academy-dashboard-profile-details__label"><?php echo esc_html( $data['label'] ); ?></span>
					<span>-</span>
					<span class="academy-dashboard-profile-details__data"><?php echo esc_html( $data['value'] ); ?></span>
				</div>
			<?php endforeach; ?>
	</div>
</div>
