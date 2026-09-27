<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

	<div class="<?php echo esc_attr( \Academy\FrontendDashboard\Dashboard::wrapper_class() ); ?>">
		<div class="<?php echo esc_attr( \Academy\FrontendDashboard\Dashboard::inner_container_class() ); ?>">
			<div class="academy-row">
				<div class="academy-col-lg-12">
					<?php
						\Academy\Helper::get_template( 'frontend-dashboard/sidebar.php' );
					?>
					<?php
						\Academy\Helper::get_template( 'frontend-dashboard/content.php' );
					?>
				</div>
			</div>
		</div>

	</div>
