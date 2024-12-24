<?php
namespace  Academy\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy;
use Academy\Helper;
use Academy\Classes\Sanitizer;
use Academy\Classes\AbstractAjaxHandler;

class Course extends AbstractAjaxHandler {
	public function __construct() {
		$this->actions = array(
			'get_course_slug' => array(
				'callback' => array( $this, 'get_course_slug' ),
				'capability'    => 'manage_academy_instructor'
			),
			'fetch_course_category' => array(
				'callback' => array( $this, 'fetch_course_category' ),
				'capability'    => 'manage_academy_instructor'
			),
			'render_enrolled_courses' => array(
				'callback' => array( $this, 'render_enrolled_courses' ),
				'capability'    => 'read'
			),
			'render_pending_enrolled_courses' => array(
				'callback' => array( $this, 'render_enrolled_courses' ),
				'capability'    => 'read'
			),
			'render_wishlist_courses' => array(
				'callback' => array( $this, 'render_wishlist_courses' ),
				'capability'    => 'read'
			),
			'course_add_to_wishlist' => array(
				'callback' => array( $this, 'course_add_to_wishlist' ),
				'allow_visitor_action'    => true
			),
			'archive_course_filter' => array(
				'callback' => array( $this, 'archive_course_filter' ),
				'allow_visitor_action'    => true
			),
			'course_add_to_favorite' => array(
				'callback' => array( $this, 'course_add_to_favorite' ),
				'capability'    => 'read'
			),
			'get_my_courses' => array(
				'callback' => array( $this, 'get_my_courses' ),
				'capability'    => 'manage_academy_instructor'
			),
			'enroll_course' => array(
				'callback' => array( $this, 'enroll_course' ),
				'allow_visitor_action'    => true
			),
			'complete_course' => array(
				'callback' => array( $this, 'complete_course' ),
				'capability'    => 'read'
			),
			'add_course_review' => array(
				'callback' => array( $this, 'add_course_review' ),
				'capability'    => 'read'
			),
			'get_course_details' => array(
				'callback' => array( $this, 'get_course_details' ),
				'capability'    => 'read'
			),
			'import_course' => array(
				'callback'  => array( $this, 'import_all_courses' ),
				'capability' => 'manage_academy_instructor',
			)
		);
	}

