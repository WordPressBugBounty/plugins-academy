<?php
namespace Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PermalinkRewrite {
	public static function init() {
		$self = new self();
		add_filter( 'query_vars', array( $self, 'register_query_vars' ) );
		add_action( 'generate_rewrite_rules', array( $self, 'add_rewrite_rules' ) );
		add_filter( 'post_type_link', array( $self, 'change_curriculum_url' ), 10, 2 );
	}
	public function register_query_vars( $query_vars ) {
		$query_vars[] = 'course_subpage';
		$query_vars[] = 'source';
		$query_vars[] = 'curriculum_type';
		$query_vars[] = 'course_name';
		$query_vars[] = 'academy_dashboard_page';
		$query_vars[] = 'academy_dashboard_sub_page';
		return $query_vars;
	}
	public function add_rewrite_rules( $wp_rewrite ) {
		$permalinks = \Academy\Helper::get_permalink_structure();
		$course_rewrite_slug = str_replace( '/', '', $permalinks['course_rewrite_slug'] );
		$new_rules         = [
			$course_rewrite_slug . '/(.+?)/lessons/(.+?)/?$' => 'index.php?source=lessons', // will be removed after migrate user v1.7.4
			$course_rewrite_slug . '/(.+?)/curriculums/(.+?)/?$' => 'index.php?source=curriculums',
			$course_rewrite_slug . '/(.+?)/certificate/(.+?)/?$' => 'index.php?source=certificate',
			// Lesson Permalink
			$course_rewrite_slug . '/(.+?)/lesson/(.+?)/?$' => 'index.php?curriculum_type=lesson&course_name=' . $wp_rewrite->preg_index( 1 ) . '&name=' . $wp_rewrite->preg_index( 2 ),
			$course_rewrite_slug . '/(.+?)/quiz/(.+?)/?$' => 'index.php?curriculum_type=quiz&course_name=' . $wp_rewrite->preg_index( 1 ) . '&name=' . $wp_rewrite->preg_index( 2 ),
			$course_rewrite_slug . '/(.+?)/assignment/(.+?)/?$' => 'index.php?curriculum_type=assignment&course_name=' . $wp_rewrite->preg_index( 1 ) . '&name=' . $wp_rewrite->preg_index( 2 ),
			$course_rewrite_slug . '/(.+?)/booking/(.+?)/?$' => 'index.php?curriculum_type=booking&course_name=' . $wp_rewrite->preg_index( 1 ) . '&name=' . $wp_rewrite->preg_index( 2 ),
			$course_rewrite_slug . '/(.+?)/zoom/(.+?)/?$' => 'index.php?curriculum_type=zoom&course_name=' . $wp_rewrite->preg_index( 1 ) . '&name=' . $wp_rewrite->preg_index( 2 ),
			$course_rewrite_slug . '/(.+?)/meeting/(.+?)/?$' => 'index.php?curriculum_type=meeting&course_name=' . $wp_rewrite->preg_index( 1 ) . '&name=' . $wp_rewrite->preg_index( 2 ),
		];

		// Frontend Dashboard
		$dashboard_page_id = (int) Helper::get_settings( 'frontend_dashboard_page' );
		$dashboard_page_slug = get_post_field( 'post_name', $dashboard_page_id );
		$dashboard_pages     = Helper::get_frontend_dashboard_menu_items();
		foreach ( $dashboard_pages as $dashboard_key => $dashboard_page ) {
			$new_rules[ "({$dashboard_page_slug})/{$dashboard_key}/?$" ] = 'index.php?pagename=' . $wp_rewrite->preg_index( 1 ) . '&academy_dashboard_page=' . $dashboard_key;
			$new_rules[ "({$dashboard_page_slug})/{$dashboard_key}/(.+?)/?$" ] = 'index.php?pagename=' . $wp_rewrite->preg_index( 1 ) . '&academy_dashboard_page=' . $dashboard_key . '&academy_dashboard_sub_page=' . $wp_rewrite->preg_index( 2 );
			// Child Items
			if ( isset( $dashboard_page['child_items'] ) && is_array( $dashboard_page['child_items'] ) ) {
				foreach ( $dashboard_page['child_items'] as $child_key => $child_page ) {
					$new_rules[ "({$dashboard_page_slug})/{$dashboard_key}/{$child_key}/?$" ] = 'index.php?pagename=' . $wp_rewrite->preg_index( 1 ) . '&academy_dashboard_page=' . $dashboard_key;
					$new_rules[ "({$dashboard_page_slug})/{$dashboard_key}/{$child_key}/(.+?)/?$" ] = 'index.php?pagename=' . $wp_rewrite->preg_index( 1 ) . '&academy_dashboard_page=' . $dashboard_key . '&academy_dashboard_sub_page=' . $child_key;
				}
			}
		}

		$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
	}

	public function change_curriculum_url( $post_link, $id = 0 ) {
		global $wp_query;

		if ( ! (bool) Helper::get_settings( 'is_enabled_lessons_php_render' ) || empty( $wp_query->query_vars['curriculum_type'] ) ) {
			return $post_link;
		}

		$post             = get_post( $id );
		$permalinks = \Academy\Helper::get_permalink_structure();
		$course_rewrite_slug = str_replace( '/', '', $permalinks['course_rewrite_slug'] );
		$course_post_type = $course_rewrite_slug;

		$course_name = get_query_var( 'course_name' );

		if ( is_object( $post ) && 'academy_quiz' === $post->post_type && $course_name ) {
			return home_url( "/{$course_post_type}/{$course_name}/quiz/{$post->post_name}/" );
		} elseif ( is_object( $post ) && 'academy_assignments' === $post->post_type && $course_name ) {
			return home_url( "/{$course_post_type}/{$course_name}/assignment/{$post->post_name}/" );
		} elseif ( is_object( $post ) && 'academy_zoom' === $post->post_type && $course_name ) {
			return home_url( "/{$course_post_type}/{$course_name}/zoom/{$post->post_name}/" );
		} elseif ( is_object( $post ) && 'academy_meeting' === $post->post_type && $course_name ) {
			return home_url( "/{$course_post_type}/{$course_name}/meeting/{$post->post_name}/" );
		} elseif ( is_object( $post ) && 'academy_booking' === $post->post_type && $course_name ) {
			return home_url( "/{$course_post_type}/{$course_name}/booking/{$post->post_name}/" );
		}
		return $post_link;
	}
}
