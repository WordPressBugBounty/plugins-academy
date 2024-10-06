<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Academy\Helper;

/**
 * Academy Templates Related all functions write here
 */

if ( ! function_exists( 'academy_load_canvas_page_template' ) ) {
	function academy_load_canvas_page_template( $templates ) {
		$templates['academy-canvas.php'] = esc_html__( 'Academy Canvas', 'academy' );
		return $templates;
	}
}

if ( ! function_exists( 'academy_redirect_canvas_page_template' ) ) {
	function academy_redirect_canvas_page_template( $template ) {
		$post = get_post();
		$page_template = get_post_meta( $post->ID, '_wp_page_template', true );
		if ( 'academy-canvas.php' === basename( $page_template ) ) {
			$template = ACADEMY_ROOT_DIR_PATH . 'templates/academy-canvas.php';
		}
		return $template;
	}
}

if ( ! function_exists( 'academy_single_course_sidebar' ) ) {
	function academy_single_course_sidebar() {
		Helper::get_template( 'single-course/sidebar.php' );
	}
}



if ( ! function_exists( 'academy_single_course_header' ) ) {
	function academy_single_course_header() {
		$difficulty_level = get_post_meta( get_the_ID(), 'academy_course_difficulty_level', true );
		$preview_video    = Helper::get_course_preview_video( get_the_ID() );
		Helper::get_template(
			'single-course/header.php',
			apply_filters(
				'academy/single_course_header_args',
				[
					'difficulty_level' => $difficulty_level,
					'preview_video'    => $preview_video,
				]
			)
		);
	}
}





if ( ! function_exists( 'academy_single_course_description' ) ) {
	function academy_single_course_description() {
		Helper::get_template( 'single-course/description.php' );
	}
}


if ( ! function_exists( 'academy_single_course_curriculums' ) ) {
	function academy_single_course_curriculums() {
		$course_id = get_the_ID();
		$curriculums = Helper::get_course_curriculum( $course_id, false );
		$topics_first_item_open_status = (bool) Helper::get_settings( 'is_opened_course_single_first_topic', true );

		Helper::get_template(
			'single-course/curriculums.php',
			array(
				'curriculums'                     => $curriculums,
				'topics_first_item_open_status'  => $topics_first_item_open_status,
			)
		);
	}
}//end if


if ( ! function_exists( 'academy_single_course_instructors' ) ) {
	function academy_single_course_instructors() {
		global $post;
		$author_id = $post->post_author;
		if ( Helper::get_addon_active_status( 'multi_instructor' ) ) {
			$instructors = Helper::get_instructors_by_course_id( get_the_ID() );
		} else {
			$instructors = Helper::get_instructor_by_author_id( $author_id );
		}
		$instructor_reviews_status = (bool) Helper::get_settings( 'is_enabled_instructor_review', true );
		if ( ! $instructors ) {
			return;
		}
		Helper::get_template(
			'single-course/instructors.php',
			apply_filters(
				'academy/single_course_content_instructors_args',
				[
					'instructors' => $instructors,
					'instructor_reviews_status' => $instructor_reviews_status,
				]
			)
		);
	}
}//end if

if ( ! function_exists( 'academy_single_course_benefits' ) ) {
	function academy_single_course_benefits() {
		$benefits = Academy\Helper::string_to_array( get_post_meta( get_the_ID(), 'academy_course_benefits', true ) );
		Helper::get_template( 'single-course/benefits.php', apply_filters( 'academy/single_course_content_benefits_args', [ 'benefits' => $benefits ] ) );
	}
}

if ( ! function_exists( 'academy_single_course_additional_info' ) ) {
	function academy_single_course_additional_info() {
		$audience     = Academy\Helper::string_to_array( get_post_meta( get_the_ID(), 'academy_course_audience', true ) );
		$requirements = Academy\Helper::string_to_array( get_post_meta( get_the_ID(), 'academy_course_requirements', true ) );
		$materials    = Academy\Helper::string_to_array( get_post_meta( get_the_ID(), 'academy_course_materials_included', true ) );
		$tabs_nav     = [];
		$tabs_content = [];
		if ( is_array( $audience ) && count( $audience ) > 0 ) {
			$tabs_nav['audience']     = esc_html__( 'Targeted Audience', 'academy' );
			$tabs_content['audience'] = $audience;
		}
		if ( is_array( $requirements ) && count( $requirements ) > 0 ) {
			$tabs_nav['requirements']     = esc_html__( 'Requirements', 'academy' );
			$tabs_content['requirements'] = $requirements;
		}
		if ( is_array( $materials ) && count( $materials ) > 0 ) {
			$tabs_nav['materials']     = esc_html__( 'Materials Included', 'academy' );
			$tabs_content['materials'] = $materials;
		}

		Helper::get_template(
			'single-course/additional-info.php',
			apply_filters(
				'academy/single_course_content_additional_info_args',
				[
					'tabs_nav'     => $tabs_nav,
					'tabs_content' => $tabs_content,
				]
			)
		);
	}
}//end if

if ( ! function_exists( 'academy_single_course_feedback' ) ) {
	function academy_single_course_feedback() {
		if ( ! (bool) Helper::get_settings( 'is_enabled_course_review', true ) ) {
			return;
		}
		$rating = Helper::get_course_rating( get_the_ID() );
		Helper::get_template( 'single-course/feedback.php', array( 'rating' => $rating ) );
	}
}

if ( ! function_exists( 'academy_single_course_reviews' ) ) {
	function academy_single_course_reviews() {
		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
	}
}

if ( ! function_exists( 'academy_archive_course_header' ) ) {
	function academy_archive_course_header() {
		Helper::get_template( 'archive/header.php' );
	}
}

