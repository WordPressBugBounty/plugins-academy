<?php
namespace Academy;

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
		// A lesson/quiz/assignment that is deleted for good must also leave every
		// course curriculum that lists it, or the builder keeps a dead item.
		add_action( 'deleted_post', array( $self, 'remove_deleted_post_from_curriculums' ), 10, 2 );
		add_action( 'academy/lesson/deleted', array( $self, 'remove_deleted_lesson_from_curriculums' ) );
	}

	/**
	 * `deleted_post` handler for the post-backed curriculum item types.
	 *
	 * @param int           $post_id Deleted post ID.
	 * @param \WP_Post|null $post    Deleted post.
	 */
	public function remove_deleted_post_from_curriculums( $post_id, $post = null ) {
		$types = array(
			'academy_lessons'     => 'lesson',
			'academy_quiz'        => 'quiz',
			'academy_assignments' => 'assignment',
		);
		$post_type = $post ? $post->post_type : get_post_type( $post_id );
		if ( isset( $types[ $post_type ] ) ) {
			$this->remove_topic_from_curriculums( (int) $post_id, $types[ $post_type ] );
		}
	}

	/**
	 * Lessons kept in the custom tables aren't posts, so they announce their
	 * own deletion (see HpLesson::delete()).
	 *
	 * @param int $lesson_id Deleted lesson ID.
	 */
	public function remove_deleted_lesson_from_curriculums( $lesson_id ) {
		$this->remove_topic_from_curriculums( (int) $lesson_id, 'lesson' );
	}

	/**
	 * Drop one item (top level or inside a sub-curriculum) from every course
	 * curriculum that lists it. The module/sub-curriculum itself stays, even if
	 * it ends up empty.
	 *
	 * @param int    $topic_id Item ID.
	 * @param string $type     Item type: lesson, quiz or assignment.
	 */
	private function remove_topic_from_curriculums( $topic_id, $type ) {
		global $wpdb;

		if ( ! $topic_id ) {
			return;
		}

		// The curriculum is a serialized (or JSON) array; narrow the courses down
		// with the ways the id can appear, then confirm against the real array.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$course_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT post_id FROM {$wpdb->postmeta}
				WHERE meta_key = 'academy_course_curriculum'
				AND ( meta_value LIKE %s OR meta_value LIKE %s OR meta_value LIKE %s OR meta_value LIKE %s )",
				'%"id";i:' . $topic_id . ';%',
				'%"id";s:' . strlen( (string) $topic_id ) . ':"' . $topic_id . '";%',
				'%"id":' . $topic_id . '%',
				'%"id":"' . $topic_id . '"%'
			)
		);

		foreach ( $course_ids as $course_id ) {
			// Bulk delete sends one request per item, all at once. Each one reads,
			// edits and rewrites the same meta, so without a lock they overwrite
			// each other and the last writer brings back items another just
			// removed. The lock is per course and waits up to 10 seconds.
			$lock = 'academy_curriculum_' . (int) $course_id;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK( %s, 10 )', $lock ) );

			try {
				// Read fresh: another request may have just written it.
				wp_cache_delete( (int) $course_id, 'post_meta' );
				$this->strip_topic_from_course( (int) $course_id, $topic_id, $type );
			} finally {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK( %s )', $lock ) );
			}
		}
	}

	/**
	 * Remove one item from a single course's curriculum meta, if it is listed.
	 *
	 * @param int    $course_id Course ID.
	 * @param int    $topic_id  Item ID.
	 * @param string $type      Item type.
	 */
	private function strip_topic_from_course( $course_id, $topic_id, $type ) {
		$curriculum = get_post_meta( $course_id, 'academy_course_curriculum', true );
		if ( ! is_array( $curriculum ) ) {
			return;
		}

		$changed = false;
		$strip   = function ( array $topics ) use ( &$strip, &$changed, $topic_id, $type ) {
			$kept = array();
			foreach ( $topics as $topic ) {
				if ( is_array( $topic ) ) {
					if ( isset( $topic['id'], $topic['type'] ) && $type === $topic['type'] && (int) $topic['id'] === $topic_id ) {
						$changed = true;
						continue;
					}
					if ( ! empty( $topic['topics'] ) && is_array( $topic['topics'] ) ) {
						$topic['topics'] = $strip( $topic['topics'] );
					}
				}
				$kept[] = $topic;
			}
			return $kept;
		};

		foreach ( $curriculum as $index => $module ) {
			if ( is_array( $module ) && ! empty( $module['topics'] ) && is_array( $module['topics'] ) ) {
				$curriculum[ $index ]['topics'] = $strip( $module['topics'] );
			}
		}

		if ( $changed ) {
			update_post_meta( $course_id, 'academy_course_curriculum', $curriculum );
		}
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
