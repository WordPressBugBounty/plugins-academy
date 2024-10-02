<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="academy_dashboard-tabs">
	<?php
	foreach ( $menu as $menu_slug => $menu_label ) :
		$current_url = get_site_url() . $_SERVER['REQUEST_URI'];
		$endpoint_url = \Academy\Helper::get_frontend_dashboard_endpoint_url( $menu_slug );
		$class = ( $current_url === $endpoint_url ) ? 'academy_dashboard-tabs__selected' : '';
		?>
	<a 
		class="academy_dashboard-tabs__tab <?php echo esc_html( $class ); ?>" 
		role="presentation"
		href="<?php echo esc_url( $endpoint_url ); ?>"
	><?php echo esc_html( $menu_label ); ?></a>
		<?php
		endforeach;
	?>
</div>