if ( ! function_exists( 'academy_archive_course_header_filter' ) ) {
	function academy_archive_course_header_filter() {
		global $wp_query;
		$orderby = ( get_query_var( 'orderby' ) ) ? get_query_var( 'orderby' ) : ''; ?>
		<div class="academy-courses__header-filter">
			<p class="academy-courses__header-result-count"><?php esc_html_e( 'Showing all', 'academy' ); ?>
				<span><?php echo esc_html( $wp_query->found_posts ); ?></span> <?php esc_html_e( 'results', 'academy' ); ?>
			</p>
			<form class="academy-courses__header-ordering" method="get">
				<select name="orderby" class="academy-courses__header-orderby" aria-label="Course order"
					onchange="this.form.submit()">
					<option value="DESC" <?php selected( $orderby, 'DESC' ); ?>>
						<?php esc_html_e( 'Default Sorting', 'academy' ); ?>
					</option>
					<option value="menu_order" <?php selected( $orderby, 'menu_order' ); ?>>
						<?php esc_html_e( 'Menu Order', 'academy' ); ?>
					</option>
					<option value="name" <?php selected( $orderby, 'name' ); ?>>
						<?php esc_html_e( 'Order by course name', 'academy' ); ?>
					</option>
					<option value="date" <?php selected( $orderby, 'date' ); ?>>
						<?php esc_html_e( 'Order by Publish Date', 'academy' ); ?>
					</option>
					<option value="modified" <?php selected( $orderby, 'modified' ); ?>>
						<?php esc_html_e( 'Order by Modified Date', 'academy' ); ?>
					</option>
					<option value="ratings" <?php selected( $orderby, 'ratings' ); ?>>
						<?php esc_html_e( 'Order by Most Reviews', 'academy' ); ?>
					</option>
					<option value="ID" <?php selected( $orderby, 'ID' ); ?>>
						<?php esc_html_e( 'Order by ID', 'academy' ); ?>
					</option>
				</select>
				<input type="hidden" name="paged" value="1">
			</form>
		</div>
		<?php
	}
}//end if

if ( ! function_exists( 'academy_no_course_found' ) ) {
	function academy_no_course_found() {
		Helper::get_template( 'archive/course-none.php' );
	}
}

if ( ! function_exists( 'academy_course_pagination' ) ) {
	function academy_course_pagination() {
		Helper::get_template( 'archive/pagination.php' );
	}
}

if ( ! function_exists( 'academy_course_loop_header' ) ) {
	function academy_course_loop_header() {
		global $wpdb;
		$course_id              = get_the_ID();
		$user_id                = get_current_user_id();
		$is_already_in_wishlist = false;
		$wishlists_status = (bool) Helper::get_settings( 'is_enabled_course_wishlist', true );
		if ( $wishlists_status ) {
			$is_already_in_wishlist = $wpdb->get_row( $wpdb->prepare( "SELECT * from {$wpdb->usermeta} WHERE user_id = %d AND meta_key = 'academy_course_wishlist' AND meta_value = %d;", $user_id, $course_id ) );
		}
		Helper::get_template( 'loop/header.php', array(
			'is_already_in_wishlist' => $is_already_in_wishlist,
			'wishlists_status' => $wishlists_status
		) );
	}
}

if ( ! function_exists( 'academy_course_loop_content' ) ) {
	function academy_course_loop_content() {
		$card_style = \Academy\Helper::get_settings( 'course_card_style' );
		switch ( $card_style ) {
			case 'layout_two':
				Helper::get_template( 'loop/content-layout-two.php' );
				break;
			case 'layout_three':
				Helper::get_template( 'loop/content-layout-three.php' );
				break;
			case 'layout_four':
				Helper::get_template( 'loop/content-layout-four.php' );
				break;
			default:
				Helper::get_template( 'loop/content.php' );
				break;
		}
	}
}

if ( ! function_exists( 'academy_course_loop_footer' ) ) {
	function academy_course_loop_footer() {
		Helper::get_template( 'loop/footer.php' );
	}
}

if ( ! function_exists( 'academy_course_loop_rating' ) ) {
	function academy_course_loop_rating( $card_style ) {
		$rating = Helper::get_course_rating( get_the_ID() );
		$reviews_status = Helper::get_settings( 'is_enabled_course_review', true );
		if ( $reviews_status && 'default' === $card_style ) {
			Helper::get_template( 'loop/rating.php', [ 'rating' => $rating ] );
		}
	}
}

if ( ! function_exists( 'academy_course_loop_enroll' ) ) {
	function academy_course_loop_enroll() {
		Helper::get_template( 'loop/enroll.php' );
	}
}

if ( ! function_exists( 'academy_course_loop_footer_inner_price' ) ) {
	function academy_course_loop_footer_inner_price( $card_style ) {
		if ( 'layout_two' !== $card_style ) {
			$course_id = get_the_ID();
			$course_type   = Helper::get_course_type( $course_id );
			$is_paid   = Helper::is_course_purchasable( $course_id );
			$price     = '';
			if ( Helper::is_active_woocommerce() && $is_paid ) {
				$product_id = Academy\Helper::get_course_product_id( $course_id );
				if ( $product_id ) {
					$product = wc_get_product( $product_id );
					if ( $product ) {
						$price   = $product->get_price_html();
					}
				}
			}
			Helper::get_template(
				'loop/price.php',
				apply_filters('academy/template/loop/price_args', array(
					'price'   => $price,
					'is_paid' => $is_paid,
					'course_type' => $course_type,
				), $course_id )
			);
		}//end if
	}
}//end if

if ( ! function_exists( 'academy_course_loop_footer_form' ) ) {
	function academy_course_loop_footer_form( $card_style ) {
		$course_id = get_the_ID();
		$post_type = get_post_type( $course_id );
		if ( 'default' !== $card_style && 'alms_course_bundle' !== $post_type ) {
			$course_type   = Helper::get_course_type( $course_id );
			$is_paid   = Helper::is_course_purchasable( $course_id );
			$monetization_engine   = \Academy\Helper::monetization_engine();
			$product_id = 0;
			$download_id = 0;
			if ( $is_paid ) {
				$product_id = Academy\Helper::get_course_product_id( $course_id );
				$download_id = get_post_meta( $course_id, 'academy_course_download_id', true );
			}
			if ( 'paid-memberships-pro' === $monetization_engine ) {
				$required_levels = AcademyProPaidMembershipsPro\Helper::has_course_access( $course_id );
				if ( is_array( $required_levels ) && count( $required_levels ) ) {
					$pmp_levels = $required_levels;
				}
			}

			Helper::get_template(
				'loop/footer-form.php',
				apply_filters('academy/template/loop/footer_form', array(
					'is_paid'  => $is_paid,
					'course_type' => $course_type,
					'product_id' => $product_id,
					'download_id' => $download_id,
					'required_levels' => $pmp_levels ?? '',
					'engine'          => $monetization_engine,
				), $course_id )
			);
		}//end if
	}
}//end if

