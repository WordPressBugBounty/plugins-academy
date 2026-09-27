<?php
namespace Academy\Guardian;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Guardian tab on the student frontend dashboard. Server-rendered through the
 * WooCommerce-style overridable template hierarchy: this class only gathers the
 * data and hands it to `frontend-dashboard/pages/guardian.php` (themes can
 * override it). The Pro addon fills the `child_card_footer` / `dashboard_sections`
 * seams the page template exposes.
 */
class Frontend {

	public static function init() {
		$self = new self();
		add_filter( 'academy/frontend_dashboard_menu_items', array( $self, 'add_menu_item' ) );
		add_action( 'academy_frontend_dashboard_guardian_endpoint', array( $self, 'render_page' ) );
	}

	public function add_menu_item( $items ) {
		$items['guardian'] = array(
			'label'    => __( 'My Family', 'academy' ),
			'area'     => 'family',
			'icon'     => 'academy-icon academy-icon--group-profile',
			'public'   => current_user_can( 'manage_academy_guardian' ),
			'priority' => 12,
		);
		return $items;
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_academy_guardian' ) ) {
			echo '<p class="academy-guardian-restricted">' . esc_html__( 'This area is available to guardians.', 'academy' ) . '</p>';
			return;
		}

		$guardian_id = get_current_user_id();
		$children    = array();
		$summary     = array(
			'children'  => 0,
			'enrolled'  => 0,
			'completed' => 0,
			'certs'     => 0,
		);
		foreach ( Store::get_children( $guardian_id ) as $student_id ) {
			$child                  = Store::child_snapshot( $student_id );
			$children[]             = $child;
			$summary['children']   += 1;
			$summary['enrolled']   += (int) $child['enrolled_count'];
			$summary['completed']  += (int) $child['completed_count'];
			$summary['certs']      += (int) $child['certificate_count'];
		}

		// Assignable courses for the per-child "assign a course" picker.
		// phpcs:disable WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- picker list capped at 200
		$course_posts   = get_posts( array(
			'post_type'      => 'academy_courses',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
		// phpcs:enable WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
		$course_options = array_map(
			function ( $c ) {
				return array(
					'id' => (int) $c->ID,
					'title' => $c->post_title
				);
			},
			$course_posts
		);

		\Academy\Helper::get_template(
			'frontend-dashboard/pages/guardian.php',
			array(
				'guardian_id'    => $guardian_id,
				'children'       => $children,
				'course_options' => $course_options,
				'summary'        => $summary,
			)
		);
	}
}
