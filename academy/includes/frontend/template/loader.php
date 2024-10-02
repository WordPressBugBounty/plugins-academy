<?php
namespace  Academy\Frontend\Template;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy;

class Loader {
	public static function init() {
		$self = new self();
		add_filter( 'template_include', array( $self, 'template_loader' ) );
		add_filter( 'template_include', array( $self, 'load_course_curriculums_template' ), 30 );
		add_filter( 'template_include', array( $self, 'load_author_profile_template' ) );
		add_filter( 'comments_template', array( $self, 'load_comments_template' ) );
		add_filter( 'body_class', array( $self, 'add_body_custom_class' ) );
	}

	public function template_loader( $template ) {
		if ( is_embed() ) {
			return $template;
		}

		$default_file = $this->get_template_loader_default_file();

		if ( $default_file ) {
			/**
			 * Filter hook to choose which files to find before Academy does it's own logic.
			 *
			 * @var array
			 */
			$search_files = $this->get_template_loader_files( $default_file );
			$template     = locate_template( $search_files );

			if ( ! $template ) {
				if ( false !== strpos( $default_file, 'academy_courses_category' ) || false !== strpos( $default_file, 'academy_courses_tag' ) ) {
					$cs_template = str_replace( '_', '-', $default_file );
					$template    = \Academy\Helper::plugin_path() . 'templates/' . $cs_template;
				} else {
					$template = \Academy\Helper::plugin_path() . 'templates/' . $default_file;
				}
			}
		}
		return $template;
	}

	/**
	 * Get the default filename for a template.
	 *
	 * @return string
	 */
	private function get_template_loader_default_file() {
		if ( is_singular( 'academy_courses' ) ) {
			$default_file = 'single-course.php';
		} elseif ( \Academy\Helper::is_course_taxonomy() ) {
			if ( is_tax( 'academy_courses_category' ) ) {
				$default_file = 'taxonomy-course-category.php';
			} elseif ( is_tax( 'academy_courses_tag' ) ) {
				$default_file = 'taxonomy-course-tag.php';
			} else {
				$default_file = 'archive-course.php';
			}
		} elseif ( is_post_type_archive( 'academy_courses' ) ) {
			$default_file = 'archive-course.php';
		} else {
			$default_file = '';
		}
		return $default_file;
	}

	private function get_template_loader_files( $default_file ) {
		$templates   = apply_filters( 'academy\frontend\template\loader_files', array(), $default_file );
		$templates[] = 'academy.php';

		if ( is_page_template() ) {
			$page_template = get_page_template_slug();

			if ( $page_template ) {
				$validated_file = validate_file( $page_template );
				if ( 0 === $validated_file ) {
					$templates[] = $page_template;
				} else {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( "Academy: Unable to validate template path: \"$page_template\". Error Code: $validated_file." );
				}
			}
		}

		if ( is_singular( 'academy_courses' ) ) {
			$object       = get_queried_object();
			$name_decoded = urldecode( $object->post_name );
			if ( $name_decoded !== $object->post_name ) {
				$templates[] = "single-course-{$name_decoded}.php";
			}
			$templates[] = "single-course-{$object->post_name}.php";
		}

		if ( \Academy\Helper::is_course_taxonomy() ) {
			$object = get_queried_object();
			if ( is_tax( 'academy_courses_category' ) ) {
				$templates[] = 'taxonomy-course-category-' . $object->slug . '.php';
				$templates[] = \Academy\Helper::template_path() . 'taxonomy-course-category-' . $object->slug . '.php';
				$templates[] = 'taxonomy-course-category.php';
				$templates[] = \Academy\Helper::template_path() . 'taxonomy-course-category.php';
			} elseif ( is_tax( 'academy_courses_tag' ) ) {
				$templates[] = 'taxonomy-course-tag-' . $object->slug . '.php';
				$templates[] = \Academy\Helper::template_path() . 'taxonomy-course-tag-' . $object->slug . '.php';
				$templates[] = 'taxonomy-course-tag.php';
				$templates[] = \Academy\Helper::template_path() . 'taxonomy-course-tag.php';
			}
			$cs_default  = str_replace( '_', '-', $default_file );
			$templates[] = $cs_default;
		}

		$templates[] = $default_file;
		if ( isset( $cs_default ) ) {
			$templates[] = \Academy\Helper::template_path() . $cs_default;
		}
		$templates[] = \Academy\Helper::template_path() . $default_file;

		return array_unique( $templates );
	}

	/**
	 * Load comments template.
	 *
	 * @param string $template template to load.
	 * @return string
	 */
	public function load_comments_template( $template ) {
		if ( get_post_type() !== 'academy_courses' ) {
			return $template;
		}

		$check_dirs = array(
			trailingslashit( get_stylesheet_directory() ) . Academy\Helper::template_path(),
			trailingslashit( get_template_directory() ) . Academy\Helper::template_path(),
			trailingslashit( get_stylesheet_directory() ),
			trailingslashit( get_template_directory() ),
			trailingslashit( Academy\Helper::plugin_path() ) . 'templates/',
		);

		if ( ACADEMY_TEMPLATE_DEBUG_MODE ) {
			$check_dirs = array( array_pop( $check_dirs ) );
		}

		foreach ( $check_dirs as $dir ) {
			if ( file_exists( trailingslashit( $dir ) . 'single-course-reviews.php' ) ) {
				return trailingslashit( $dir ) . 'single-course-reviews.php';
			}
		}
	}

	public function load_course_curriculums_template( $template ) {
		if ( ( ! empty( get_query_var( 'name' ) ) && ! empty( get_query_var( 'curriculum_type' ) ) ) || get_query_var( 'post_type' ) === 'academy_courses' && ( 'curriculums' === get_query_var( 'source' ) || 'lessons' === get_query_var( 'source' ) ) ) {
			return \Academy\Helper::plugin_path() . 'templates/single-course-curriculums.php';
		}
		return $template;
	}

	public function load_author_profile_template( $template ) {
		global $wp_query;
		if ( ! empty( $wp_query->query['author_name'] ) && Academy\Helper::get_settings( 'is_show_public_profile' ) ) {
			$user = get_user_by( 'login', $wp_query->query['author_name'] );
			if ( $user ) {
				if ( current( $user->roles ) === 'academy_instructor' || current( $user->roles ) === 'administrator' ) {
					return \Academy\Helper::plugin_path() . 'templates/instructor-public-profile.php';
				}
			}
		}

		return $template;
	}

	public function add_body_custom_class( $classes ) {
		global $wp_query;
		if ( get_query_var( 'post_type' ) === 'academy_courses' && ( get_query_var( 'source' ) === 'lessons' || get_query_var( 'source' ) === 'curriculums' ) ) {
			$theme_header_footer = \Academy\Helper::get_settings( 'is_enabled_lessons_theme_header_footer', false );
			$custom_classes = array( 'academy-course-single-lessons' );
			if ( $theme_header_footer ) {
				$custom_classes[] = 'academy-course-single-lessons--allow-theme-header-footer';
			}
			return array_merge( $classes, $custom_classes );
		} elseif ( ! empty( $wp_query->query['author_name'] ) && Academy\Helper::get_settings( 'is_show_public_profile' ) ) {
			return array_merge( $classes, array( 'academy-instructor-public-profile' ) );
		}
		return $classes;
	}
}