if ( ! function_exists( 'academy_review_lists' ) ) {
	function academy_review_lists( $comment, $args, $depth ) {
		Helper::get_template(
			'single-course/review.php',
			array(
				'comment' => $comment,
				'args'    => $args,
				'depth'   => $depth,
			)
		);
	}
}


if ( ! function_exists( 'academy_review_display_gravatar' ) ) {
	/**
	 * Display the review authors gravatar
	 *
	 * @param array $comment WP_Comment.
	 * @return void
	 */
	function academy_review_display_gravatar( $comment ) {
		echo get_avatar( $comment->comment_author_email, apply_filters( 'academy/review_gravatar_size', '80' ), '' );
	}
}

if ( ! function_exists( 'academy_review_display_rating' ) ) {
	/**
	 * Display the reviewers star rating
	 *
	 * @return void
	 */
	function academy_review_display_rating() {
		if ( post_type_supports( 'academy_courses', 'comments' ) ) {
			$reviews_status = (bool) Helper::get_settings( 'is_enabled_course_review', true );
			if ( $reviews_status ) {
				Helper::get_template( 'single-course/review-rating.php' );
			}
		}
	}
}

if ( ! function_exists( 'academy_review_display_meta' ) ) {
	/**
	 * Display the review authors meta (name, verified owner, review date)
	 *
	 * @return void
	 */
	function academy_review_display_meta() {
		Helper::get_template( 'single-course/review-meta.php' );
	}
}


if ( ! function_exists( 'academy_review_display_comment_text' ) ) {

	/**
	 * Display the review content.
	 */
	function academy_review_display_comment_text() {
		echo '<div class="academy-review-description">';
		comment_text();
		echo '</div>';
	}
}


if ( ! function_exists( 'academy_get_rating_html' ) ) {
	/**
	 * Get HTML for ratings.
	 *
	 * @param  float $rating Rating being shown.
	 * @param  int   $count  Total number of ratings.
	 * @return string
	 */
	function academy_get_rating_html( $rating, $count = 0 ) {
		$html = '';
		if ( 0 < $rating ) {
			$html = Helper::single_star_rating_generator( $rating );
		}
		return apply_filters( 'academy/course_get_rating_html', $html, $rating, $count );
	}
}

if ( ! function_exists( 'academy_single_course_enroll' ) ) {
	function academy_single_course_enroll() {
		Helper::get_template(
			'single-course/enroll/enroll.php'
		);
	}
}

if ( ! function_exists( 'academy_single_course_enroll_content' ) ) {
	function academy_single_course_enroll_content() {
		$course_id   = get_the_ID();
		$enrolled    = Helper::is_enrolled( get_the_ID(), get_current_user_id() );
		$completed   = Helper::is_completed_course( get_the_ID(), get_current_user_id(), true );
		$is_paid     = (bool) Helper::is_course_purchasable( $course_id );
		$is_public = Helper::is_public_course( $course_id );
		$price       = '';
		$monetization = Helper::get_settings( 'monetization_engine', 'woocommerce' );
		if ( $is_paid && ( Helper::is_active_woocommerce() || Helper::is_active_easy_digital_downloads() ) ) {
			if ( 'woocommerce' === $monetization ) {
				$product_id = Academy\Helper::get_course_product_id( $course_id );
				if ( $product_id ) {
					$product = wc_get_product( $product_id );
					if ( $product ) {
						$price   = $product->get_price_html();
					}
				}
			}
			if ( 'edd' === $monetization ) {
				$download_id = Academy\Helper::get_course_download_id( $course_id );
				if ( $download_id ) {
					$download = edd_get_download( $download_id );
					if ( $download ) {
						$price   = edd_price( $download_id, false );
					}
				}
			}
		}

		$duration       = Helper::get_course_duration( $course_id );
		$total_lessons  = Helper::get_total_number_of_course_lesson( $course_id );
		$total_enroll_count_status = Helper::get_settings( 'is_enabled_course_single_enroll_count', true );
		$total_enrolled = Helper::count_course_enrolled( $course_id );
		$skill          = Helper::get_course_difficulty_level( $course_id );
		$language       = get_post_meta( $course_id, 'academy_course_language', true );
		$max_students   = (int) get_post_meta( $course_id, 'academy_course_max_students', true );
		$last_update    = get_the_modified_time( get_option( 'date_format' ), $course_id );

		ob_start();

		Helper::get_template(
			'single-course/enroll/content.php',
			apply_filters(
				'academy/single/enroll_content_args',
				array(
					'enrolled'       => $enrolled,
					'completed'      => $completed,
					'is_paid'        => $is_paid,
					'is_public'      => $is_public,
					'price'          => $price,
					'duration'       => $duration,
					'total_lessons'  => $total_lessons,
					'total_enroll_count_status' => $total_enroll_count_status,
					'total_enrolled' => $total_enrolled,
					'skill'          => $skill,
					'language'       => $language,
					'max_students'   => $max_students,
					'last_update'    => $last_update,
				),
				$course_id
			)
		);
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo apply_filters( 'academy/templates/single_course/enroll_content', ob_get_clean(), $course_id );
	}
}//end if

