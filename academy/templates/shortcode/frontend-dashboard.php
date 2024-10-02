<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

	<div class="academy-frontend-dashboard">
		<div class="academy-container">
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
