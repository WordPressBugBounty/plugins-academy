<?php
/**
 * [academy_enrolled_courses] — the current user's enrolled courses as a grid.
 *
 * All markup lives in templates/shortcode/academy-enrolled-courses/ so a theme
 * can override it; this class only resolves which courses to show.
 */

namespace Academy\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AcademyEnrolledCourses {

	public function __construct() {
		add_shortcode( 'academy_enrolled_courses', array( $this, 'enrolled_courses' ) );
	}

	public function enrolled_courses( $atts ) {
		if ( ! is_user_logged_in() ) {
			return self::render(
				'login-required.php',
				array( 'message' => __( 'Please log in to view your enrolled courses.', 'academy' ) )
			);
		}

		$atts = shortcode_atts(
			array(
				'status'     => 'all',
				'count'      => 10,
				'orderby'    => 'date',
				'order'      => 'DESC',
				'columns'    => 3,
				'pagination' => 'yes',
			),
			$atts,
			'academy_enrolled_courses'
		);

		$user_id        = get_current_user_id();
		$status         = sanitize_text_field( $atts['status'] );
		$valid_statuses = array( 'all', 'completed', 'not-started', 'in-progress' );

		if ( ! in_array( $status, $valid_statuses, true ) ) {
			_doing_it_wrong(
				'[academy_enrolled_courses]',
				sprintf(
					/* translators: %s: the invalid status value that was passed in. */
					esc_html__( 'Unknown status "%s"; expected one of all, completed, not-started, in-progress. Falling back to "all".', 'academy' ),
					esc_html( $status )
				),
				esc_html( ACADEMY_VERSION )
			);
			$status = 'all';
		}

		$columns    = (int) $atts['columns'];
		$pagination = filter_var( $atts['pagination'], FILTER_VALIDATE_BOOLEAN );

		// -1 (all courses) is the only meaningful non-positive value; anything
		// else falsy would make WP_Query fall back to the site's posts-per-page
		// and silently ignore the attribute.
		$count = (int) $atts['count'];
		$count = ( $count > 0 || -1 === $count ) ? $count : 10;

		// A user can hold more than one enrolment record for the same course,
		// so this list arrives with duplicates in it.
		$enrolled_course_ids = array_values( array_unique(
			(array) \Academy\Helper::get_enrolled_courses_ids_by_user( $user_id )
		) );

		if ( 'all' !== $status ) {
			$enrolled_course_ids = $this->filter_courses_by_status( $enrolled_course_ids, $status, $user_id );
		}

		if ( empty( $enrolled_course_ids ) ) {
			return self::render(
				'empty-state.php',
				array(
					'message'    => $this->get_empty_state_message( $status ),
					'browse_url' => $this->get_browse_url(),
				)
			);
		}

		// A shortcode usually sits on a page, and WP paginates a singular page
		// with `page` rather than `paged` — reading only `paged` pins the grid
		// to page one forever.
		$paged = (int) ( get_query_var( 'paged' ) ? get_query_var( 'paged' ) : get_query_var( 'page' ) );
		$paged = max( 1, $paged );

		$query_args = array(
			'post__in'       => $enrolled_course_ids,
			'post_type'      => 'academy_courses',
			'posts_per_page' => $count,
			'paged'          => $paged,
			'orderby'        => $this->map_orderby( sanitize_text_field( $atts['orderby'] ) ),
			'order'          => 'ASC' === strtoupper( sanitize_text_field( $atts['order'] ) ) ? 'ASC' : 'DESC',
		);

		$grid_class = \Academy\Helper::get_responsive_column( array(
			'desktop' => $columns,
			'tablet'  => 6,
			'mobile'  => 12,
		) );

		wp_reset_query();
		// phpcs:ignore WordPress.WP.DiscouragedFunctions.query_posts_query_posts
		query_posts( apply_filters( 'academy_enrolled_courses_shortcode_args', $query_args ) );

		$output = self::render(
			'courses.php',
			array(
				'grid_class'     => $grid_class,
				'has_pagination' => $pagination,
				'paged'          => $paged,
			)
		);

		wp_reset_query();

		return $output;
	}