if ( ! function_exists( 'academy_course_enroll_form' ) ) {
	function academy_course_enroll_form( $course_id = null ) {
		global $post;
		$original_post = $post;
		if ( $course_id ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$GLOBALS['post'] = get_post( $course_id );
		}
		$user_ID   = get_current_user_id();
		$enrolled  = Helper::is_enrolled( get_the_ID(), get_current_user_id(), 'any' );
		// Course Materials Access
		$is_administrator = current_user_can( 'administrator' );
		$is_instructor    = Helper::is_instructor_of_this_course( $user_ID, get_the_ID() );
		$is_public_course = Helper::is_public_course( get_the_ID() );

		ob_start();

		// is private course
		if ( 'private' === get_post_status( $course_id ) ) {
			if ( ! current_user_can( 'manage_academy_instructor' ) && ! $enrolled ) {
				return Helper::get_template( 'single-course/enroll/private-course.php' );
			}
		}

		if ( ( $enrolled && 'completed' === $enrolled->enrolled_status ) || $is_administrator || $is_instructor || $is_public_course ) {
			Helper::get_template( 'single-course/enroll/continue.php' );
		}

		// is public course
		if ( $is_public_course ) {
			return;
		}
		// Enrollment Functionality
		if ( $enrolled && 'completed' === $enrolled->enrolled_status ) {
			$is_completed_course = Helper::is_completed_course( get_the_ID(), $user_ID );
			$is_show_complete_form = apply_filters( 'academy/single/is_show_complete_form', true, $is_completed_course, get_the_ID() );
			if ( $is_show_complete_form ) {
				Helper::get_template( 'single-course/enroll/complete-form.php', array( 'is_completed_course' => $is_completed_course ) );
			}
		} elseif ( $enrolled && ( 'on-hold' === $enrolled->enrolled_status || 'processing' === $enrolled->enrolled_status ) ) {
			Helper::get_template( 'single-course/enroll/notice.php', array(
				'status' => $enrolled->enrolled_status
			) );
		} elseif ( Helper::is_course_fully_booked( get_the_ID() ) ) {
			Helper::get_template( 'single-course/enroll/closed-enrollment.php' );
		} elseif ( 'woocommerce' === Helper::monetization_engine() && Helper::is_course_purchasable( get_the_ID() ) ) {
			$product_id = Academy\Helper::get_course_product_id( get_the_ID() );
			$is_enabled_academy_login = Helper::get_settings( 'is_enabled_academy_login', true );
			$force_login_before_enroll = $is_enabled_academy_login && Helper::get_settings( 'woo_force_login_before_enroll', true );
			Helper::get_template( 'single-course/enroll/add-to-cart-form.php', array(
				'product_id'                => $product_id,
				'force_login_before_enroll' => $force_login_before_enroll
			) );
		} elseif ( 'edd' === Helper::monetization_engine() && Helper::is_course_purchasable( get_the_ID() ) ) {
			$download_id = Academy\Helper::get_course_download_id( get_the_ID() );
			$is_enabled_academy_login = Helper::get_settings( 'is_enabled_academy_login', true );
			$force_login_before_enroll = $is_enabled_academy_login && Helper::get_settings( 'woo_force_login_before_enroll', true );
			Helper::get_template( 'single-course/enroll/add-to-cart-form.php', array(
				'download_id'                => $download_id,
				'force_login_before_enroll' => $force_login_before_enroll
			) );
		} else {
			$is_enabled_academy_login = Helper::get_settings( 'is_enabled_academy_login', true );
			Helper::get_template( 'single-course/enroll/enroll-form.php', array(
				'is_enabled_academy_login' => $is_enabled_academy_login
			) );
		}//end if

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo apply_filters( 'academy/templates/single_course/enroll_form', ob_get_clean(), get_the_ID() );
		if ( $course_id ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$GLOBALS['post'] = $original_post;
		}
	}
}//end if

if ( ! function_exists( 'academy_course_enroll_wishlist_and_share' ) ) {
	function academy_course_enroll_wishlist_and_share() {
		global $wpdb;
		$course_id              = get_the_ID();
		$user_id                = get_current_user_id();
		$is_already_in_wishlist = $wpdb->get_row( $wpdb->prepare( "SELECT * from {$wpdb->usermeta} WHERE user_id = %d AND meta_key = 'academy_course_wishlist' AND meta_value = %d;", $user_id, $course_id ) );
		$is_show_wishlist = (bool) Helper::get_settings( 'is_enabled_course_wishlist', true );
		$is_show_course_share = (bool) Helper::get_settings( 'is_enabled_course_share', true );
		if ( $is_show_wishlist || $is_show_course_share ) {
			Helper::get_template(
				'single-course/enroll/wishlist-and-share.php',
				apply_filters(
					'academy/single/course_enroll_wishlist_and_share_args',
					[
						'is_already_in_wishlist'    => $is_already_in_wishlist,
						'is_show_wishlist'          => $is_show_wishlist,
						'is_show_course_share'      => $is_show_course_share,
					]
				)
			);
		}
	}
}//end if


if ( ! function_exists( 'academy_archive_course_filter_widget' ) ) {
	function academy_archive_course_filter_widget() {
		$filters = Helper::get_settings(
			'course_archive_filters',
			[
				[
					'search'   => true,
				],
				[
					'category'   => true,
				],
				[
					'tags'   => true,
				],
				[
					'levels'   => true,
				],
				[
					'type'   => true,
				],
			]
		);
		// make it single array
		$filters = array_reduce($filters, function( $carry, $item ) {
			return array_merge( $carry, (array) $item );
		}, []);

		$filters = apply_filters( 'academy/archive/course_filter_widget_args', $filters );

		foreach ( $filters as $key => $value ) {
			$filter_function = 'academy_archive_course_filter_by_' . $key;
			if ( $value && function_exists( $filter_function ) ) {
				$filter_function();
			}
		}
	}
}//end if



if ( ! function_exists( 'academy_archive_course_filter_by_search' ) ) {
	function academy_archive_course_filter_by_search() {
		Helper::get_template( 'archive/widgets/search.php', apply_filters( 'academy/archive/course_filter_by_search_args', [] ) );
	}
}

if ( ! function_exists( 'academy_archive_course_filter_by_category' ) ) {
	function academy_archive_course_filter_by_category() {
		$categories = Academy\Helper::get_all_courses_category_lists();
		Helper::get_template(
			'archive/widgets/category.php',
			apply_filters(
				'academy/archive/course_filter_by_category_args',
				[
					'categories' => $categories,
				]
			)
		);
	}
}

if ( ! function_exists( 'academy_archive_course_filter_by_tags' ) ) {
	function academy_archive_course_filter_by_tags() {
		$tags = get_terms(
			array(
				'taxonomy'   => 'academy_courses_tag',
				'hide_empty' => true,
			)
		);

		Helper::get_template(
			'archive/widgets/tags.php',
			apply_filters(
				'academy/archive/course_filter_by_tags_args',
				[
					'tags' => $tags,
				]
			)
		);
	}
}//end if

if ( ! function_exists( 'academy_archive_course_filter_by_levels' ) ) {
	function academy_archive_course_filter_by_levels() {
		$levels = array(
			'beginner'     => __( 'Beginner', 'academy' ),
			'intermediate' => __( 'Intermediate', 'academy' ),
			'experts'      => __( 'Expert', 'academy' ),
		);

		Helper::get_template(
			'archive/widgets/levels.php',
			apply_filters(
				'academy/archive/course_filter_by_levels_args',
				[
					'levels' => $levels,
				]
			)
		);
	}
}//end if

