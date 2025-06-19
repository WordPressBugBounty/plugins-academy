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
		add_action( 'init', [ $this, 'register_block_styles' ] );
		add_filter( 'render_block', [ $this, 'custom_featured_image_with_default' ], 10, 2 ); // Add the filter here
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
						$q->set( 'author', $q->query['author_name'] );
						$q->set( 'posts_per_page', $per_page );
						$q->set( 'post_type', 'academy_courses' );
					}
				}
			} elseif ( is_post_type_archive( 'academy_courses' ) ) {
				$paged = ( get_query_var( 'paged' ) ) ? absint( get_query_var( 'paged' ) ) : 1;
				$orderby = ( get_query_var( 'orderby' ) ) ? get_query_var( 'orderby' ) : Academy\Helper::get_settings( 'course_archive_courses_order' );
				$q->set( 'post_type', apply_filters( 'academy/course_archive_post_types', array( 'academy_courses' ) ) );
				$q->set( 'posts_per_page', $per_page );
				$q->set( 'paged', $paged );
				if ( 'name' === $orderby ) {
					$q->set( 'orderby', 'title' );
					$q->set( 'order', 'ASC' );
				} else {
					$q->set( 'orderby', $orderby );
				}
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
		$items = [ 'lesson', 'quiz', 'assignment', 'booking', 'zoom', 'meeting' ];
		if ( class_exists( 'RankMath' ) &&
			! in_array( get_query_var( 'curriculum_type' ), $items, true )
		) {
			$page_id = (int) get_queried_object_id();
			$course_page = (int) \Academy\Helper::get_settings( 'course_page' );
			if ( $page_id === $course_page ) {
				return;
			}
		} elseif ( get_query_var( 'name' ) && get_query_var( 'curriculum_type' ) ) {
			if ( 'lesson' === get_query_var( 'curriculum_type' ) ) {
				$lesson = helper::get_lesson_by_slug( get_query_var( 'name' ) );
				if ( $lesson ) {
					return $lesson['lesson_title'];
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
	public function register_block_styles() {
		// Define block styles
		$block_custom_styles = [
			[
				'block' => 'core/columns',
				'styles' => [
					[
						'name'  => 'academylms',
						'label' => __( 'AcademyLms', 'academy' ),
					],

				],
			],
			[
				'block' => 'core/column',
				'styles' => [
					[
						'name'  => 'academylmssticky',
						'label' => __( 'AcademyLms Sticky', 'academy' ),
					],

				],
			],
			// Add more blocks and styles as needed
		];

		foreach ( $block_custom_styles as $block_custom_style ) {
			foreach ( $block_custom_style['styles'] as $style ) {
				register_block_style( $block_custom_style['block'], $style );
			}
		}
	}

	public function custom_featured_image_with_default( $academy_fse_block_content, $block ) {
		if ( 'core/post-featured-image' === $block['blockName'] ) {
			if ( ! has_post_thumbnail() && is_singular( 'academy_courses' ) ) {
				$video_output = self::get_course_fsc_preview_videos( get_the_ID() );
				if ( empty( $video_output ) ) {
					$academy_fse_image_url = plugins_url( 'academy/assets/images/thumbnail-placeholder.png' );
					$academy_fse_block_content = '<img src="' . esc_url( $academy_fse_image_url ) . '" alt="' . esc_attr__( 'Default Featured Image', 'academy' ) . '" class="default-feture-image" />';
				} else {
					// If a video exists, use the video output
					$academy_fse_block_content = $video_output;
				}
			}
		}
		return $academy_fse_block_content;
	}

	public static function get_course_fsc_preview_videos( $id ) {
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
				$output .= sprintf( '<div class="academy-embed-responsive academy-embed-responsive-16by9"><iframe class="academy-embed-responsive-item" src="%s" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe></div>', esc_url( $embed['url'] ) );
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


}