	/**
	 * Render one of this shortcode's templates and return it as a string.
	 *
	 * @param string $template Filename inside templates/shortcode/academy-enrolled-courses/.
	 * @param array  $args     Variables extracted into the template.
	 * @return string
	 */
	private static function render( $template, $args = array() ) {
		ob_start();
		\Academy\Helper::get_template( 'shortcode/academy-enrolled-courses/' . $template, $args );
		return ob_get_clean();
	}

	/**
	 * Narrow the user's enrolled courses down to one progress status.
	 *
	 * Completed course IDs are read in one go and the other two statuses are
	 * derived from the same lookup, so this costs a fixed number of queries
	 * rather than two per enrolled course.
	 *
	 * @param array  $course_ids Enrolled course IDs.
	 * @param string $status     'not-started', 'in-progress' or 'completed'.
	 * @param int    $user_id    User ID.
	 * @return array Filtered course IDs.
	 */
	private function filter_courses_by_status( $course_ids, $status, $user_id ) {
		if ( empty( $course_ids ) ) {
			return array();
		}

		// Always intersect with the enrolled set: a user can hold progress on a
		// course they are no longer enrolled in, and this shortcode only ever
		// shows enrolled courses.
		$completed_ids = array_intersect(
			$course_ids,
			(array) \Academy\Helper::get_completed_courses_ids_by_user( $user_id )
		);

		if ( 'completed' === $status ) {
			return array_values( $completed_ids );
		}

		if ( 'not-started' !== $status && 'in-progress' !== $status ) {
			return $course_ids;
		}

		$filtered_ids = array();

		foreach ( $course_ids as $course_id ) {
			if ( in_array( $course_id, $completed_ids, false ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.FoundNonStrictFalse
				continue;
			}

			$has_progress = 0 < \Academy\Helper::get_total_number_of_completed_course_topics_by_course_and_student_id( $course_id, $user_id );

			if ( ( 'in-progress' === $status ) === $has_progress ) {
				$filtered_ids[] = $course_id;
			}
		}

		return $filtered_ids;
	}

	/**
	 * Map the shortcode's orderby to one WP_Query accepts.
	 *
	 * @param string $orderby Orderby attribute.
	 * @return string
	 */
	private function map_orderby( $orderby ) {
		$map = array(
			'date'  => 'post_date',
			'title' => 'post_title',
			'rand'  => 'rand',
		);

		return isset( $map[ $orderby ] ) ? $map[ $orderby ] : 'post_date';
	}

	/**
	 * Explain why the grid is empty, in the terms the visitor filtered by.
	 *
	 * @param string $status Status the shortcode was filtered to.
	 * @return string
	 */
	private function get_empty_state_message( $status = 'all' ) {
		$status_labels = array(
			'not-started' => __( 'not started', 'academy' ),
			'in-progress' => __( 'in progress', 'academy' ),
			'completed'   => __( 'completed', 'academy' ),
		);

		if ( ! isset( $status_labels[ $status ] ) ) {
			return __( 'You haven\'t enrolled in any courses yet.', 'academy' );
		}

		return sprintf(
			/* translators: %s: Course status, e.g. "in progress". */
			__( 'You don\'t have any %s courses yet.', 'academy' ),
			$status_labels[ $status ]
		);
	}

	/**
	 * Link to the course archive for the empty state's call to action.
	 *
	 * @return string Empty when the archive is unreachable, so the template can
	 *                drop the button rather than link to a guessed URL.
	 */
	private function get_browse_url() {
		$browse_url = get_post_type_archive_link( 'academy_courses' );

		return $browse_url ? $browse_url : '';
	}
}