if ( ! function_exists( 'academy_archive_course_filter_by_type' ) ) {
	function academy_archive_course_filter_by_type() {
		$type = apply_filters('academy/get_course_filter_types', array(
			'free' => __( 'Free', 'academy' ),
			'paid' => __( 'Paid', 'academy' ),
		));
		Helper::get_template(
			'archive/widgets/type.php',
			apply_filters(
				'academy/archive/course_filter_by_type_args',
				[
					'type' => $type,
				]
			)
		);
	}
}


if ( ! function_exists( 'academy_archive_course_sidebar' ) ) {
	function academy_archive_course_sidebar() {
		Helper::get_template( 'archive/sidebar.php' );
	}
}


if ( ! function_exists( 'academy_instructor_public_profile_sidebar' ) ) {
	function academy_instructor_public_profile_sidebar() {
		$author_ID    = Academy\Helper::get_the_author_id();
		$reviews      = Helper::get_instructor_ratings( $author_ID );
		$website_url  = get_user_meta( $author_ID, 'academy_website_url', true );
		$facebook_url = get_user_meta( $author_ID, 'academy_facebook_url', true );
		$github_url   = get_user_meta( $author_ID, 'academy_github_url', true );
		$twitter_url  = get_user_meta( $author_ID, 'academy_twitter_url', true );
		$linkdin_url  = get_user_meta( $author_ID, 'academy_linkedin_url', true );
		$is_enabled_instructor_review = Helper::get_settings( 'is_enabled_instructor_review', true );
		Helper::get_template(
			'instructor/sidebar.php',
			apply_filters(
				'academy/instructor/instructor_public_profile_sidebar_args',
				[
					'author_ID'    => $author_ID,
					'reviews'      => $reviews,
					'website_url'  => $website_url,
					'facebook_url' => $facebook_url,
					'github_url'   => $github_url,
					'twitter_url'  => $twitter_url,
					'linkdin_url'  => $linkdin_url,
					'is_enabled_instructor_review'  => $is_enabled_instructor_review,
				]
			)
		);
	}
}//end if

if ( ! function_exists( 'academy_global_courses' ) ) {
	function academy_global_courses() {
		Helper::get_template( 'global/courses.php' );
	}
}

if ( ! function_exists( 'academy_instructor_public_profile_header' ) ) {
	function academy_instructor_public_profile_header() {
		$author_ID       = Academy\Helper::get_the_author_id();
		$cover_photo_url = get_the_author_meta( 'academy_cover_photo', $author_ID );
		if ( empty( $cover_photo_url ) ) {
			$cover_photo_url = apply_filters( 'academy/instructor/public_profile_placeholder_cover_photo_url', ACADEMY_ASSETS_URI . 'images/banner.jpg' );
		}
		$share_config    = array(
			'title' => Academy\Helper::get_the_author_name( $author_ID ),
			'text'  => get_the_author_meta( 'academy_profile_designation', $author_ID ),
			'image' => esc_url( Academy\Helper::get_the_author_thumbnail_url( $author_ID ) ),
		);
		Helper::get_template(
			'instructor/header.php',
			apply_filters(
				'academy/instructor/public_profile_header_args',
				[
					'cover_photo_url' => $cover_photo_url,
					'share_config'    => $share_config,
				]
			)
		);
	}
}//end if

if ( ! function_exists( 'academy_instructor_public_profile_tabs_nav' ) ) {
	function academy_instructor_public_profile_tabs_nav() {
		$is_enabled_instructor_review = (bool) Helper::get_settings( 'is_enabled_instructor_review', true );

		Helper::get_template(
			'instructor/tab-navbar.php',
			apply_filters(
				'academy/instructor/public_profile_tabs_nav_args',
				[
					'is_enabled_instructor_review' => $is_enabled_instructor_review
				]
			)
		);
	}
}
if ( ! function_exists( 'academy_instructor_public_profile_tabs_content' ) ) {
	function academy_instructor_public_profile_tabs_content() {
		$is_enabled_instructor_review = (bool) Helper::get_settings( 'is_enabled_instructor_review', true );
		Helper::get_template(
			'instructor/tab-content.php',
			apply_filters(
				'academy/instructor/public_profile_tabs_content_args',
				[
					'is_enabled_instructor_review' => $is_enabled_instructor_review
				]
			)
		);
	}
}


if ( ! function_exists( 'academy_instructor_public_profile_reviews' ) ) {
	function academy_instructor_public_profile_reviews() {
		$author_ID = get_query_var( 'author' );
		$reviews   = Helper::get_reviews_by_instructor( $author_ID );
		$results   = [];
		if ( is_array( $reviews ) ) {
			foreach ( $reviews as $review ) {
				$review->post_title     = get_the_title( $review->comment_post_ID );
				$review->post_permalink = esc_url( get_the_permalink( $review->comment_post_ID ) );
				$results[]              = $review;
			}
		}
		Helper::get_template(
			'instructor/reviews.php',
			apply_filters(
				'academy/instructor/public_profile_reviews_args',
				[ 'reviews' => $results ]
			)
		);
	}
}//end if

if ( ! function_exists( 'academy_update_avatar_url' ) ) {
	function academy_update_avatar_url( $url, $user ) {
		if ( is_object( $user ) ) {
			$profile_photo = get_user_meta( $user->ID, 'academy_profile_photo', true );
			if ( ! empty( $profile_photo ) ) {
				return esc_url( $profile_photo );
			}
		}
		return $url;
	}
}

if ( ! function_exists( 'academy_update_avatar_data' ) ) {
	function academy_update_avatar_data( $args, $user_id ) {
		if ( $user_id ) {
			$profile_photo = get_user_meta( $user_id, 'academy_profile_photo', true );
			if ( ! empty( $profile_photo ) ) {
				$args['url'] = esc_url( $profile_photo );
				return $args;
			}
		}
		return $args;
	}
}

if ( ! function_exists( 'academy_get_the_canvas_container_class' ) ) {
	function academy_get_the_canvas_container_class() {
		global $post;
		$class_name = apply_filters( 'academy/templates/canvas_container_class', 'academy-container', $post->ID );
		echo esc_attr( $class_name );
	}
}

