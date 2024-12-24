<?php
namespace Academy\Customizer\Style;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Interfaces\DynamicStyleInterface;

class FrontendDashboard extends Base implements DynamicStyleInterface {
	public static function get_css() {
		$css = '';
		$settings = self::get_settings();

		// Menu Options
		$sidebar_menu_bg_color = ( isset( $settings['frontend_dashboard_sidebar_bg_color'] ) ? $settings['frontend_dashboard_sidebar_bg_color'] : '' );
		$sidebar_menu_color = ( isset( $settings['frontend_dashboard_sidebar_color'] ) ? $settings['frontend_dashboard_sidebar_color'] : '' );
		$sidebar_icon_color = ( isset( $settings['frontend_dashboard_sidebar_icon_color'] ) ? $settings['frontend_dashboard_sidebar_icon_color'] : '' );
		$sidebar_menu_hover_color = ( isset( $settings['frontend_dashboard_sidebar_menu_hover_color'] ) ? $settings['frontend_dashboard_sidebar_menu_hover_color'] : '' );

		if ( $sidebar_menu_bg_color ) {
			$css .= ".academy-dashboard-menu , .academy-container .academy-row .academy-col-lg-12 .academy-frontend-dashboard__sidebar{
                background: $sidebar_menu_bg_color;
            }";
		}

		if ( $sidebar_menu_color ) {
			$css .= ".academy-dashboard-menu li a {
                color: $sidebar_menu_color;
            }";
		}

		if ( $sidebar_icon_color ) {
			$css .= ".academy-dashboard-menu li a .academy-icon:before {
                color: $sidebar_icon_color;
            }";
		}

		if ( $sidebar_menu_hover_color ) {
			$css .= ".academy-dashboard-menu li a:hover , .academy-dashboard-menu li a:hover .academy-icon:before {
                color: $sidebar_menu_hover_color;
            }";
			$css .= ".academyFrontendDashWrap .academy-dashboard-sidebar ul.academy-dashboard-menu li a.active, .academyFrontendDashWrap .academy-dashboard-sidebar ul.academy-dashboard-menu li a:focus, .academyFrontendDashWrap .academy-dashboard-sidebar ul.academy-dashboard-menu li a:hover {
                border-left-color: $sidebar_menu_hover_color;
                color: $sidebar_menu_hover_color;
            }";
		}

		// Topbar Options
		$topbar_bg_color = ( isset( $settings['frontend_dashboard_topbar_bg_color'] ) ? $settings['frontend_dashboard_topbar_bg_color'] : '' );
		$topbar_border_color = ( isset( $settings['frontend_dashboard_topbar_border_color'] ) ? $settings['frontend_dashboard_topbar_border_color'] : '' );
		$topbar_color = ( isset( $settings['frontend_dashboard_topbar_color'] ) ? $settings['frontend_dashboard_topbar_color'] : '' );
		if ( $topbar_bg_color ) {
			$css .= ".academy-frontend-dashboard .academy-topbar {
                background-color: $topbar_bg_color;
            }";
		}
		if ( $topbar_border_color ) {
			$css .= ".academy-frontend-dashboard .academy-topbar {
                border-color: $topbar_border_color;
            }";
		}
		if ( $topbar_color ) {
			$css .= ".academy-frontend-dashboard .academy-topbar__entry-left .academy-topbar-heading {
                color: $topbar_color;
            }";
		}

		// Card Options
		$card_bg_color = ( isset( $settings['frontend_dashboard_card_bg_color'] ) ? $settings['frontend_dashboard_card_bg_color'] : '' );
		$card_border_color = ( isset( $settings['frontend_dashboard_card_border_color'] ) ? $settings['frontend_dashboard_card_border_color'] : '' );
		$card_color = ( isset( $settings['frontend_dashboard_card_color'] ) ? $settings['frontend_dashboard_card_color'] : '' );
		if ( $card_bg_color ) {
			$css .= ".academy-analytics-cards--card {
                background: $card_bg_color;
            }";
		}
		if ( $card_border_color ) {
			$css .= ".academy-analytics-cards--card{
                border-color: $card_border_color;
            }";
		}
		if ( $card_color ) {
			$css .= ".academy-analytics-card--data h2, .academy-analytics-card--data p.academy-analytics-card--label , .academy-analytics-cards--card .academy-analytics-card--data {
                color: $card_color;
            }";
		}

		// Table Options
		$table_bg_color = ( isset( $settings['frontend_dashboard_table_bg_color'] ) ? $settings['frontend_dashboard_table_bg_color'] : '' );
		$table_border_color = ( isset( $settings['frontend_dashboard_table_border_color'] ) ? $settings['frontend_dashboard_table_border_color'] : '' );
		$table_color = ( isset( $settings['frontend_dashboard_table_color'] ) ? $settings['frontend_dashboard_table_color'] : '' );

		if ( $table_bg_color || $table_border_color ) {
			$table_css = '';
			if ( $table_bg_color ) {
				$table_css .= "background: $table_bg_color;";
			}
			if ( $table_border_color ) {
				$table_css .= "border-color: $table_border_color !important;";
			}
			if ( $table_css ) {
				$css .= ".kzui-table {
					$table_css
				}";
			}
		}
		if ( $table_color ) {
			$css .= ".kzui-table {
                color: $table_color;
            }";
		}
		return $css;
	}
}
