<?php
namespace  Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Miscellaneous {
	public static function init() {
		$self = new self();
		add_action( 'init', array( $self, 'add_image_sizes' ) );
		add_action( 'admin_bar_menu', array( $self, 'add_admin_bar_menu' ), 90 );
		add_action( 'rest_delete_academy_courses', array( $self, 'delete_associated_enrollment' ) );
		add_filter( 'post_type_link', array( $self, 'course_post_type_link' ), 10, 2 );
		add_action( 'before_delete_post', array( $self, 'restrict_academy_post_type_deletion' ) );
	}

	public function add_image_sizes() {
		add_image_size( 'academy_thumbnail', 1280, 855, true );
	}

	public function add_admin_bar_menu( $wp_admin_bar ) {
		$dashboard_page_id = (int) \Academy\Helper::get_settings( 'frontend_dashboard_page' );
		$title = ( current_user_can( 'manage_academy_instructor' ) ? esc_html__( 'Instructor Dashboard', 'academy' ) : esc_html__( 'Student Dashboard', 'academy' ) );
		if ( $dashboard_page_id ) {
			$wp_admin_bar->add_node(
				array(
					'id'     => 'academyfrontenddashboard',
					'title'  => $title,
					'href'   => get_the_permalink( $dashboard_page_id ),
					'parent' => 'site-name',
				)
			);
		}

		if ( is_singular( 'academy_courses' ) && current_user_can( 'manage_academy_instructor' ) ) {
			$wp_admin_bar->add_menu(
				array(
					'id'    => 'academycourses',
					'title' => esc_html__( 'Edit Course', 'academy' ),
					'href'  => esc_url( admin_url( 'admin.php?page=academy-courses&id=' . get_the_ID() . '&action=edit' ) ),
				)
			);
		}
	}

	public function delete_associated_enrollment( $post ) {
		$course_id = $post->ID;
		$user_id   = $post->post_author;
		// delete single instructor
		delete_user_meta( $user_id, 'academy_instructor_course_id', $course_id );
		// delete multi instructor data
		$instructors = \Academy\Helper::get_instructors_by_course_id( $course_id );
		if ( is_array( $instructors ) ) {
			foreach ( $instructors as $instructor ) {
				delete_user_meta( $instructor->ID, 'academy_instructor_course_id', $course_id );
			}
		}
		// delete enrolled data
		\Academy\Helper::delete_enrolled_courses( $course_id );
	}


	/**
	 * Filter to allow course_category in the permalinks for courses.
	 *
	 * @param  string  $permalink The existing permalink URL.
	 * @param  WP_Post $post WP_Post object.
	 * @return string
	 */
	public function course_post_type_link( $permalink, $post ) {
		// Abort if post is not a product.
		if ( 'academy_courses' !== $post->post_type ) {
			return $permalink;
		}

		// Abort early if the placeholder rewrite tag isn't in the generated URL.
		if ( false === strpos( $permalink, '%' ) ) {
			return $permalink;
		}

		// Get the custom taxonomy terms in use by this post.
		$terms = get_the_terms( $post->ID, 'academy_courses_category' );

		if ( ! empty( $terms ) ) {
			$terms           = wp_list_sort(
				$terms,
				array(
					'parent'  => 'DESC',
					'term_id' => 'ASC',
				)
			);
			$category_object = apply_filters( 'academy/course_post_type_link_course_category', $terms[0], $terms, $post );
			$course_category     = $category_object->slug;

			if ( $category_object->parent ) {
				$ancestors = get_ancestors( $category_object->term_id, 'course_category' );
				foreach ( $ancestors as $ancestor ) {
					$ancestor_object = get_term( $ancestor, 'course_category' );
					if ( apply_filters( 'academy/course_post_type_link_parent_category_only', false ) ) {
						$course_category = $ancestor_object->slug;
					} else {
						$course_category = $ancestor_object->slug . '/' . $course_category;
					}
				}
			}
		} else {
			// If no terms are assigned to this post, use a string instead (can't leave the placeholder there).
			$course_category = _x( 'uncategorized', 'slug', 'academy' );
		}//end if

		$find = array(
			'%year%',
			'%monthnum%',
			'%day%',
			'%hour%',
			'%minute%',
			'%second%',
			'%post_id%',
			'%category%',
			'%course_category%',
		);

		$replace = array(
			date_i18n( 'Y', strtotime( $post->post_date ) ),
			date_i18n( 'm', strtotime( $post->post_date ) ),
			date_i18n( 'd', strtotime( $post->post_date ) ),
			date_i18n( 'H', strtotime( $post->post_date ) ),
			date_i18n( 'i', strtotime( $post->post_date ) ),
			date_i18n( 's', strtotime( $post->post_date ) ),
			$post->ID,
			$course_category,
			$course_category,
		);

		$permalink = str_replace( $find, $replace, $permalink );

		return $permalink;
	}
	public function restrict_academy_post_type_deletion( $post_id ) {
		$post = get_post( $post_id );
		$post_types = [
			'academy_courses',
			'academy_quiz',
			'academy_webhook',
			'academy_announcement',
		];
		if ( in_array( $post->post_type, $post_types, true ) && get_current_user_id() !== (int) $post->post_author && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to delete this post.', 'academy' ) );
		}
	}
}