if ( ! function_exists( 'academy_frontend_dashbaord_container_class' ) ) {
	function academy_frontend_dashbaord_container_class( $class_name, $page_id ) {
		if ( current_user_can( 'manage_academy_instructor' ) && (int) Helper::get_settings( 'frontend_dashboard_page' ) === $page_id ) {
			return $class_name . '-fluid';
		}
		return $class_name;
	}
}

if ( ! function_exists( 'academy_get_preloader_html' ) ) {
	function academy_get_preloader_html() {
		ob_start();
		?>
			<div class="academy-initial-preloader"><?php esc_html_e( 'Loading...', 'academy' ); ?></div>
		<?php
		return ob_get_clean();
	}
}

if ( ! function_exists( 'academy_get_header' ) ) {
	function academy_get_header( $header_name = 'course' ) {
		global $wp_version;
		if ( version_compare( $wp_version, '5.9', '>=' ) && function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			?>
			<!doctype html>
				<html <?php language_attributes(); ?>>
				<head>
					<meta charset="<?php bloginfo( 'charset' ); ?>">
					<?php wp_head(); ?>
				</head>

				<body <?php body_class(); ?>>
				<?php wp_body_open(); ?>
					<div class="wp-site-blocks">
						<?php
						if ( apply_filters( 'academy/templates/is_allow_block_theme_header', true ) ) :
							?>
						<header class="wp-block-template-part site-header">
							<?php block_header_area(); ?>
						</header>
							<?php
							endif;
						?>
			<?php
		} else {
			get_header( $header_name );
		}//end if
	}
}//end if

if ( ! function_exists( 'academy_get_footer' ) ) {
	function academy_get_footer( $footer_name = 'course' ) {
		global $wp_version;
		if ( version_compare( $wp_version, '5.9', '>=' ) && function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			if ( apply_filters( 'academy/templates/is_allow_block_theme_footer', true ) ) :
				?>
				<footer class="wp-block-template-part site-footer">
					<?php block_footer_area(); ?>
				</footer>
				<?php
				endif;
			?>
			</div>
			<?php wp_footer(); ?>
			</body>
			</html>
			<?php
		} else {
			get_footer( $footer_name );
		}
	}
}//end if


if ( ! function_exists( 'academy_curriculum_lesson_content' ) ) {
	function academy_curriculum_lesson_content( $course_id, $topic_id ) {
		$lesson = \Academy\Helper::get_lesson( $topic_id );
		$lesson_meta = \Academy\Helper::get_lesson_meta_data( $topic_id );
		$course_id = \Academy\Helper::get_the_current_course_id();
		$lesson->meta = $lesson_meta;

		do_action( 'academy/templates/curriculums/before_render_lesson_content', $lesson, $course_id, $topic_id );

		$has_permission = \Academy\Helper::has_permission_to_access_lesson_curriculum( $course_id, $lesson ? $lesson->ID : null, get_current_user_id() );

		if ( ! apply_filters( 'academy/templates/curriculums/has_access_lesson_content', $has_permission ) ) {
			return;
		}

		if ( $lesson ) {
			\Academy\Helper::get_template(
				'curriculums/lesson.php',
				array(
					'lesson' => $lesson,
					'lesson_meta' => $lesson_meta,
					'course_id' => $course_id
				)
			);
		} else {
			\Academy\Helper::get_template( 'curriculums/not-found.php' );
		}

		do_action( 'academy/templates/curriculums/after_render_lesson_content', $lesson, $course_id, $topic_id );
	}
}//end if

if ( ! function_exists( 'academy_curriculum_previous_next_template' ) ) {
	function academy_curriculum_previous_next_template() {
		$result = \Academy\Helper::get_prev_and_next_details_of_curriculum();
		\Academy\Helper::get_template( 'curriculums/prev-next-btn.php',
			array(
				'previous' => $result['previous'],
				'next' => $result['next'],
			)
		);
	}
}

function academy_frontend_dashboard_content() {
	global $wp;
	if ( ! empty( $wp->query_vars ) ) {
		foreach ( $wp->query_vars as $key => $value ) {
			// Ignore pagename param.
			if ( 'pagename' === $key || 'academy_dashboard_page' !== $key ) {
				continue;
			}

			if ( has_action( 'academy_frontend_dashboard_' . $value . '_endpoint' ) ) {
				do_action( 'academy_frontend_dashboard_' . $value . '_endpoint', $value );
				return;
			}
		}
	}

	// No endpoint found? Default to dashboard.
	$user_id = get_current_user_id();
	$total_course = \Academy\Helper::get_course_ids_by_instructor_id( $user_id );

	$data = [
		'enrolled_course' => [
			'label' => esc_html__( 'Enrolled Courses', 'academy' ),
			'value' => count( \Academy\Helper::get_enrolled_courses_ids_by_user( $user_id ) ),
			'color' => 'course',
			'icon' => 'academy-icon academy-icon--course-enrolled-two',
			'link' => esc_url( \Academy\Helper::get_frontend_dashboard_endpoint_url( 'enrolled-courses' ) )
		],
		'completed_course' => [
			'label' => esc_html__( 'Completed Courses', 'academy' ),
			'value' => count( \Academy\Helper::get_completed_courses_ids_by_user( $user_id ) ),
			'color' => 'complete',
			'icon' => 'academy-icon academy-icon--certificate',
			'link' => esc_url( \Academy\Helper::get_frontend_dashboard_endpoint_url( 'complete-courses' ) )
		]
	];
	if ( current_user_can( 'manage_academy_instructor' ) ) {
		$data['total_students'] = [
			'label' => esc_html__( 'Total Students', 'academy' ),
			'value' => \Academy\Helper::get_total_number_of_students_by_instructor( $user_id ),
			'color' => 'instructor',
			'icon' => 'academy-icon academy-icon--students-two'
		];
		$data['total_courses'] = [
			'label' => esc_html__( 'Total Courses', 'academy' ),
			'value' => is_array( $total_course ) ? count( $total_course ) : 0,
			'color' => 'students',
			'icon' => 'academy-icon academy-icon--total-course',
			'link' => esc_url( \Academy\Helper::get_frontend_dashboard_endpoint_url( 'courses' ) )
		];
		$data['total_lessons'] = [
			'label' => esc_html__( 'Total Lessons', 'academy' ),
			'value' => \Academy\Helper::get_total_number_of_lessons_by_instructor( $user_id ),
			'color' => 'lesson',
			'icon' => 'academy-icon academy-icon--lessons',
			'link' => esc_url( \Academy\Helper::get_frontend_dashboard_endpoint_url( 'lessons' ) )
		];
		$data['total_questions'] = [
			'label' => esc_html__( 'Total Questions', 'academy' ),
			'value' => \Academy\Classes\Query::get_total_number_of_questions_by_instructor_id( $user_id ),
			'color' => 'question',
			'icon' => 'academy-icon academy-icon--questions-fill',
			'link' => esc_url( \Academy\Helper::get_frontend_dashboard_endpoint_url( 'question-answer' ) )
		];
	}//end if

	if ( \Academy\Helper::get_addon_active_status( 'quizzes' ) && current_user_can( 'manage_academy_instructor' ) ) {
		$data['total_quizzes'] = array(
			'label' => esc_html__( 'Total Quizzes', 'academy' ),
			'value' => \AcademyQuizzes\Classes\Query::get_total_number_of_quizzes_by_instructor_id( $user_id ),
			'color' => 'quiz',
			'icon' => 'academy-icon academy-icon--quiz-fill',
			'link' => esc_url( \Academy\Helper::get_frontend_dashboard_endpoint_url( 'quizzes' ) )
		);
	}

	\Academy\Helper::get_template(
		'frontend-dashboard/pages/dashboard.php',
		[
			'data' => $data,
			'course_ids' => $total_course
		]
	);
}

