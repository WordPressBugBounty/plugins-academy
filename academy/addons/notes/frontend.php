<?php
namespace AcademyNotes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Learner-dashboard integration: a "My Notes" page that aggregates every note
 * across all enrolled courses. Registers the menu item (which also drives the
 * rewrite rule) and the page endpoint that mounts the React app.
 */
class Frontend {

	public static function init() {
		$self = new self();
		add_filter( 'academy/frontend_dashboard_menu_items', array( $self, 'add_menu_item' ) );
		add_action( 'academy_frontend_dashboard_my-notes_endpoint', array( $self, 'render_page' ) );
	}

	public function add_menu_item( $items ) {
		$items['my-notes'] = array(
			'label'    => __( 'My Notes', 'academy' ),
			'area'     => 'learning',
			'icon'     => 'academy-icon academy-icon--edit',
			'public'   => true,
			'priority' => 22,
		);
		return $items;
	}

	public function render_page() {
		printf(
			'<div id="academy_frontend_dashboard_react_render" path="%s" sub-path="%s">%s</div>',
			esc_attr( get_query_var( 'academy_dashboard_page' ) ),
			esc_attr( get_query_var( 'academy_dashboard_sub_page' ) ),
			esc_html__( 'Loading…', 'academy' )
		);
	}
}
