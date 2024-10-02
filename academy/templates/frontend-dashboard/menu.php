<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Helper;


foreach ( $menu_lists as $endpoint => $menu ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	if ( ! $menu['public'] ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		continue;
	}
	?>
	<li class="academy-dashboard-menu__item-<?php echo esc_attr( $endpoint ); ?> <?php echo get_query_var( 'academy_dashboard_page' ) === $endpoint || ( get_query_var( 'academy_dashboard_page' ) === '' && 'index' === $endpoint ) ? 'academy-dashboard-menu__item-current' : ''; ?>">
		<a href="<?php echo esc_url( isset( $menu['permalink'] ) ? $menu['permalink'] : Helper::get_frontend_dashboard_endpoint_url( $endpoint ) ); ?>">
			<i class="<?php echo esc_html( $menu['icon'] ); ?>"></i>
				<span class="academy-dashboard-menu__item-label"><?php echo esc_html( $menu['label'] ); ?></span>
			<?php
			if ( isset( $menu['child_items'] ) ) :
				?>
				<?php
				endif;
			?>
		</a>
		<?php
		if ( isset( $menu['child_items'] ) ) :
			?>
		<span class="academy-icon academy-icon--angle-right menu-icon"></span>
		<ul class="academy-dashboard-submenu" id="academy-<?php echo esc_attr( $endpoint ); ?>-submenu">
			<li class="academy-dashboard-menu__child-item-<?php echo esc_attr( $endpoint ); ?> <?php echo get_query_var( 'academy_dashboard_page' ) === $endpoint && get_query_var( 'academy_dashboard_sub_page' ) === '' ? 'academy-dashboard-menu__item-current' : ''; ?>">
				<a href="<?php echo esc_url( Helper::get_frontend_dashboard_endpoint_url( $endpoint ) ); ?>">
				<span><?php echo sprintf( esc_html__( 'All %s', 'academy' ), esc_html( $menu['label'] ) ); ?></span>
				</a>
			</li>
			<?php
			foreach ( $menu['child_items'] as $child_endpoint => $child_menu ) :
				?>
			<li class="academy-dashboard-menu__child-item<?php echo esc_attr( $endpoint . '-' . $child_endpoint ); ?> <?php echo get_query_var( 'academy_dashboard_sub_page' ) === $child_endpoint ? 'academy-dashboard-menu__item-current' : ''; ?>">
				<a href="<?php echo esc_url( Helper::get_frontend_dashboard_endpoint_url( $endpoint . '/' . $child_endpoint ) ); ?>"> 
					<span><?php echo esc_html( $child_menu['label'] ); ?></span>    
				</a>
			</li>
					<?php
				endforeach;
			?>
		</ul>
		<?php endif; ?>
	</li>
	<?php
endforeach;