function academy_frontend_dashboard_menu() {
	$menu_lists = \Academy\Helper::get_frontend_dashboard_menu_items();
	uasort($menu_lists, function( $a, $b ) {
		return $a['priority'] <=> $b['priority'];
	});

	\Academy\Helper::get_template(
		'frontend-dashboard/menu.php',
		array(
			'menu_lists' => $menu_lists
		)
	);
}

function academy_frontend_dashboard_content_topbar() {
	$path = get_query_var( 'academy_dashboard_page' );
	$sub_path = get_query_var( 'academy_dashboard_sub_page' );
	$page_title = \Academy\Helper::get_frontend_dashboard_page_title( $path ? $path : 'index', $sub_path );
	\Academy\Helper::get_template(
		'frontend-dashboard/topbar.php',
		[
			'page_title' => $page_title
		]
	);
}
function academy_frontend_dashboard_become_an_instructor_page() {
	$instructor_status = get_user_meta(
		get_current_user_id(),
		'academy_instructor_status',
		true
	);
	if ( ! current_user_can( 'manage_academy_instructor' ) ) {
		\Academy\Helper::get_template(
			'frontend-dashboard/pages/become-an-instructor.php', [
				'instructor_status' => $instructor_status,
			]
		);
	}
}
function academy_frontend_dashboard_profile_page() {
	$user_id = get_current_user_id();
	$user_info = get_userdata( $user_id );
	$instructor_fields = \Academy\Helper::get_form_builder_fields( 'instructor' );
	$student_fields = \Academy\Helper::get_form_builder_fields( 'student' );

	$user_data = [
		'registration_date' => [
			'label' => esc_html__( 'Registration Date', 'academy' ),
			'value' => get_date_from_gmt( $user_info->user_registered, get_option( 'date_format' ) . ', ' . get_option( 'time_format' ) )
		],
		'first_name' => [
			'label' => esc_html__( 'First Name', 'academy' ),
			'value' => get_user_meta( $user_id, 'first_name', true )
		],
		'last_name' => [
			'label' => esc_html__( 'Last Name', 'academy' ),
			'value' => get_user_meta( $user_id, 'last_name', true )
		],
		'nicename' => [
			'label' => esc_html__( 'User Name', 'academy' ),
			'value' => $user_info->data->user_nicename
		],
		'email' => [
			'label' => esc_html__( 'Email', 'academy' ),
			'value' => $user_info->data->user_email
		],
		'designation' => [
			'label' => esc_html__( 'Designation', 'academy' ),
			'value' => get_user_meta( $user_id, 'academy_profile_designation', true )
		],
		'phone_number' => [
			'label' => esc_html__( 'Phone Number', 'academy' ),
			'value' => get_user_meta( $user_id, 'academy_phone_number', true )
		],
		'bio' => [
			'label' => esc_html__( 'Bio', 'academy' ),
			'value' => get_user_meta( $user_id, 'academy_profile_bio', true )
		],
		'website_url' => [
			'label' => esc_html__( 'Website URL', 'academy' ),
			'value' => get_user_meta( $user_id, 'academy_website_url', true )
		],
		'github_url' => [
			'label' => esc_html__( 'Github URL', 'academy' ),
			'value' => get_user_meta( $user_id, 'academy_github_url', true )
		],
		'facebook_url' => [
			'label' => esc_html__( 'Facebook URL', 'academy' ),
			'value' => get_user_meta( $user_id, 'academy_facebook_url', true )
		],
		'twitter_url' => [
			'label' => esc_html__( 'Twitter URL', 'academy' ),
			'value' => get_user_meta( $user_id, 'academy_twitter_url', true )
		],
		'linkedin_url' => [
			'label' => esc_html__( 'Linkedin URL', 'academy' ),
			'value' => get_user_meta( $user_id, 'academy_linkedin_url', true )
		],
		'linkedin_url' => [
			'label' => esc_html__( 'Linkedin URL', 'academy' ),
			'value' => get_user_meta( $user_id, 'academy_linkedin_url', true )
		],
	];

	\Academy\Helper::get_template(
		'frontend-dashboard/pages/my-profile.php', [
			'user_id' => $user_id,
			'user_data' => $user_data,
			'instructor_fields' => $instructor_fields,
			'student_fields' => $student_fields,
		]
	);
}

function academy_frontend_dashboard_enrolled_courses_page() {
	// get enroll courses
	$user_id = get_current_user_id();
	$enrolled_courses = \Academy\Helper::get_enrolled_courses_ids_by_user( $user_id );
	$pending_enrolled_courses = \Academy\Helper::get_pending_enrolled_courses_ids_by_user( $user_id );

	\Academy\Helper::get_template(
		'frontend-dashboard/pages/enrolled-courses.php',
		[
			'enrolled_courses' => $enrolled_courses,
			'pending_enrolled_courses' => $pending_enrolled_courses
		]
	);
}

