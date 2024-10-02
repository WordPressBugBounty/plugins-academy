<?php
namespace AcademyMigrationTool\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Migration {

	public function migrate_course_complete_data( $courses, $course_id ) {
		global $wpdb;
		foreach ( $courses as $course ) {
			$user_id = $course->user_id;
			$is_enrolled = \Academy\Helper::is_enrolled( $course_id, $user_id );
			if ( ! $is_enrolled ) {
				$date = gmdate( 'Y-m-d H:i:s', \Academy\Helper::get_time() );
				do {
					$hash    = substr( md5( wp_generate_password( 32 ) . $date . $course_id . $user_id ), 0, 16 );
					$hasHash = (int) $wpdb->get_var(
						$wpdb->prepare(
							"SELECT COUNT(comment_ID) from {$wpdb->comments} 
						WHERE comment_agent = 'academy' AND comment_type = 'course_completed' AND comment_content = %s ",
							$hash
						)
					);

				} while ( $hasHash > 0 );

				$data = array(
					'comment_post_ID'  => $course_id,
					'comment_author'   => $user_id,
					'comment_date'     => $date,
					'comment_date_gmt' => get_gmt_from_date( $date ),
					'comment_content'  => $hash,
					'comment_approved' => 'approved',
					'comment_agent'    => 'academy',
					'comment_type'     => 'course_completed',
					'user_id'          => $user_id,
				);
				$wpdb->insert( $wpdb->comments, $data );
			}//end if
		}//end foreach
	}

	public function enrollment_migration( $course_id, $enrollments ) {
		foreach ( $enrollments as $enrollment ) {
			$is_enrolled = \Academy\Helper::is_enrolled( $course_id, $enrollment->user_id );
			$time = strtotime( $enrollment->order_post_date );
			if ( ! $is_enrolled ) {
				$post_title = __( 'Course Enrolled', 'academy' ) . ' ' .
				gmdate( get_option( 'date_format' ), $time ) . ' @ ' .
				gmdate( get_option( 'time_format' ), $time );
				$enroll_data = array(
					'post_title' => $post_title,
					'post_type' => 'academy_enrolled',
					'post_status' => 'completed',
					'post_parent' => $course_id,
					'post_author' => $enrollment->user_id,
				);
				$enrolled = wp_insert_post( $enroll_data );
				if ( $enrolled ) {
					update_user_meta( $enrollment->user_id, 'is_academy_student', $time );
				}
			}
		}
	}

	public function woo_create_or_update_product( $args ) {
		$course_id = sanitize_text_field( $args['course_id'] );

		// that's CRUD object
		$product = new \WC_Product_Simple();
		$product->set_name( $args['course_title'] );
		$product->set_slug( $args['course_slug'] );
		$product->set_regular_price( $args['regular_price'] );
		if ( $args['sale_price'] ) {
			$product->set_sale_price( $args['sale_price'] );
		}
		$product_id = $product->save();

		if ( $product_id ) {
			update_post_meta( $product_id, '_academy_product', 'yes' );
		}

		if ( $course_id ) {
			update_post_meta( $course_id, 'academy_course_product_id', $product_id );
		}
		return $product_id;
	}

	public function academy_course_prerequisite( $course_ids ) {
		global $wpdb;
		$course_prerequisites = [];
		if ( is_array( $course_ids ) ) {
			foreach ( $course_ids as $course_id ) {
				$titles = $wpdb->get_results( $wpdb->prepare( "SELECT post_title FROM {$wpdb->posts} WHERE ID = %d", $course_id ) );
				foreach ( $titles as $title ) {
						$course_prerequisites[] = array(
							'label' => $title->post_title,
							'value' => $course_id,
						);
				}
			}
		}
		return $course_prerequisites;
	}

	public function migrate_taxonomy_tag( $source_taxonomy, $target_taxonomy ) {
		$terms = get_terms(array(
			'taxonomy' => $source_taxonomy,
			'hide_empty' => false,
		));
		if ( is_array( $terms ) && ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$term_name = $term->name;
				$term_slug = $term->slug;

				$existing_term = term_exists( $term_name, $target_taxonomy );

				if ( ! $existing_term ) {
					wp_insert_term($term_name, $target_taxonomy, array(
						'slug' => $term_slug,
					));
				}

				$target_term_id = term_exists( $term_name, $target_taxonomy );

				// Reassign posts from the source term to the target term
				if ( $target_term_id && ! is_wp_error( $target_term_id ) ) {
					$posts = get_posts(array(
						'post_type' => 'academy_courses',
						'taxonomy' => $source_taxonomy,
						'term' => $term_slug,
						'posts_per_page' => -1,
					));

					if ( $posts ) {
						foreach ( $posts as $post ) {
							// Assign the post to the target term
							wp_set_post_terms( $post->ID, $term_name, $target_taxonomy, true );
							// Remove the term from the source taxonomy
							wp_remove_object_terms( $post->ID, $term_slug, $source_taxonomy );
						}
					}
				}
			}//end foreach
		}//end if
	}

	public function migrate_taxonomy_category( $source_taxonomy, $target_taxonomy ) {
		// Get all terms from the source taxonomy
		$terms = get_terms(array(
			'taxonomy' => $source_taxonomy,
			'hide_empty' => false, // Include empty terms
		));

		if ( is_array( $terms ) && ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				// Get the term's name and slug
				$term_name = $term->name;
				$term_slug = $term->slug;

				// Check if the term already exists in the target taxonomy
				$existing_term = term_exists( $term_name, $target_taxonomy );

				// If the term doesn't exist in the target taxonomy, create it
				if ( ! $existing_term ) {
					$parent_term_id = 0; // Initialize as top-level term

					// Check if the term has a parent in the source taxonomy
					if ( $term->parent > 0 ) {
						// Get the parent term in the target taxonomy
						$parent_term = term_exists( get_term( $term->parent )->name, $target_taxonomy );
						if ( $parent_term && ! is_wp_error( $parent_term ) ) {
							$parent_term_id = $parent_term['term_id'];
						}
					}

					// Create the term in the target taxonomy with the correct parent
					wp_insert_term($term_name, $target_taxonomy, array(
						'slug' => $term_slug,
						'parent' => $parent_term_id,
					));
				}

				// Get the term ID of the newly created or existing term in the target taxonomy
				$target_term_id = term_exists( $term_name, $target_taxonomy );

				// Reassign posts from the source term to the target term
				if ( $target_term_id && ! is_wp_error( $target_term_id ) ) {
					$posts = get_posts(array(
						'post_type' => 'academy_courses', // Replace with your post type
						'taxonomy' => $source_taxonomy,
						'term' => $term_slug,
						'posts_per_page' => -1,
					));

					if ( $posts ) {
						foreach ( $posts as $post ) {
							// Assign the post to the target term
							wp_set_post_terms( $post->ID, $target_term_id['term_id'], $target_taxonomy, true );

							// Remove the term from the source taxonomy
							wp_remove_object_terms( $post->ID, $term_slug, $source_taxonomy );
						}
					}
				}
			}//end foreach
		}//end if
	}
}
