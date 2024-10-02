<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>


<div class="academy-frontend-dashboard__content">
	<?php
		/**
		 * @hook -'academy_frontend_dashboard_content
		 */
		do_action( 'academy_frontend_dashboard_content' )
	?>
</div>