function academy_frontend_dashboard_active_courses_page() {
	// get active courses
	$user_id = get_current_user_id();
	$enrolled_courses = \Academy\Helper::get_enrolled_courses_ids_by_user( $user_id );
	$completed_courses = \Academy\Helper::get_completed_courses_ids_by_user( $user_id );
	$active_courses    = array_diff( $enrolled_courses, $completed_courses );
	$pending_enrolled_courses = \Academy\Helper::get_pending_enrolled_courses_ids_by_user( $user_id );

	\Academy\Helper::get_template(
		'frontend-dashboard/pages/active-courses.php', [
			'active_courses' => $active_courses,
			'pending_enrolled_courses' => $pending_enrolled_courses
		]
	);
}

function academy_frontend_dashboard_completed_courses_page() {
	// get completed courses
	$user_id = get_current_user_id();
	$completed_courses = \Academy\Helper::get_completed_courses_ids_by_user( $user_id );
	$pending_enrolled_courses = \Academy\Helper::get_pending_enrolled_courses_ids_by_user( $user_id );
	\Academy\Helper::get_template(
		'frontend-dashboard/pages/completed-courses.php', [
			'completed_courses' => $completed_courses,
			'pending_enrolled_courses' => $pending_enrolled_courses
		]
	);
}

function academy_frontend_dashboard_reviews_page() {
	// get given reviews
	$user_id = get_current_user_id();
	$reviews = \Academy\Helper::get_reviews_by_user( $user_id );
	\Academy\Helper::get_template(
		'frontend-dashboard/pages/given-reviews.php',
		[
			'reviews' => $reviews
		]
	);
}

function academy_frontend_dashboard_received_reviews_page() {
	// get given reviews
	$user_id = get_current_user_id();
	$reviews = \Academy\Helper::get_reviews_by_instructor( $user_id );
	\Academy\Helper::get_template(
		'frontend-dashboard/pages/received-reviews.php',
		[
			'reviews' => $reviews
		]
	);
}

function academy_frontend_dashboard_purchase_history_page() {
	$user_id = get_current_user_id();
	$orders  = \Academy\Helper::get_orders_by_user_id( $user_id );
	$results = [];
	if ( is_array( $orders ) ) {
		foreach ( $orders as $order ) {
			$courses_order = \Academy\Helper::get_course_enrolled_ids_by_order_id( $order->ID );
			$courses       = [];
			if ( is_array( $courses_order ) ) {
				foreach ( $courses_order as $course ) {
					$courses[] = [
						'ID'        => $course['course_id'],
						'title'     => get_the_title( $course['course_id'] ),
						'permalink' => esc_url( get_the_permalink( $course['course_id'] ) ),
					];
				}
			}
			$wc_order  = wc_get_order( $order->ID );
			$price     = $wc_order->get_total();
			$status    = \Academy\Helper::order_status_context( $order->post_status );
			$results[] = [
				'ID'      => $order->ID,
				'courses' => $courses,
				'price'   => wc_price( $price, array( 'currency' => $wc_order->get_currency() ) ),
				'status'  => $status,
				'date'    => date_i18n( get_option( 'date_format' ), strtotime( $order->post_date ) ),
			];
		}//end foreach
	}//end if
	\Academy\Helper::get_template(
		'frontend-dashboard/pages/purchase-history.php', [
			'orders' => $results
		]
	);
}

function academy_frontend_dashboard_wishlist_page() {
	// get wishlist courses
	$user_id = get_current_user_id();
	$wishlist_courses = \Academy\Helper::get_wishlist_courses_by_user( $user_id, array( 'private', 'publish' ) );
	\Academy\Helper::get_template(
		'frontend-dashboard/pages/wishlist.php', [
			'wishlist_courses' => $wishlist_courses
		]
	);
}

function academy_frontend_dashboard_courses_page() {

	\Academy\Helper::get_template(
		'frontend-dashboard/pages/courses.php'
	);
}

function academy_frontend_dashboard_lessons_page() {

	\Academy\Helper::get_template(
		'frontend-dashboard/pages/lessons.php'
	);
}

function academy_frontend_dashboard_announcements_page() {
	\Academy\Helper::get_template(
		'frontend-dashboard/pages/announcements.php'
	);
}

function academy_frontend_dashboard_question_answer_page() {
	\Academy\Helper::get_template(
		'frontend-dashboard/pages/question-answer.php'
	);
}

function academy_frontend_dashboard_settings_page() {
	$user_id = get_current_user_id();
	\Academy\Helper::get_template(
		'frontend-dashboard/pages/settings.php', [
			'user_id' => $user_id
		]
	);
}

function academy_frontend_dashboard_reset_password_page() {
	\Academy\Helper::get_template(
		'frontend-dashboard/pages/reset-password.php'
	);
}

function academy_frontend_dashboard_withdrawal_page() {
	$user_id                     = get_current_user_id();
	$withdraw_history    = \Academy\Helper::get_withdraw_history_by_user_id( $user_id );
	$earning                     = (object) \Academy\Helper::get_earning_by_user_id( $user_id );
	$earning->withdraw_currency_symbol = '$';
	if ( \Academy\Helper::is_active_woocommerce() ) {
		$earning->withdraw_currency_symbol = \get_woocommerce_currency_symbol( 'USD' );
	}
	$withdraw_method_type = get_user_meta( $user_id, 'academy_instructor_withdraw_method_type', true );

	\Academy\Helper::get_template(
		'frontend-dashboard/pages/withdrawal.php', [
			'user_id' => $user_id,
			'withdraw_history' => $withdraw_history,
			'withdraw_method_type' => $withdraw_method_type,
			'earning' => $earning,
		]
	);
}

function academy_frontend_dashboard_withdraw_page() {
	$user_id = get_current_user_id();
	\Academy\Helper::get_template(
		'frontend-dashboard/pages/withdraw.php', [
			'user_id' => $user_id
		]
	);
}

function academy_frontend_dashboard_withdraw_echeck_page() {
	$user_id = get_current_user_id();
	\Academy\Helper::get_template(
		'frontend-dashboard/pages/withdraw-echeck.php', [
			'user_id' => $user_id
		]
	);
}

function academy_frontend_dashboard_withdraw_bank_page() {
	$user_id = get_current_user_id();
	\Academy\Helper::get_template(
		'frontend-dashboard/pages/withdraw-bank.php', [
			'user_id' => $user_id
		]
	);
}

