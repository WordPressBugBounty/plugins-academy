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

}
