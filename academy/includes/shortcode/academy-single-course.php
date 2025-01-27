<?php
namespace Academy\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

class AcademySingleCourse {

	public function __construct() {
		add_shortcode('academy_single_course_addition_info', [
			$this,
			'single_course_additional_info',
		]);
		add_shortcode( 'academy_single_course_description', [
			$this,
			'single_course_description'
		]);
		add_shortcode('academy_single_course_curriculums', [
			$this,
			'single_course_curriculums',
		]);
		add_shortcode( 'academy_single_course_review_rating', [
			$this,
			'single_course_review_rating'
		]);
		add_shortcode( 'academy_single_course_review_form', [
			$this,
			'single_course_review_form'
		]);
		add_shortcode( 'academy_course_reviews', [
			$this,
			'single_course_user_display_reviews'
		]);
	}

	public function single_course_additional_info( $attributes, $content = '' ) {
		ob_start();

		$audience     = \Academy\Helper::string_to_array( get_post_meta( get_the_ID(), 'academy_course_audience', true ) );
		$requirements = \Academy\Helper::string_to_array( get_post_meta( get_the_ID(), 'academy_course_requirements', true ) );
		$materials    = \Academy\Helper::string_to_array( get_post_meta( get_the_ID(), 'academy_course_materials_included', true ) );
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

		\Academy\Helper::get_template(
			'single-course/additional-info.php',
			apply_filters(
				'academy/single_course_content_additional_info_args',
				[
					'tabs_nav'     => $tabs_nav,
					'tabs_content' => $tabs_content,
				]
			)
		);

		return apply_filters( 'academy/templates/shortcode/single_course_additional_info', ob_get_clean() );
	}

	public function single_course_description( $attributes, $content = '' ) {
		ob_start();

		\Academy\Helper::get_template(
			'single-course/description.php'
		);

		return apply_filters( 'academy/templates/shortcode/single_course_description', ob_get_clean() );
	}

	public function single_course_curriculums( $attributes, $content = '' ) {
		ob_start();

		$course_id = get_the_ID();
		$curriculums = \Academy\Helper::get_course_curriculum( $course_id, false );
		$topics_first_item_open_status = (bool) \Academy\Helper::get_settings( 'is_opened_course_single_first_topic', true );

		\Academy\Helper::get_template(
			'single-course/curriculums.php',
			array(
				'course_id'                      => $course_id,
				'curriculums'                    => $curriculums,
				'topics_first_item_open_status'  => $topics_first_item_open_status,
			)
		);

		return apply_filters( 'academy/templates/shortcode/single_course_curriculums', ob_get_clean() );
	}

	public function single_course_review_rating( $attributes, $content = '' ) {
		ob_start();
		$course_id = get_the_ID();
		if ( ! (bool) \Academy\Helper::get_settings( 'is_enabled_course_review', true ) || get_post_meta( $course_id, 'academy_is_disabled_course_review', true ) ) {
			return;
		}
		$rating = \Academy\Helper::get_course_rating( $course_id );
		\Academy\Helper::get_template(
			'single-course/feedback.php',
			array(
				'rating' => $rating
			)
		);

		return apply_filters( 'academy/templates/shortcode/single_course_review_rating', ob_get_clean() );
	}

	public function single_course_review_form( $attributes, $content = '' ) {
		global $current_user;
		ob_start();
		$course_id = get_the_ID();
		if ( post_password_required() || ! (bool) \Academy\Helper::get_settings( 'is_enabled_course_review', true ) || get_post_meta( $course_id, 'academy_is_disabled_course_review', true ) ) {
			return;
		}

		$usercomment = get_comments(array(
			'user_id' => $current_user->ID,
			'post_id' => $course_id,
		));

		if ( ! $usercomment && \Academy\Helper::is_enrolled( $course_id, $current_user->ID ) ) {
			\Academy\Helper::get_template( 'single-course/review-form.php' );
		}

		return apply_filters( 'academy/templates/shortcode/single_course_review_form', ob_get_clean() );
	}

