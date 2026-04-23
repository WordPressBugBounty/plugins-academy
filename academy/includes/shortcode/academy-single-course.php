<?php
namespace Academy\Shortcode;

use Academy\Helper;

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
		add_shortcode( 'academy_course_featured_image', [
			$this,
			'single_course_featured_image'
		]);
		add_shortcode('academy_single_course_attachment_files', [
			$this,
			'single_course_attachment_file',
		] );
	}

	public function single_course_additional_info( $attributes, $content = '' ) {
		ob_start();

		$benefits     = \Academy\Helper::string_to_array( get_post_meta( get_the_ID(), 'academy_course_benefits', true ) );
		$audience     = \Academy\Helper::string_to_array( get_post_meta( get_the_ID(), 'academy_course_audience', true ) );
		$requirements = \Academy\Helper::string_to_array( get_post_meta( get_the_ID(), 'academy_course_requirements', true ) );
		$materials    = \Academy\Helper::string_to_array( get_post_meta( get_the_ID(), 'academy_course_materials_included', true ) );
		$tabs_nav     = [];
		$tabs_content = [];
		if ( is_array( $benefits ) && count( $benefits ) > 0 ) {
			\Academy\Helper::get_template(
				'single-course/benefits.php',
				apply_filters(
					'academy/single_course_content_benefits_args',
					[
						'benefits' => $benefits,
					]
				)
			);
		}
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
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( shortcode_atts( [ 'course_id' => get_the_ID() ], $attributes ) );
		ob_start();
		$course_id = isset( $course_id ) ? $course_id : get_the_ID();
		if ( ! (bool) \Academy\Helper::get_settings( 'is_enabled_course_review', true ) || get_post_meta( $course_id, 'academy_is_disabled_course_review', true ) ) {
			return;
		}

		$usercomment = get_comments(array(
			'user_id' => $current_user->ID,
			'post_id' => $course_id,
		));
		$completed_percent = (float) \Academy\Helper::get_percentage_of_completed_topics_by_student_and_course_id( $current_user->ID, $course_id );
		$required_percent = (float) \Academy\Helper::get_settings( 'minimum_course_completion_on_review', 0 );
		if ( ! $usercomment && \Academy\Helper::is_enrolled( $course_id, $current_user->ID ) && ( $completed_percent >= $required_percent ) ) {
			\Academy\Helper::get_template( 'single-course/review-form.php' );
		}

		return apply_filters( 'academy/templates/shortcode/single_course_review_form', ob_get_clean() );
	}

	public function single_course_user_display_reviews( $attributes, $content = '' ) {
		ob_start();

		$course_id = get_the_ID();
		$completed_percent = (float) \Academy\Helper::get_percentage_of_completed_topics_by_student_and_course_id( get_current_user_id(), $course_id );
		$required_percent = (float) \Academy\Helper::get_settings( 'minimum_course_completion_on_review', 0 );
		if ( ! (bool) \Academy\Helper::get_settings( 'is_enabled_course_review', true ) ||
			get_post_meta( $course_id, 'academy_is_disabled_course_review', true ) ||
			( $completed_percent < $required_percent && $completed_percent !== $required_percent ) ) {
			return '';
		}
		?>
		<div id="comments" class="academy-single-course__content-item academy-single-course__content-item--reviews">
			<?php
			$paged             = get_query_var( 'cpage', 1 );
			$comments_per_page = 5;

			$args = array(
				'post_id'      => (int) $course_id,
				'status'       => 'approve',
				'type'         => 'academy_courses',
				'number'       => $comments_per_page,
				'paged'        => $paged,
			);

			$comment_query = new \WP_Comment_Query();
			$comments      = $comment_query->query( $args );
			$current_user_id = get_current_user_id();
			$edit_permission = \Academy\Helper::get_settings('is_enable_course_review_edit', false );
			if ( ! empty( $comments ) ) {
				?>
				<ol class="academy-review-list">
					<?php foreach ( $comments as $comment ) : 
						$comment_id = $comment->comment_ID;	?>
						<li <?php comment_class('', $comment_id ); ?>
							id="academy-review-<?php echo esc_attr( $comment_id ); ?>">
							<div id="comment-<?php echo esc_attr( $comment_id ); ?>" class="academy-review_container">
								<?php do_action('academy/templates/review_before', $comment); ?>
								
								<div class="academy-review_container__top">
									<div class="academy-review_container__thumbnail">
										<?php
										echo get_avatar(
											$comment->comment_author_email,
											apply_filters('academy/review_gravatar_size', '80')
										);
										?>
									</div>

									<div class="academy-review_container__profile">
										<?php
										if ('0' === $comment->comment_approved) {
											?>
											<p class="academy-review-meta">
												<em class="academy-review-meta__awaiting-approval">
													<?php esc_html_e('Your review is awaiting approval', 'academy'); ?>
												</em>
											</p>
											<?php
										} else {
											$rating = intval( get_comment_meta( $comment_id, 'academy_rating', true ) );
											?>

											<div class="academy-column-items">
												<p class="academy-column-items">
													<strong class="academy-review_container__author">
														<?php echo esc_html($comment->comment_author); ?>
													</strong>
													<time class="academy-review_container__published-date"
														datetime="<?php echo esc_attr(get_comment_date('c', $comment)); ?>">
														<?php echo esc_html(get_comment_date(\Academy\Helper::get_date_format(), $comment)); ?>
													</time>
												</p>

												<div class="academy-review_container__rating">
													<span class="academy-review_container__rating-label">
														<?php echo esc_html__('Rating: ', 'academy'); ?>
													</span>
													<span class="academy-review_container__rating-star">
														<?php
															echo esc_html($rating);
															echo wp_kses_post(\Academy\Helper::single_star_rating_generator($rating));
														?>
													</span>
												</div>
											</div>
											<?php
											if ( $edit_permission && (int) $comment->user_id === $current_user_id ) {
												?>
												<button class="academy-review_container__edit-btn academy-review-edit-btn"
													data-comment-id="<?php echo esc_attr( $comment_id ); ?>"
													data-comment-rating="<?php echo esc_attr( $rating ); ?>"
													data-comment-content="<?php echo esc_attr( $comment->comment_content ); ?>">

													<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
														<path d="M9.1665 1.6665H7.49984C3.33317 1.6665 1.6665 3.33317 1.6665 7.49984V12.4998C1.6665 16.6665 3.33317 18.3332 7.49984 18.3332H12.4998C16.6665 18.3332 18.3332 16.6665 18.3332 12.4998V10.8332" stroke="#7B68EE" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
														<path d="M13.3666 2.51688L6.7999 9.08354C6.5499 9.33354 6.2999 9.82521 6.2499 10.1835L5.89157 12.6919C5.75823 13.6002 6.3999 14.2335 7.30823 14.1085L9.81657 13.7502C10.1666 13.7002 10.6582 13.4502 10.9166 13.2002L17.4832 6.63354C18.6166 5.50021 19.1499 4.18354 17.4832 2.51688C15.8166 0.850211 14.4999 1.38354 13.3666 2.51688Z" stroke="#7B68EE" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
														<path d="M12.4248 3.4585C12.9831 5.45016 14.5415 7.0085 16.5415 7.57516" stroke="#7B68EE" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
													</svg>
												</button>
												<?php
											}
										}
										?>
									</div>
								</div>

								<div class="academy-review-description">
									<?php comment_text( $comment_id ); ?>
								</div>

								<div class="academy-review-content">
									<?php
									if (
										$edit_permission && (int) $comment->user_id === $current_user_id
									):
										?>

										<div id="academy-review-edit-form" class="academy-review-edit-form academy-review-edit-form academy-mt-6" style="display:none;">
											<span>
												<?php echo esc_html__('Select Rating : ', 'academy'); ?>
											</span>
											<select id="academy-review-edit-rating" name="academy_review_rating">
												<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
													<option value="<?php echo esc_attr( $i ); ?>" 
														<?php selected( $rating, $i ); ?>>
														<?php echo esc_html( $i ) . ' ' . esc_html__( 'Star', 'academy' ); ?>
													</option>
												<?php endfor; ?>
											</select>

											<textarea id="academy-review-edit-content"
												class="academy-review-edit-form__textarea"></textarea>
											<input type="hidden" id="academy-review-edit-id">
											<div class="academy-review_container__update">
												<button id="academy-review-update-btn"
													class="academy-btn academy-btn--bg-purple academy-btn--xs">
													<?php esc_html_e('Update Review', 'academy'); ?>
												</button>
												<button type="button" id="academy-review-cancel-btn"
													class="academy-btn academy-btn--preset-gray academy-btn--xs">
													<?php esc_html_e('Cancel', 'academy'); ?>
												</button>
											</div>
										</div>
									<?php endif; ?>
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

	public function single_course_featured_image( $attributes, $content = '' ) {
		$atts = shortcode_atts(
			[
				'course_id' => get_the_ID()
			],
			$attributes
		);
		$course_id = absint( $atts['course_id'] );

		$video_output = self::get_course_preview_videos( $course_id );

		if ( ! empty( $video_output ) ) {
			return $video_output;
		}

		if ( has_post_thumbnail() ) {
			$featured = get_the_post_thumbnail( $course_id, '', [
				'class' => 'academy-default-featured-image',
				'loading' => 'lazy'
			] );
			return '<div class="academy-courses-featured-image">' . $featured . '</div>';
		}

		$image_url = ACADEMY_ASSETS_URI . 'images/thumbnail-placeholder.png';
		return sprintf(
			'<img src="%s" alt="%s" class="academy-default-featured-image" />',
			esc_url( $image_url ),
			esc_attr__( 'Academy Default Featured Image', 'academy' )
		);
	}

	public static function get_course_preview_videos( $id ) {
		$output      = '';
		$intro_video = get_post_meta( $id, 'academy_course_intro_video', true );
		if ( $intro_video && is_array( $intro_video ) && count( $intro_video ) > 1 && ! empty( $intro_video[1] ) ) {
			$type = $intro_video[0];
			if ( 'html5' === $type ) {
				$attachment_id = (int) $intro_video[1];
				$att_url       = wp_get_attachment_url( $attachment_id );
				$thumb_id      = get_post_thumbnail_id( $attachment_id );
				$thumb_url     = wp_get_attachment_url( $thumb_id );
				$output       .= sprintf(
					'<video class="academy-plyr" id="academyPlayer" playsinline controls data-poster="%s">
                    <source src="%s" type="video/mp4" />
                </video>',
					esc_url( $thumb_url ),
					esc_url( $att_url )
				);
			} elseif ( 'embedded' === $type ) {
				$embed = Helper::parse_embedded_url( $intro_video[1] );
				$output .= sprintf( '<div class="academy-embed-responsive"><iframe class="academy-embed-responsive-item" src="%s" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe></div>', esc_url( $embed['url'] ) );
			} elseif ( 'youtube' === $type || 'vimeo' === $type ) {
				$embed = Helper::get_basic_url_to_embed_url( $intro_video[1] );
				$output .= sprintf( '<div class="academy-plyr plyr__video-embed" id="academyPlayer"><iframe src="%s" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe></div>', esc_url( $embed['url'] ) );
			} elseif ( 'shortcode' === $type ) {
				$output .= do_shortcode( $intro_video[1] );
			} else {
				$embed = Helper::get_basic_url_to_embed_url( $intro_video[1] );
				$output .= sprintf( '<div class="academy-embed-responsive academy-embed-responsive-16by9"><iframe class="academy-embed-responsive-item" src="%s" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe></div>', esc_url( $embed['url'] ) );
			}//end if
		}//end if
		return $output;
	}

	public function single_course_attachment_file( $attributes, $content = '' ) {

		$course_id = get_the_ID();

		if ( ! \Academy\Helper::is_enrolled( $course_id, get_current_user_id() ) || ! \Academy\Helper::is_active_academy_pro() ) {
			return '';
		}

		$attachments = get_post_meta( $course_id, 'academy_course_attachments', true );

		ob_start();

		\AcademyPro\Helper::get_template(
			'multimedia-attachment/attachment-file.php',
			[
				'attachments' => $attachments,
			]
		);

		$output = ob_get_clean();

		return apply_filters(
			'academy/templates/shortcode/single_course_attachments',
			$output
		);
	}
}
