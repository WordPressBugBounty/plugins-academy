<?php
namespace  Academy\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy;
use Academy\Helper;
use WP_Query;

class Template {
	public static function init() {
		$self = new self();
		$self->dispatch_hook();
		Template\Loader::init();
	}

	public function dispatch_hook() {
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 30 );
		add_action( 'template_redirect', array( $this, 'archive_course_template_redirect' ) );
		add_action( 'template_redirect', array( $this, 'course_curriculum_learn_page_redirect' ) );
		add_action( 'template_redirect', array( $this, 'frontend_dashboard_template_redirect' ) );
		add_filter( 'pre_get_document_title', array( $this, 'pre_get_document_title' ), 30, 1 );
		add_filter( 'post_type_archive_title', array( $this, 'archive_course_document_title' ), 30, 2 );
	}

	/**
	 * Hook into pre_get_posts to do the main product query.
	 *
	 * @param WP_Query $q Query instance.
	 */
	public function pre_get_posts( $q ) {
		$per_page = (int) \Academy\Helper::get_settings( 'course_archive_courses_per_page', 12 );
		if ( $q->is_main_query() && ! $q->is_feed() && ! is_admin() ) {
			if ( ! empty( $q->query['author_name'] ) && Academy\Helper::get_settings( 'is_show_public_profile' ) ) {
				$user = get_user_by( 'login', $q->query['author_name'] );
				if ( $user ) {
					if ( current( $user->roles ) === 'academy_instructor' || current( $user->roles ) === 'administrator' ) {
						$q->set( 'post_type', array( 'academy_courses' ) );
						$q->set( 'author', $q->query['author_name'] );
						$q->set( 'posts_per_page', $per_page );
					}
				}
			} elseif ( is_archive( 'academy_courses' ) && 'academy_courses' === $q->query['post_type'] ) {
				$paged = ( get_query_var( 'paged' ) ) ? absint( get_query_var( 'paged' ) ) : 1;
				$orderby = ( get_query_var( 'orderby' ) ) ? get_query_var( 'orderby' ) : Academy\Helper::get_settings( 'course_archive_courses_order' );
				$q->set( 'posts_per_page', $per_page );
				$q->set( 'paged', $paged );
				$q->set( 'orderby', $orderby );
			}//end if
		}//end if
	}

	public function archive_course_template_redirect() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['page_id'] ) && '' === get_option( 'permalink_structure' ) && (int) \Academy\Helper::get_settings( 'course_page' ) === absint( $_GET['page_id'] ) ) {
			$archive_link = $this->get_post_type_archive_link( 'academy_courses' );
			if ( $archive_link ) {
				wp_safe_redirect( $this->get_post_type_archive_link( 'academy_courses' ) );
				exit;
			}
		}
	}

	public function course_curriculum_learn_page_redirect() {
		global $post;
		if ( $post && (int) Helper::get_settings( 'is_enabled_lessons_php_render' ) && (int) Helper::get_settings( 'lessons_page' ) === (int) $post->ID ) {
			$course_id = Helper::get_last_course_id();
			if ( $course_id ) {
				wp_safe_redirect( Helper::get_start_course_permalink( $course_id ) );
			}
		}
	}

	public function frontend_dashboard_template_redirect() {
		if ( ! is_user_logged_in() && (int) \Academy\Helper::get_settings( 'frontend_dashboard_page' ) === get_the_ID() ) {
			if ( ! \Academy\Helper::get_settings( 'is_enabled_academy_login', true ) && wp_safe_redirect( wp_login_url( get_the_permalink() ) ) ) {
				exit;
			}
		}
	}

	public function get_post_type_archive_link( $post_type ) {
		global $wp_rewrite;

		$post_type_obj = get_post_type_object( $post_type );
		if ( ! $post_type_obj ) {
			return false;
		}

		if ( 'post' === $post_type ) {
			$show_on_front  = get_option( 'show_on_front' );
			$page_for_posts = get_option( 'page_for_posts' );

			if ( 'page' === $show_on_front && $page_for_posts ) {
				$link = get_permalink( $page_for_posts );
			} else {
				$link = get_home_url();
			}
			/** This filter is documented in wp-includes/link-template.php */
			return apply_filters( 'post_type_archive_link', $link, $post_type );
		}

		if ( ! $post_type_obj->has_archive ) {
			return false;
		}

		if ( get_option( 'permalink_structure' ) && is_array( $post_type_obj->rewrite ) ) {
			$struct = ( true === $post_type_obj->has_archive ) ? $post_type_obj->rewrite['slug'] : $post_type_obj->has_archive;
			if ( $post_type_obj->rewrite['with_front'] ) {
				$struct = $wp_rewrite->front . $struct;
			} else {
				$struct = $wp_rewrite->root . $struct;
			}
			$link = home_url( user_trailingslashit( $struct, 'post_type_archive' ) );
		} else {
			$link = home_url( '?post_type=' . $post_type );
		}

		return apply_filters( 'academy/frontend/post_type_archive_link', $link, $post_type );
	}
	public function pre_get_document_title( $title ) {
		if ( class_exists( 'RankMath' ) ) {
			$page_id = (int) get_queried_object_id();
			$course_page = (int) \Academy\Helper::get_settings( 'course_page' );
			if ( $page_id === $course_page ) {
				return;
			}
		} elseif ( get_query_var( 'name' ) && get_query_var( 'curriculum_type' ) ) {
			if ( 'lesson' === get_query_var( 'curriculum_type' ) ) {
				$lesson = helper::get_lesson_by_slug( get_query_var( 'name' ) );
				if ( $lesson ) {
					return $lesson->lesson_title;
				}
				return get_query_var( 'name' );
			}
			$post = get_page_by_path( get_query_var( 'name' ), OBJECT, self::get_post_type_name( get_query_var( 'curriculum_type' ) ) );
			if ( $post ) {
				return $post->post_title;
			}
			return get_query_var( 'name' );
		}
		return $title;
	}

	public function archive_course_document_title( $name, $post_type ) {
		if ( 'academy_courses' === $post_type ) {
			$course_page = (int) \Academy\Helper::get_settings( 'course_page' );
			return get_the_title( $course_page );
		}
		return $name;
	}
	public function get_post_type_name( $type ) {
		if ( 'quiz' === $type ) {
			return 'academy_quiz';
		} elseif ( 'booking' === $type ) {
			return 'academy_booking';
		} elseif ( 'meeting' === $type ) {
			return 'academy_meeting';
		} elseif ( 'assignment' === $type ) {
			return 'academy_assignments';
		}
		return $type;
	}
}