	public function get_course_slug( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'ID' => 'integer',
			'new_title' => 'string',
			'new_slug' => 'string',
		], $payload_data );

		wp_send_json_success( Helper::get_sample_permalink_args( $payload['ID'], $payload['new_title'], $payload['new_slug'] ) );
	}

	public function fetch_course_category( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'postId' => 'integer',
			'keyword' => 'string',
			'type' => 'string',
		], $payload_data );

		$catId   = ( isset( $payload['postId'] ) ? $payload['postId'] : 0 );
		$keyword = ( isset( $payload['keyword'] ) ? $payload['keyword'] : '' );
		$type    = ( isset( $payload['type'] ) ? $payload['type'] : 'single' );

		$categories = [];
		if ( ! empty( $keyword ) ) {
			$categories = get_term_by( 'name', $keyword, 'academy_courses_category' );
		} elseif ( $catId && 'single' === $type ) {
			$categories = get_term( $catId, 'academy_courses_category' );
		} else {
			$categories = get_terms( array(
				'taxonomy'   => 'academy_courses_category',
				'hide_empty' => false,
			) );
		}
		$results = [];
		if ( is_array( $categories ) && count( $categories ) ) {
			foreach ( $categories as $category ) {
				$results[] = array(
					'label' => $category->name,
					'value' => $category->term_id,
				);
			}
		}

		wp_send_json_success( $results );
	}

	public function render_enrolled_courses( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'request_type' => 'string',
		], $payload_data );

		$request_type = ( isset( $payload['request_type'] ) ? $payload['request_type'] : 'enrolled' );
		$user_id = get_current_user_id();
		$enrolled_course_ids = \Academy\Helper::get_enrolled_courses_ids_by_user( $user_id );
		$complete_course_ids = \Academy\Helper::get_complete_courses_ids_by_user( $user_id );
		$post_in = $enrolled_course_ids;
		if ( 'complete' === $request_type ) {
			$post_in = $complete_course_ids;
		} elseif ( 'active' === $request_type ) {
			$post_in      = array_diff( $enrolled_course_ids, $complete_course_ids );
		}

		$course_args = array(
			'post_type'      => 'academy_courses',
			'post_status'    => 'publish',
			'post__in'       => $post_in,
			'posts_per_page' => -1,
		);
		$courses = new \WP_Query( apply_filters( 'academy/enrolled_courses_args', $course_args ) );
		ob_start();
		?>
		<div class="academy-row"> 
			<?php
			if ( count( $post_in ) && $courses && $courses->have_posts() ) :
				while ( $courses->have_posts() ) :
					$courses->the_post();
					$ID                      = get_the_ID();
					$rating                  = \Academy\Helper::get_course_rating( $ID );
					$total_topics           = \Academy\Helper::get_total_number_of_course_topics( $ID );
					$total_completed_topics = \Academy\Helper::get_total_number_of_completed_course_topics_by_course_and_student_id( $ID );
					$percentage              = \Academy\Helper::calculate_percentage( $total_topics, $total_completed_topics );
					?>
			<div class="academy-col-xl-3 academy-col-lg-4 academy-col-md-6 academy-col-sm-12">
				<div class="academy-mycourse academy-mycourse-<?php the_ID(); ?>">
					<div class="academy-mycourse__thumbnail">
						<a href="<?php echo esc_url( get_the_permalink() ); ?>">
							<img class="academy-course__thumbnail-image" src="<?php echo esc_url( Academy\Helper::get_the_course_thumbnail_url( 'academy_thumbnail' ) ); ?>" alt="<?php esc_html_e( 'thumbnail', 'academy' ); ?>">
						</a>
					</div>
					<div class="academy-mycourse__content">
						<div class="academy-course__rating">
								<?php
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									echo \Academy\Helper::star_rating_generator( $rating->rating_avg );
								?>
								<?php echo esc_html( $rating->rating_avg ); ?> <span
								class="academy-course__rating-count"><?php echo esc_html( '(' . $rating->rating_count . ')' ); ?></span>
						</div>
						<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<div class="academy-course__meta">
							<div class="academy-course__meta-item"><?php esc_html_e( 'Total Topics:', 'academy' ); ?><span><?php echo esc_html( $total_topics ); ?></span></div>
							<div class="academy-course__meta-item"><?php esc_html_e( 'Completed Topics:', 'academy' ); ?><span><?php echo esc_html( $total_topics . '/' . $total_completed_topics ); ?></span>
							</div>
						</div>
						<div class="academy-progress-wrap">
							<div class="academy-progress">
								<div class="academy-progress-bar"
									style="width: <?php echo esc_attr( $percentage ) . '%'; ?>;">
								</div>
							</div>
							<span class="academy-progress-wrap__percent"><?php echo esc_html( $percentage ) . esc_html__( '%  Complete', 'academy' ); ?></span>
						</div>
						<?php
							\Academy\Helper::get_template( 'single-course/enroll/continue.php' );
						?>
						<div class="academy-widget-enroll__view_details" data-id="<?php echo esc_attr( get_the_ID() ); ?>">
							<button class="academy-btn academy-btn--bg-purple">
								<?php
								esc_html_e( 'View Details', 'academy' );
								?>
							</button>
						</div>
					</div>
				</div>
			</div>
					<?php
				endwhile;
				?>
		</div>
				<?php

				wp_reset_query(); else : ?>
				<div class='academy-mycourse'>
					<h3 class='academy-not-found'>
						<?php
						if ( 'active' === $request_type ) {
							esc_html_e( 'You have no active courses.', 'academy' );
						} elseif ( 'complete' === $request_type ) {
							esc_html_e( 'You have no complete courses.', 'academy' );
						} else {
							esc_html_e( 'You are not enrolled in any course yet.', 'academy' );
						}
						?>
					</h3>
				</div>
					<?php
		endif;
				$output = ob_get_clean();
				wp_send_json_success( array(
					'html' => $output
				) );
	}


	public function render_pending_enrolled_courses() {
		$user_id = get_current_user_id();
		$pending_enrolled_course_ids = \Academy\Helper::get_pending_enrolled_courses_ids_by_user( $user_id );
		if ( ! count( $pending_enrolled_course_ids ) ) {
			wp_send_json_success( [] );
		}
		$course_args = array(
			'post_type'      => 'academy_courses',
			'post_status'    => 'publish',
			'post__in'       => $pending_enrolled_course_ids,
			'posts_per_page' => -1,
		);
		$courses = new \WP_Query( apply_filters( 'academy/pending_enrolled_course_args', $course_args ) );
		$response = [];
		if ( count( $pending_enrolled_course_ids ) && $courses->have_posts() ) {
			while ( $courses->have_posts() ) :
				$courses->the_post();
				$response[] = array(
					'ID' => get_the_ID(),
					'permalink' => get_the_permalink(),
					'title' => get_the_title(),
				);
			endwhile;
			wp_reset_query();
		}
		wp_send_json_success( $response );
	}

	public function render_wishlist_courses() {

		$courses = \Academy\Helper::get_wishlist_courses_by_user( get_current_user_id(), array( 'private', 'publish' ) );

		ob_start();
		?>

			<div class="academy-courses">
				<div class="academy-row">
					<?php
					if ( $courses && $courses->have_posts() ) :
						while ( $courses->have_posts() ) :
							$courses->the_post();
							\Academy\Helper::get_template_part( 'content', 'course' );
						endwhile;
						wp_reset_query();
					else :
						?>
					<div class='academy-mycourse'>
						<h3 class='academy-not-found'><?php esc_html_e( 'Your wishlist is empty!', 'academy' ); ?></h3>
					</div>
						<?php
						endif;
					?>
				</div>
			</div>
		<?php
		$output = ob_get_clean();
		wp_send_json_success( $output );
		wp_die();
	}


	public function course_add_to_wishlist( $payload_data ) {
		if ( ! is_user_logged_in() ) {
			if ( \Academy\Helper::get_settings( 'is_enabled_academy_login', true ) ) {
				ob_start();
				echo do_shortcode( '[academy_login_form form_title="' . esc_html__( 'Hi, Welcome back!', 'academy' ) . '" show_logged_in_message="false"]' );
				$markup = ob_get_clean();
				wp_send_json_error( array( 'markup' => $markup ) );
			}
			wp_send_json_error( array( 'redirect_to' => wp_login_url( wp_get_referer() ) ) );
		}

		global $wpdb;
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
		], $payload_data );

		$course_id          = $payload['course_id'];
		$user_id            = get_current_user_id();
		$is_already_in_list = $wpdb->get_row( $wpdb->prepare( "SELECT * from {$wpdb->usermeta} WHERE user_id = %d AND meta_key = 'academy_course_wishlist' AND meta_value = %d;", $user_id, $course_id ) );
		if ( $is_already_in_list ) {
			$wpdb->delete(
				$wpdb->usermeta,
				array(
					'user_id'    => $user_id,
					'meta_key'   => 'academy_course_wishlist',
					'meta_value' => $course_id,
				)
			);
			wp_send_json_success( array( 'is_added' => false ) );
		}
		add_user_meta( $user_id, 'academy_course_wishlist', $course_id );
		wp_send_json_success( array( 'is_added' => true ) );
	}

	public function course_add_to_favorite( $payload_data ) {
		global $wpdb;
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
		], $payload_data );

		$course_id          = $payload['course_id'];
		$user_id            = get_current_user_id();
		$is_already_in_list = $wpdb->get_row( $wpdb->prepare( "SELECT * from {$wpdb->usermeta} WHERE user_id = %d AND meta_key = 'academy_course_favorite' AND meta_value = %d;", $user_id, $course_id ) );
		if ( $is_already_in_list ) {
			$wpdb->delete(
				$wpdb->usermeta,
				array(
					'user_id'    => $user_id,
					'meta_key'   => 'academy_course_favorite',
					'meta_value' => $course_id,
				)
			);
			wp_send_json_success( array( 'is_added' => false ) );
		}
		add_user_meta( $user_id, 'academy_course_favorite', $course_id );
		wp_send_json_success( array( 'is_added' => true ) );
	}


	public function archive_course_filter( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'search' => 'string',
			'category' => 'array',
			'cat_not_in' => 'array',
			'tags' => 'array',
			'tag_not_in' => 'array',
			'levels' => 'array',
			'type' => 'array',
			'orderby' => 'string',
			'paged' => 'integer',
			'per_row' => 'integer',
			'per_page' => 'integer',
			'ids'   => 'string',
			'count' => 'integer',
			'exclude_ids' => 'array',
		], $payload_data );

		$search      = ( isset( $payload['search'] ) ? $payload['search'] : '' );
		$category    = ( isset( $payload['category'] ) ? $payload['category'] : [] );
		$cat_not_in  = ( isset( $payload['cat_not_in'] ) ? $payload['cat_not_in'] : [] );
		$tags        = ( isset( $payload['tags'] ) ? $payload['tags'] : [] );
		$tag_not_in  = ( isset( $payload['tag_not_in'] ) ? $payload['tag_not_in'] : [] );
		$levels      = ( isset( $payload['levels'] ) ? $payload['levels'] : [] );
		$type        = ( isset( $payload['type'] ) ? $payload['type'] : [] );
		$orderby     = ( isset( $payload['orderby'] ) ? $payload['orderby'] : 'DESC' );
		$paged       = ( isset( $payload['paged'] ) ) ? $payload['paged'] : 1;
		$ids         = ( isset( $payload['ids'] ) ? $payload['ids'] : [] );
		$exclude_ids = ( isset( $payload['exclude_ids'] ) ? $payload['exclude_ids'] : [] );
		$count       = ( isset( $payload['count'] ) ? $payload['count'] : 0 );
		$per_row     = ( isset( $payload['per_row'] ) ? array(
			'desktop' => $payload['per_row'],
			'tablet'  => 2,
			'mobile'  => 1
		) : Academy\Helper::get_settings( 'course_archive_courses_per_row', array(
			'desktop' => 3,
			'tablet'  => 2,
			'mobile'  => 1
		) ) );
		$per_page = ( isset( $payload['per_page'] ) ? $payload['per_page'] : (int) \Academy\Helper::get_settings( 'course_archive_courses_per_page', 12 ) );
		if ( $count ) {
			$per_page = $count;
		}
		if ( $cat_not_in || $tag_not_in ) {
			$category = array_diff( $category, $cat_not_in );
			$tags = array_diff( $tags, $tag_not_in );
		}
		$args = \Academy\Helper::prepare_course_search_query_args(
			[
				'search'         => $search,
				'category'       => $category,
				'tags'           => $tags,
				'levels'         => $levels,
				'type'           => $type,
				'paged'          => $paged,
				'orderby'        => $orderby,
				'posts_per_page' => $per_page,
			]
		);

		if ( $ids || $exclude_ids ) {
			$page_num = $paged - 1;
			$ids = $ids ? (array) explode( ',', $ids ) : [];
			$exclude_ids = $exclude_ids ? (array) explode( ',', $exclude_ids ) : [];
			$ids = array_diff( $ids, $exclude_ids );
			$found_posts = (int) count( $ids );
			$count = $count ?? 0;
			if ( $count && $found_posts > $count ) {
				$ids = array_slice( $ids, - ( $found_posts - ( $count * $page_num ) ) );
			}
			$args['post_type'] = [
				'academy_courses'
			];
			$args['post__in'] = $ids;
			$args['paged'] = $page_num;
		}
		$grid_class = \Academy\Helper::get_responsive_column( $per_row );
		// phpcs:ignore WordPress.WP.DiscouragedFunctions.query_posts_query_posts
		wp_reset_query();
		wp_reset_postdata();
		$courses_query = new \WP_Query( apply_filters( 'academy_courses_filter_args', $args ) );

		if ( $found_posts ) {
			$courses_query->max_num_pages = ceil( $found_posts / $count );
		}
		ob_start();
		?>
		<div class="academy-row">
			<?php
			if ( $courses_query->have_posts() ) {
				// Load posts loop.
				while ( $courses_query->have_posts() ) {
					$courses_query->the_post();
					/**
					 * Hook: academy/templates/course_loop.
					 */
					do_action( 'academy/templates/course_loop' );
					\Academy\Helper::get_template( 'content-course.php', array( 'grid_class' => $grid_class ) );
				}
				\Academy\Helper::get_template( 'archive/pagination.php', array(
					'paged' => $paged,
					'max_num_pages' => $courses_query->max_num_pages,
				) );
				wp_reset_query();
				wp_reset_postdata();
			} else {
				\Academy\Helper::get_template( 'archive/course-none.php' );
			}
			?>
		</div>
		<?php
		$markup = ob_get_clean();
		wp_send_json_success(
			[
				'markup'      => apply_filters( 'academy/course_filter_markup', $markup ),
				'found_posts' => $courses_query->found_posts,
			]
		);
	}


	public function get_my_courses() {
		$response = [];
		$course_args = array(
			'post_type'         => 'academy_courses',
			'post_status'       => 'publish',
			'author'            => get_current_user_id(),
			'posts_per_page'    => -1,
		);
		$courses = new \WP_Query( apply_filters( 'academy/my_courses_args', $course_args ) );
		if ( $courses->have_posts() ) :
			while ( $courses->have_posts() ) :
				$courses->the_post();
				$ID                      = get_the_ID();
				$rating                  = \Academy\Helper::get_course_rating( $ID );
				$rating_markup = \Academy\Helper::star_rating_generator( $rating->rating_avg );
				$total_enrolled = \Academy\Helper::count_course_enrolled( $ID );
				$response[] = array(
					'title'             => get_the_title( $ID ),
					'permalink'         => get_the_permalink( $ID ),
					'rating'            => $rating,
					'rating_markup'     => $rating_markup,
					'total_enrolled'    => $total_enrolled
				);
			endwhile;
			wp_reset_query();
		endif;
		wp_send_json_success( $response );
	}


	public function enroll_course( $payload_data ) {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'is_required_logged_in' => true ) );
		}

		$user_id = get_current_user_id();
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
		], $payload_data );

		$course_id = (int) $payload['course_id'];
		$course_type = get_post_meta( $course_id, 'academy_course_type', true );
		$course_type = apply_filters( 'academy/before_enroll_course_type', $course_type, $course_id );
		if ( 'free' === $course_type || 'public' === $course_type ) {
			$is_enrolled = \Academy\Helper::do_enroll( $course_id, $user_id );
		}

		if ( $is_enrolled ) {
			wp_send_json_success( __( 'Successfully Enrolled.', 'academy' ) );
		}
		wp_send_json_error( __( 'Failed to enrolled course.', 'academy' ) );
	}

	public function complete_course( $payload_data ) {
		$user_id = get_current_user_id();
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
		], $payload_data );
		$course_id = $payload['course_id'];
		$has_incomplete_topic = false;
		$curriculum_lists = \Academy\Helper::get_course_curriculum( $course_id );
		foreach ( $curriculum_lists as $curriculum_list ) {
			if ( is_array( $curriculum_list['topics'] ) ) {
				foreach ( $curriculum_list['topics'] as $topic ) {
					if ( empty( $topic['is_completed'] ) && 'sub-curriculum' !== $topic['type'] ) {
						$has_incomplete_topic = true;
						break;
					}
					if ( isset( $topic['topics'] ) && is_array( $topic['topics'] ) ) {
						foreach ( $topic['topics'] as $child_topic ) {
							if ( empty( $child_topic['is_completed'] ) ) {
								$has_incomplete_topic = true;
								break;
							}
						}
					}
				}
			}
			// found incomplete topic then break loop
			if ( $has_incomplete_topic ) {
				break;
			}
		}//end foreach

		if ( $has_incomplete_topic ) {
			wp_send_json_error( __( 'To complete this course, please make sure that you have finished all the topics.', 'academy' ) );
		}

		do_action( 'academy/admin/course_complete_before', $course_id );
		global $wpdb;

		$completed = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(comment_ID) from {$wpdb->comments} 
				WHERE comment_agent = 'academy' AND comment_type = 'course_completed' 
				AND comment_post_ID = %d AND user_id = %d",
				$course_id, $user_id
			),
		);

		if ( $completed > 0 ) {
			wp_send_json_error( __( 'You have already completed this course.', 'academy' ) );
		}

		$date = gmdate( 'Y-m-d H:i:s', \Academy\Helper::get_time() );

		// hash is unique.
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
		$is_complete = $wpdb->insert( $wpdb->comments, $data );

		do_action( 'academy/admin/course_complete_after', $course_id, $user_id );

		if ( $is_complete ) {
			wp_send_json_success( __( 'Successfully Completed.', 'academy' ) );
		}
		wp_send_json_error( __( 'Failed, try again.', 'academy' ) );
	}

	public function add_course_review( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
			'rating' => 'integer',
			'review' => 'post',
		], $payload_data );
		$course_id = $payload['course_id'];
		$user_id = get_current_user_id();
		$current_user = get_userdata( $user_id );

		if ( ! \Academy\Helper::is_completed_course( $course_id, $user_id ) ) {
			wp_send_json_error( __( 'Sorry, you have to complete the course first.', 'academy' ) );
		}

		$rating = (int) $payload['rating'];
		$review = $payload['review'];

		$data = array(
			'comment_post_ID'       => $course_id,
			'comment_content'       => $review,
			'user_id'               => $current_user->ID,
			'comment_author'        => $current_user->user_login,
			'comment_author_email'  => $current_user->user_email,
			'comment_author_url'    => $current_user->user_url,
			'comment_type'          => 'academy_courses',
			'comment_approved'      => '1',
			'comment_meta'          => array(
				'academy_rating'    => $rating,
			)
		);

		// get all review of current user
		$existing_reviews = get_comments(array(
			'comment_type' => 'academy_courses',
			'post_id' => $course_id,
			'user_id' => $current_user->ID,
		));

		// if the review exist then update it
		if ( count( $existing_reviews ) ) {
			$existing_review = current( $existing_reviews );

			$data['comment_ID'] = $existing_review->comment_ID;

			$is_update = wp_update_comment( $data );

			if ( $is_update ) {
				wp_send_json_success(array(
					'message'       => __( 'Successfully Updated Review.', 'academy' ),
					'redirect_url' => get_the_permalink( $course_id ),
				));
			}
		}

		// insert the review
		$comment_id = wp_insert_comment( $data );
		if ( $comment_id ) {
			wp_send_json_success(array(
				'message'       => __( 'Successfully Added Review.', 'academy' ),
				'redirect_url' => get_the_permalink( $course_id ),
			));
		}
		wp_send_json_error( __( 'Sorry, Failed to add review.', 'academy' ) );
	}

	public function get_course_details( $payload_data ) {
		$student_id = get_current_user_id();
		$payload = Sanitizer::sanitize_payload([
			'courseID' => 'integer',
		], $payload_data );
		$course_id = isset( $payload['courseID'] ) ? $payload['courseID'] : 0;
		$is_administrator = current_user_can( 'administrator' );
		$is_instructor    = \Academy\Helper::is_instructor_of_this_course( $student_id, $course_id );
		$enrolled         = \Academy\Helper::is_enrolled( $course_id, $student_id );
		$response = [];
		if ( $is_administrator || $is_instructor || $enrolled ) {
			$analytics_data = \Academy\Helper::prepare_analytics_for_user( $student_id, $course_id );
			$analytics_data['title'] = get_the_title( $course_id );
			$analytics_data['course_link'] = get_post_permalink( $course_id );
			$response['enrolled_info'][] = $analytics_data;
		}
		wp_send_json_success( $response );
	}

	public function import_all_courses() {
		check_admin_referer( 'academy_nonce', 'security' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}

		if ( ! isset( $_FILES['upload_file'] ) ) {
			wp_send_json_error( __( 'Upload File is empty.', 'academy' ) );
		}

		$file = $_FILES['upload_file'];
		if ( 'csv' !== pathinfo( $file['name'] )['extension'] ) {
			wp_send_json_error( __( 'Wrong File Format! Please import csv file.', 'academy' ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		$file_open = fopen( $file['tmp_name'], 'r' );
		if ( false !== $file_open ) {
			$has_course = false;
			$has_course_meta = false;
			$has_quiz = false;
			$has_question = false;
			$has_answer = false;
			$has_lesson = false;
			$has_assignment = false;
			$course_header = [];
			$course_meta_header = [];
			$lesson_header = [];
			$assignment_header = [];
			$quiz_header = [];
			$new_course_id = 0;
			$new_quiz_id = 0;
			$new_question_id = 0;
			$old_course_id = 0;
			$course_ids = [];
			$new_curr_item = [];
			$response = [];
			// phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			while ( false !== ( $item = fgetcsv( $file_open ) ) ) {
				if ( in_array( 'post_title', $item, true ) ) {
					$course_header = array_map( 'strtolower', $item );
					$has_course = true;
					continue;
				} elseif ( in_array( 'course_expire_enrollment', $item, true ) ) {
					$course_meta_header = array_map( 'strtolower', $item );
					$has_course_meta = true;
					continue;
				} elseif ( in_array( 'lesson_title', $item, true ) ) {
					$lesson_header = array_map( 'strtolower', $item );
					$has_lesson = true;
					continue;
				} elseif ( in_array( 'quiz_title', $item, true ) ) {
					$quiz_header = array_map( 'strtolower', $item );
					$has_quiz = true;
					continue;
				} elseif ( in_array( 'question_title', $item, true ) ) {
					$question_header = array_map( 'strtolower', $item );
					$has_question = true;
					$has_answer = false;
					continue;
				} elseif ( in_array( 'answer_title', $item, true ) ) {
					$answer_header = array_map( 'strtolower', $item );
					$has_answer = true;
					continue;
				} elseif ( in_array( 'assignment_title', $item, true ) ) {
					$assignment_header = array_map( 'strtolower', $item );
					$has_assignment = true;
					continue;
				}//end if

				if ( $has_course ) {
					$has_course = false;
					$course_item = array_combine( $course_header, $item );
					if ( empty( $course_item['post_title'] ) ) {
						$response[] = __( 'Empty Course data', 'academy' );
						continue;
					}

					$slug_exist = \Academy\Helper::is_course_slug_exist( $course_item['post_title'] );
					if ( $slug_exist ) {
						$course = \Academy\Helper::get_page_by_title( $course_item['post_title'], 'academy_courses' );
						$old_course_id = $course->ID;
						$course_ids[] = $old_course_id;
						$response[] = __( 'Failed, Already Inserted the Course', 'academy' ) . ' - ' . $course_item['post_title'];
						continue;
					}
					$new_course_id = $this->course_data_set( $course_item );
					$response[] = $new_course_id ? __( 'Successfully Inserted the Course - ', 'academy' ) . $course_item['post_title'] : __( 'Sorry, Failed to Inserted the Course - ', 'academy' ) . $course_item['post_title'];
					$course_ids[] = $new_course_id;
				} elseif ( $has_course_meta && ( $new_course_id || $old_course_id ) ) {
					$has_course_meta = false;
					$course_meta_item = array_combine( $course_meta_header, $item );
					$new_curr_item[] = [
						'course_id' => $new_course_id ?? $old_course_id,
						'curriculum' => json_decode( $course_meta_item['course_curriculum'] )
					];
					if ( $new_course_id ) {
						$this->insert_course_meta_value( $course_meta_item, $new_course_id );
					}
				} elseif ( $has_lesson ) {
					$has_lesson = false;
					$lesson_item = array_combine( $lesson_header, $item );
					$exist_lesson = \Academy\Helper::get_lesson_by_title( $lesson_item['lesson_title'] );
					if ( ! $exist_lesson ) {
						$new_lesson_id = $this->insert_lesson_data( $lesson_item );
					}
					$response[] = ! empty( $new_lesson_id ) ? __( 'Successfully Inserted the Lesson - ', 'academy' ) . $lesson_item['lesson_title'] : __( 'Sorry, Already have the Lesson - ', 'academy' ) . $lesson_item['lesson_title'];
				} elseif ( $has_quiz ) {
					$has_quiz = false;
					if ( ! \Academy\Helper::is_active_academy_pro() ) {
						continue;
					}
					$quiz_item = array_combine( $quiz_header, $item );
					$exist_quiz = \Academy\Helper::get_page_by_title( $quiz_item['quiz_title'], 'academy_quiz' );
					if ( ! $exist_quiz ) {
						$new_quiz_id = apply_filters( 'academy_pro/export-import/insert_quiz_data', $quiz_item );
					}
					$response[] = ! empty( $new_quiz_id ) ? __( 'Successfully Inserted the Quiz - ', 'academy' ) . $quiz_item['quiz_title'] : __( 'Sorry, Already have the Quiz - ', 'academy' ) . $quiz_item['quiz_title'];
				} elseif ( $has_question && $new_quiz_id ) {
					$has_question = false;
					$question_item = array_combine( $question_header, $item );
					$new_question_id = apply_filters( 'academy_pro/export-import/insert_question_data', $question_item, $new_quiz_id );
					$response[] = $new_question_id ? __( 'Successfully Inserted the Question - ', 'academy' ) . $question_item['question_title'] : __( 'Sorry, Already have the Question - ', 'academy' ) . $question_item['question_title'];
				} elseif ( $has_answer && $new_quiz_id && $new_question_id ) {
					$answer_item = array_combine( $answer_header, $item );
					apply_filters( 'academy_pro/export-import/insert_answer_data', $answer_item, $new_quiz_id, $new_question_id );
				} elseif ( $has_assignment ) {
					$has_assignment = false;
					if ( ! \Academy\Helper::is_active_academy_pro() ) {
						continue;
					}
					$assignment_item = array_combine( $assignment_header, $item );
					$exist_assignment = \Academy\Helper::get_page_by_title( $assignment_item['assignment_title'], 'academy_assignments' );
					if ( ! $exist_assignment ) {
						$new_assignment_id = apply_filters( 'academy_pro/export-import/insert_assignment_data', $assignment_item );
					}
					$response[] = ! empty( $new_assignment_id ) ? __( 'Successfully Inserted the Assignment - ', 'academy' ) . $assignment_item['assignment_title'] : __( 'Sorry, Already have the Assignment - ', 'academy' ) . $assignment_item['assignment_title'];
				}//end if
			}//end while

			if ( count( $new_curr_item ) ) {
				foreach ( $new_curr_item as $item ) {
					$this->update_course_curriculum( $item['curriculum'], $item['course_id'] );
				}
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
			fclose( $file_open );

			wp_send_json_success( $response );
		}//end if
		wp_send_json_error( $response );
	}

	public function course_data_set( $course_item ) {
		if ( empty( $course_item ) ) {
			return false;
		}

		$allowed_post_fields = [
			'post_title'      => 'sanitize_text_field',
			'post_author'     => 'sanitize_text_field',
			'post_date'       => 'sanitize_text_field',
			'post_content'    => 'sanitize_textarea_field',
			'post_excerpt'    => 'sanitize_text_field',
			'post_status'     => 'sanitize_key',
			'comment_status'  => 'sanitize_key',
			'post_name'       => 'sanitize_title',
			'post_parent'     => 'absint',
			'comment_count'   => 'absint'
		];

		$post_data = [];
		foreach ( $allowed_post_fields as $key => $sanitizer ) {
			$post_data[ $key ] = isset( $course_item[ $key ] ) ? call_user_func( $sanitizer, $course_item[ $key ] ) : '';
		}
		$post_data['post_type'] = 'academy_courses';
		$course_id = wp_insert_post( $post_data );
		$user_ids = get_users(
			array(
				'meta_key' => 'academy_instructor_course_id',
				'meta_value' => $course_id,
			)
		);
		foreach ( $user_ids as $user_id ) {
			add_user_meta( $user_id->ID, 'academy_instructor_course_id', $course_id );
		}
		return $course_id;
	}

	public function insert_course_meta_value( $course_meta_item, $new_course_id ) {
		$response = false;
		$allowed_meta_fields = [
			'course_expire_enrollment'    => 'sanitize_text_field',
			'course_type'                 => 'sanitize_text_field',
			'course_max_students'         => 'absint',
			'course_language'             => 'sanitize_text_field',
			'course_difficulty_level'     => 'sanitize_text_field',
			'course_benefits'             => 'sanitize_textarea_field',
			'course_requirements'         => 'sanitize_textarea_field',
			'course_audience'             => 'sanitize_textarea_field',
			'course_materials_included'   => 'sanitize_textarea_field',
			'is_enabled_course_qa'        => 'sanitize_key',
			'is_enabled_course_announcements' => 'sanitize_key',
			'course_duration'             => 'sanitize_key',
			'course_intro_video'          => 'sanitize_text_field',
			'course_curriculum'           => 'sanitize_text_field',
			'course_certificate_id'       => 'absint',
		];
		// update product meta
		update_post_meta( $new_course_id, 'academy_course_download_id', 0 );
		update_post_meta( $new_course_id, 'academy_course_product_id', 0 );
		// product id handle
		$product_id = $course_meta_item['course_product_id'] ?? null;
		$download_id = $course_meta_item['course_download_id'] ?? null;

		// Handle WooCommerce product creation
		if ( $product_id && \Academy\Helper::get_addon_active_status( 'woocommerce' ) ) {
			$product = new \WC_Product_Simple( $product_id );
			$product->set_name( get_the_title( $new_course_id ) );
			$product->set_slug( get_post_field( 'post_name', $new_course_id ) );
			$product->set_regular_price( $course_meta_item['regular_price'] ?? 0 );

			if ( ! empty( $course_meta_item['sale_price'] ) ) {
				$product->set_sale_price( $course_meta_item['sale_price'] );
			}

			$product_id = $product->save();

			if ( $product_id ) {
				update_post_meta( $product_id, '_academy_product', 'yes' );
				update_post_meta( $new_course_id, 'academy_course_product_id', $product_id );
			}
		}

		// Handle EDD download creation
		if ( $download_id && \Academy\Helper::get_addon_active_status( 'easy-digital-downloads' ) ) {
			$args = [
				'post_type'   => 'download',
				'post_status' => 'publish',
				'post_title'  => get_the_title( $new_course_id ),
			];
			$download_id = wp_insert_post( $args, true );

			if ( ! is_wp_error( $download_id ) ) {
				$download = new \EDD_Download( $download_id );
				update_post_meta( $download_id, '_academy_course', 'yes' );
				update_post_meta( $download_id, 'edd_price', $course_meta_item['edd_price'] ?? 0 );

				update_post_meta( $new_course_id, 'academy_course_download_id', $download_id );
			}
		}

		// Insert course meta
		foreach ( $allowed_meta_fields as $key => $sanitizer ) {
			if ( isset( $course_meta_item[ $key ] ) ) {
				$data = ( 'course_intro_video' === $key || 'course_duration' === $key || 'course_curriculum' === $key )
					? $this->maybe_unserialize( $key, $course_meta_item[ $key ] )
					: $course_meta_item[ $key ];

				update_post_meta( $new_course_id, 'academy_' . $key, call_user_func( $sanitizer, $data ) );
				$response = true;
			}
		}

		return $response;
	}

	private function maybe_unserialize( $key, $data ) {
		if ( ! empty( $data ) && is_serialized( $data ) ) {

			$unserialized_data = maybe_unserialize( $data );
			if ( is_array( $unserialized_data ) ) {
				$unserialized_data = array_map( 'sanitize_text_field', $unserialized_data );
			} elseif ( is_string( $unserialized_data ) ) {
				$unserialized_data = sanitize_text_field( $unserialized_data );
			} else {
				$unserialized_data = null;
			}
			if ( empty( $unserialized_data ) ) {
				return 'course_duration' === sanitize_key( $key ) ? array( 0, 0, 0 ) : array();
			}

			return $unserialized_data;
		}

		return $data;
	}

	public function insert_lesson_data( $item ) {
		if ( empty( $item['lesson_title'] ) ) {
			return '';
		}

		$user                  = get_user_by( 'login', $item['lesson_author'] );
		$allowed_tags          = wp_kses_allowed_html( 'post' );
		$allowed_tags['input'] = array(
			'type'  => true,
			'name'  => true,
			'value' => true,
			'class' => true,
		);
		$allowed_tags['form']  = array(
			'action' => true,
			'method' => true,
			'class'  => true,
		);
		$allowed_tags['iframe'] = array(
			'src'             => true,
			'width'           => true,
			'height'          => true,
			'frameborder'     => true,
			'allow'           => true,
			'allowfullscreen' => true,
		);
		$content               = wp_kses( $item['lesson_content'], $allowed_tags );

		$lesson_id = \Academy\Classes\Query::lesson_insert( array(
			'lesson_author'  => $user ? $user->ID : (int) get_current_user_id(),
			'lesson_title'   => sanitize_text_field( $item['lesson_title'] ),
			'lesson_name'    => \Academy\Helper::generate_unique_lesson_slug( $item['lesson_title'] ),
			'lesson_content' => $content,
			'lesson_status'  => $item['lesson_status'],
		) );

		if ( $lesson_id ) {
			\Academy\Classes\Query::lesson_meta_insert( $lesson_id, array(
				'featured_media' => 0,
				'attachment'     => 0,
				'is_previewable' => sanitize_text_field( $item['is_previewable'] ),
				'video_duration' => sanitize_text_field( $item['video_duration'] ),
				'video_source'   => wp_json_encode( array(
					'type' => sanitize_text_field( $item['video_source_type'] ),
					'url'  => $this->sanitize_video_source( $item['video_source_type'], $item['video_source_url'] ),
				) ),
			) );

			return $lesson_id;
		}
	}

	public function sanitize_video_source( $source, $url ) {
		switch ( $source ) {
			case 'embedded':
				return filter_var( $url, FILTER_SANITIZE_URL );
			case 'short_code':
				return wp_kses_post( $url );
			default:
				return sanitize_text_field( $url );
		}
	}

	public function update_course_curriculum( $curriculums, $new_course_id ) {
		if ( is_array( $curriculums ) ) {
			$new_curriculum = array();
			foreach ( $curriculums as $curriculum ) {
				$new_topics = array();
				foreach ( $curriculum->topics as $topic ) {
					if ( isset( $topic->type ) && 'sub-curriculum' !== $topic->type ) {
						$new_topics[] = $this->set_topics( $topic );
					} elseif ( ! empty( $topic ) ) {
						$sub_topics = [
							'name' => $topic->name,
							'type' => $topic->type,
							'id'   => $topic->id,
							'topics' => [],
						];
						foreach ( $topic->topics as $sub_topic ) {
							$sub_topics['topics'][] = $this->set_topics( $sub_topic );
						}
						$new_topics[] = $sub_topics;
					}
				}//end foreach
				$new_curriculum[] = array(
					'title' => (string) $curriculum->title ? $curriculum->title : 'Academy Topics',
					'content' => $curriculum->content,
					'topics' => $new_topics,
				);
			}//end foreach
			if ( is_array( $new_curriculum ) ) {
				update_post_meta( $new_course_id, 'academy_course_curriculum', $new_curriculum );
			}
		}//end if
	}

	private function set_topics( $topic ) {
		$new_topics = [];
		switch ( $topic->type ) {
			case 'lesson':
				$lesson_id = \Academy\Helper::get_topic_id_by_topic_name_and_topic_type( $topic->name, 'lesson' );
				if ( $lesson_id ) {
					return array(
						'id' => $lesson_id,
						'name' => $topic->name,
						'type'  => 'lesson',
					);
				}
				break;
			case 'quiz':
				$quiz_id = \Academy\Helper::get_topic_id_by_topic_name_and_topic_type( $topic->name, 'quiz' );
				if ( $quiz_id ) {
					return array(
						'id' => $quiz_id,
						'name' => $topic->name,
						'type'  => 'quiz',
					);
				}
				break;
			case 'assignment':
				$assignment_id = \Academy\Helper::get_topic_id_by_topic_name_and_topic_type( $topic->name, 'assignment' );
				if ( $assignment_id ) {
					return array(
						'id' => $assignment_id,
						'name' => $topic->name,
						'type'  => 'assignment',
					);
				}
				break;
		}//end switch
		return $new_topics;
	}

}