	public function single_course_user_display_reviews( $attributes, $content = '' ) {
		ob_start();

		$course_id = get_the_ID();

		if ( post_password_required() ||
			! (bool) \Academy\Helper::get_settings( 'is_enabled_course_review', true ) ||
			get_post_meta( $course_id, 'academy_is_disabled_course_review', true ) ) {
			return '';
		}
		?>
	
		<div id="comments" class="academy-single-course__content-item academy-single-course__content-item--reviews">
			<?php
			$paged             = get_query_var( 'cpage', 1 );
			$comments_per_page = 5;

			$args = array(
				'post_id' => $course_id,
				'status'  => 'approve',
				'number'  => $comments_per_page,
				'paged'   => $paged,
			);

			$comment_query = new \WP_Comment_Query();
			$comments      = $comment_query->query( $args );

			if ( ! empty( $comments ) ) {
				?>
				<ol class="academy-review-list">
					<?php foreach ( $comments as $comment ) : ?>
						<li <?php comment_class( '', $comment->comment_ID ); ?> id="academy-review-<?php echo esc_attr( $comment->comment_ID ); ?>">
							<div id="comment-<?php echo esc_attr( $comment->comment_ID ); ?>" class="academy-review_container">
								<?php do_action( 'academy/templates/review_before', $comment ); ?>
								<div class="academy-review-thumnail">
									<?php
									echo get_avatar( $comment->comment_author_email, apply_filters( 'academy/review_gravatar_size', '80' ) );

									$rating = intval( get_comment_meta( $comment->comment_ID, 'academy_rating', true ) );
									?>
									<div class="academy-review__rating">
										<?php
										echo esc_html( $rating );
										echo wp_kses_post( \Academy\Helper::single_star_rating_generator( $rating ) );
										?>
									</div>
								</div>
								<div class="academy-review-content">
									<?php
									if ( '0' === $comment->comment_approved ) {
										?>
										<p class="academy-review-meta">
											<em class="academy-review-meta__awaiting-approval">
												<?php esc_html_e( 'Your review is awaiting approval', 'academy' ); ?>
											</em>
										</p>
										<?php
									} else {
										?>
										<p class="academy-review-meta">
											<strong class="academy-review-meta__author">
												<?php echo esc_html( $comment->comment_author ); ?>
											</strong>
											<time class="academy-review-meta__published-date" datetime="<?php echo esc_attr( get_comment_date( 'c', $comment ) ); ?>">
												<?php echo esc_html( get_comment_date( \Academy\Helper::get_date_format(), $comment ) ); ?>
											</time>
										</p>
										<?php
									}
									?>
									<div class="academy-review-description">
										<?php comment_text( $comment ); ?>
									</div>
								</div>
							</div>
						</li>
					<?php endforeach; ?>
				</ol>
				<?php
			} else {
				echo '<p>' . esc_html__( 'No reviews yet. Be the first to leave one!', 'academy' ) . '</p>';
			}//end if

			$total_comments = get_comments( array(
				'post_id' => $course_id,
				'status'  => 'approve',
				'count'   => true,
			) );

			$max_pages = ceil( (int) $total_comments / $comments_per_page );

			the_comments_pagination(
				array(
					'total'     => $max_pages,
					'current'   => $paged,
					'mid_size'  => 1,
					'prev_text' => sprintf(
						'<span class="nav-prev-text">%s</span>',
						esc_html__( 'Prev', 'academy' )
					),
					'next_text' => sprintf(
						'<span class="nav-next-text">%s</span>',
						esc_html__( 'Next', 'academy' )
					),
				)
			);

		if ( ! comments_open( $course_id ) ) {
			echo '<p class="academy-no-reviews">' . esc_html__( 'Reviews are closed.', 'academy' ) . '</p>';
		}
		?>
		</div> <!-- End #comments -->
	
		<?php
		return apply_filters( 'academy/templates/shortcode/single_course_user_rating', ob_get_clean() );
	}

}
