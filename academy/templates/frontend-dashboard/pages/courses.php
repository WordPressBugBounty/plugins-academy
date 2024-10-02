<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


?>

<div 
	id="academy_frontend_dashboard_react_render" 
	path="<?php echo esc_attr( get_query_var( 'academy_dashboard_page' ) ); ?>" 
	sub-path="<?php echo esc_attr( get_query_var( 'academy_dashboard_sub_page' ) ); ?>"
><?php esc_html_e( 'Loading...', 'academy' ); ?></div>
