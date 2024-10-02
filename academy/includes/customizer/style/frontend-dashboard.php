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
			$css .= ".academyFrontendDashWrap .academy-dashboard-sidebar {
                background: $sidebar_menu_bg_color;
            }";
		}

		if ( $sidebar_menu_color ) {
			$css .= ".academyFrontendDashWrap .academy-dashboard-sidebar ul.academy-dashboard-menu li a, 
            .academyFrontendDashWrap .academy-dashboard-sidebar .academy-dashboard-user__content>span.user-profile-name, 
            .academyFrontendDashWrap .academy-dashboard-sidebar .academy-dashboard-user__content>span.user-designation,
            .academyFrontendDashWrap .academy-dashboard-sidebar ul.academy-dashboard-menu li.academy-dashboard-menu__divider {
                color: $sidebar_menu_color;
            }";
		}

		if ( $sidebar_icon_color ) {
			$css .= ".academyFrontendDashWrap .academy-dashboard-sidebar ul.academy-dashboard-menu li a span.academy-icon:before {
                color: $sidebar_icon_color;
            }";
		}

		if ( $sidebar_menu_hover_color ) {
			$css .= ".academyFrontendDashWrap .academy-dashboard-sidebar ul.academy-dashboard-menu li a.active span.academy-icon:before, 
            .academyFrontendDashWrap .academy-dashboard-sidebar ul.academy-dashboard-menu li a:focus span.academy-icon:before, 
            .academyFrontendDashWrap .academy-dashboard-sidebar ul.academy-dashboard-menu li a:hover span.academy-icon:before,
            .academyFrontendDashWrap .academy-dashboard-sidebar ul.academy-dashboard-menu li a.active {
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
			$css .= ".academyFrontendDashWrap .academy-dashboard-entry-content .academy-topbar {
                background-color: $topbar_bg_color;
            }";
		}
		if ( $topbar_border_color ) {
			$css .= ".academyFrontendDashWrap .academy-dashboard-entry-content .academy-topbar {
                border-color: $topbar_border_color;
            }";
		}
		if ( $topbar_color ) {
			$css .= ".academyFrontendDashWrap .academy-topbar__entry-left .academy-topbar-heading {
                color: $topbar_color;
            }";
		}

		// Card Options
		$card_bg_color = ( isset( $settings['frontend_dashboard_card_bg_color'] ) ? $settings['frontend_dashboard_card_bg_color'] : '' );
		$card_border_color = ( isset( $settings['frontend_dashboard_card_border_color'] ) ? $settings['frontend_dashboard_card_border_color'] : '' );
		$card_color = ( isset( $settings['frontend_dashboard_card_color'] ) ? $settings['frontend_dashboard_card_color'] : '' );
		if ( $card_bg_color ) {
			$css .= ".academyFrontendDashWrap .academy-dashboard-entry-content .academy-dashboard-data .academy-analytics-cards--card {
                background: $card_bg_color;
            }";
		}
		if ( $card_border_color ) {
			$css .= ".academyFrontendDashWrap .academy-dashboard-entry-content .academy-dashboard-data .academy-analytics-cards--card {
                border-color: $card_border_color;
            }";
		}
		if ( $card_color ) {
			$css .= ".academyFrontendDashWrap .academy-dashboard-entry-content .academy-dashboard-data .academy-analytics-cards--card .academy-analytics-card--value,
            .academyFrontendDashWrap .academy-dashboard-entry-content .academy-dashboard-data .academy-analytics-cards--card .academy-analytics-card--label {
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
				$css .= ".academy-list-wrap .rdt_Table .rdt_TableHead .rdt_TableHeadRow,
				.academy-list-wrap .rdt_Table .rdt_TableBody .rdt_TableRow,
				.academy-list-wrap nav.rdt_Pagination {
					$table_css
				}";
			}
		}
		if ( $table_color ) {
			$css .= ".academy-list-wrap .rdt_Table .rdt_TableHead .rdt_TableCol, 
            .academy-list-wrap .rdt_Table .rdt_TableRow a,
            .academy-list-wrap .rdt_Table .rdt_TableBody .rdt_TableCell {
                color: $table_color;
            }";
		}
		return $css;
	}
}
