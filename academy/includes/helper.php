<?php

namespace Academy;

use Academy;
use DateInterval;
use DateTime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helper {

	use Traits\Courses;
	use Traits\Lessons;
	use Traits\Instructor;
	use Traits\Student;
	use Traits\Earning;
	use Traits\Withdrawals;

	public static function get_time() {
		return time() + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
	}

	/**
	 * List of admin menu
	 */
	public static function get_admin_menu_list() {
		$menu                                     = [];
		$menu[ ACADEMY_PLUGIN_SLUG ]              = [
			'parent_slug' => ACADEMY_PLUGIN_SLUG,
			'title'       => __( 'Dashboard', 'academy' ),
			'capability'  => 'manage_options',
		];
		$menu[ ACADEMY_PLUGIN_SLUG . '-courses' ] = [
			'parent_slug' => ACADEMY_PLUGIN_SLUG,
			'title'       => __( 'Courses', 'academy' ),
			'capability'  => 'manage_options',
			'sub_items'   => [
				[
					'slug'  => '',
					'title' => __( 'All Courses', 'academy' ),
				],
				[
					'slug'  => 'category',
					'title' => __( 'Category', 'academy' ),
				],
				[
					'slug'       => 'tags',
					'title'      => __( 'Tags', 'academy' ),
				],
			],
		];
		$menu[ ACADEMY_PLUGIN_SLUG . '-lessons' ] = [
			'parent_slug' => ACADEMY_PLUGIN_SLUG,
			'title'       => __( 'Lessons', 'academy' ),
			'capability'  => 'manage_options',
		];
		if ( self::get_addon_active_status( 'quizzes' ) ) {
			$menu[ ACADEMY_PLUGIN_SLUG . '-quizzes' ] = [
				'parent_slug' => ACADEMY_PLUGIN_SLUG,
				'title'       => __( 'Quizzes', 'academy' ),
				'capability'  => 'manage_options',
				'sub_items'   => [
					[
						'slug'  => '',
						'title' => __( 'All Quizzes', 'academy' ),
					],
					[
						'slug'  => 'attempts',
						'title' => __( 'Quiz Attempts', 'academy' ),
					]
				]
			];
		}
		if ( self::is_active_academy_pro() ) {
			if ( self::get_addon_active_status( 'meeting' ) ) {
				$menu[ ACADEMY_PLUGIN_SLUG . '-meeting' ] = [
					'parent_slug' => ACADEMY_PLUGIN_SLUG,
					'title'       => __( 'Meeting', 'academy' ),
					'capability'  => 'manage_options',
				];
			}
			if ( self::get_addon_active_status( 'tutor-booking' ) ) {
				$menu[ ACADEMY_PLUGIN_SLUG . '-tutor-booking' ] = [
					'parent_slug' => ACADEMY_PLUGIN_SLUG,
					'title'       => __( 'Tutor Bookings', 'academy' ),
					'capability'  => 'manage_options',
					'sub_items'   => [
						[
							'slug'  => '',
							'title' => __( 'All Bookings', 'academy' ),
						],
						[
							'slug'  => 'category',
							'title' => __( 'Category', 'academy' ),
						],
						[
							'slug'  => 'tags',
							'title' => __( 'Tags', 'academy' ),
						],
						[
							'slug'  => 'booked',
							'title' => __( 'Booked Schedules', 'academy' ),
						]
					]
				];
			}//end if
			if ( self::get_addon_active_status( 'assignments' ) ) {
				$menu[ ACADEMY_PLUGIN_SLUG . '-assignments' ] = [
					'parent_slug' => ACADEMY_PLUGIN_SLUG,
					'title'       => __( 'Assignments', 'academy' ),
					'capability'  => 'manage_options',
					'sub_items'   => [
						[
							'slug'  => '',
							'title' => __( 'All Assign.', 'academy' ),
						],
						[
							'slug'  => 'submitted-assignments',
							'title' => __( 'Submitted Assign.', 'academy' ),
						]
					]
				];
			}
			if ( self::get_addon_active_status( 'course-bundle' ) && self::is_active_woocommerce() ) {
				$menu[ ACADEMY_PLUGIN_SLUG . '-course-bundle' ] = [
					'parent_slug' => ACADEMY_PLUGIN_SLUG,
					'title'       => __( 'Course Bundle', 'academy' ),
					'capability'  => 'manage_options',
				];
			}
			if ( self::get_addon_active_status( 'google-classroom' ) ) {
				$menu[ ACADEMY_PLUGIN_SLUG . '-google-classroom' ] = [
					'parent_slug' => ACADEMY_PLUGIN_SLUG,
					'title'       => __( 'Google Classroom', 'academy' ),
					'capability'  => 'manage_options'
				];
			}
		}//end if
		$menu[ ACADEMY_PLUGIN_SLUG . '-announcements' ]   = [
			'parent_slug' => ACADEMY_PLUGIN_SLUG,
			'title'       => __( 'Announcements', 'academy' ),
			'capability'  => 'manage_options',
		];
		$menu[ ACADEMY_PLUGIN_SLUG . '-question_answer' ] = [
			'parent_slug' => ACADEMY_PLUGIN_SLUG,
			'title'       => __( 'Question & Answer', 'academy' ),
			'capability'  => 'manage_options',
		];
		if ( self::is_active_academy_pro() && self::get_addon_active_status( 'grade_book' ) ) {
			$menu[ ACADEMY_PLUGIN_SLUG . '-grade-book' ] = [
				'parent_slug' => ACADEMY_PLUGIN_SLUG,
				'title'       => __( 'GradeBook', 'academy' ),
				'capability'  => 'manage_options',
				'sub_items'   => [
					[
						'slug'  => '',
						'title' => __( 'Student Grades', 'academy' ),
					],
					[
						'slug'       => 'builder',
						'title'      => __( 'Grade Builder', 'academy' ),
					],
				],
			];
		}
		if ( self::get_addon_active_status( 'multi_instructor' ) ) {
			$menu[ ACADEMY_PLUGIN_SLUG . '-withdraw' ] = [
				'parent_slug' => ACADEMY_PLUGIN_SLUG,
				'title'       => __( 'Withdraw Requests', 'academy' ),
				'capability'  => 'manage_options',
			];
		}
		if ( self::get_addon_active_status( 'webhooks' ) ) {
			$menu[ ACADEMY_PLUGIN_SLUG . '-webhooks' ] = [
				'parent_slug' => ACADEMY_PLUGIN_SLUG,
				'title'       => __( 'Webhooks', 'academy' ),
				'capability'  => 'manage_options',
			];
		}
		$menu[ ACADEMY_PLUGIN_SLUG . '-instructors' ] = [
			'parent_slug' => ACADEMY_PLUGIN_SLUG,
			'title'       => __( 'Instructors', 'academy' ),
			'capability'  => 'manage_options',
		];
		$menu[ ACADEMY_PLUGIN_SLUG . '-students' ]    = [
			'parent_slug' => ACADEMY_PLUGIN_SLUG,
			'title'       => __( 'Students', 'academy' ),
			'capability'  => 'manage_options',
		];
		if ( self::get_addon_active_status( 'certificates' ) ) {
			$menu[ ACADEMY_PLUGIN_SLUG . '-certificates' ]    = [
				'parent_slug' => ACADEMY_PLUGIN_SLUG,
				'title'       => __( 'Certificates', 'academy' ),
				'capability'  => 'manage_options',
			];
		}
		$menu[ ACADEMY_PLUGIN_SLUG . '-addons' ]      = [
			'parent_slug' => ACADEMY_PLUGIN_SLUG,
			'title'       => __( 'Add-ons', 'academy' ),
			'capability'  => 'manage_options',
		];
		$menu[ ACADEMY_PLUGIN_SLUG . '-tools' ]       = [
			'parent_slug' => ACADEMY_PLUGIN_SLUG,
			'title'       => __( 'Tools', 'academy' ),
			'capability'  => 'manage_options',
		];
		$menu[ ACADEMY_PLUGIN_SLUG . '-settings' ]    = [
			'parent_slug' => ACADEMY_PLUGIN_SLUG,
			'title'       => __( 'Settings', 'academy' ),
			'capability'  => 'manage_options',
		];

		// Check Pro active or not
		if ( ! self::is_active_academy_pro() ) {
			$menu[ ACADEMY_PLUGIN_SLUG . '-get-pro' ] = [
				'parent_slug' => ACADEMY_PLUGIN_SLUG,
				'title'       => __( '<span class="dashicons dashicons-awards academy-blue-color"></span> Get Pro', 'academy' ),
				'capability'  => 'manage_options',
			];
		}

		return apply_filters( 'academy/admin_menu_list', $menu );
	}

	public static function get_addon_active_status( $addon_name, $is_pro = false ) {
		global $academy_addons;
		if ( $is_pro && ! self::is_active_academy_pro() ) {
			return false;
		}
		if ( isset( $academy_addons->{$addon_name} ) ) {
			return (bool) $academy_addons->{$addon_name};
		}

		return false;
	}

	public static function is_active_academy_pro() {
		$academy_pro = 'academy-pro/academy-pro.php';

		return self::is_plugin_active( $academy_pro );
	}

	public static function is_plugin_active( $basename ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			include_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $basename );
	}

	public static function monetization_engine() {
		return self::get_settings( 'monetization_engine' );
	}

	public static function get_settings( $key, $default = null ) {
		global $academy_settings;

		if ( isset( $academy_settings->{$key} ) ) {
			return $academy_settings->{$key};
		}

		return $default;
	}

	/**
	 * Check current screen is academy admin page or not
	 *
	 * @return bool
	 */
	public static function is_academy_admin_page() {
		if ( is_admin() ) {
			$screen = get_current_screen();

			return self::plugin_page_hook_suffix( $screen->base );
		}

		return false;
	}

	/**
	 * Check Supported Post type for admin page and plugin main settings page
	 *
	 * @param string $hook
	 *
	 * @return bool
	 */
	public static function plugin_page_hook_suffix( $hook ) {
		if ( strpos( $hook, '_page_' . ACADEMY_PLUGIN_SLUG ) !== false ) {
			return true;
		}

		return false;
	}

	public static function is_dev_mode_enable() {
		$environment = wp_get_environment_type();
		if ( 'local' === $environment || 'development' === $environment ) {
			return true;
		}
	}

	public static function get_customizer_settings( $key, $default = null ) {
		$customizer_settings = get_option( 'academy_customizer_settings' );
		if ( isset( $customizer_settings[ $key ] ) ) {
			return $customizer_settings[ $key ];
		}

		return $default;
	}

	public static function get_customizer_style_settings( $key, $default = null ) {
		$customizer_settings = get_option( 'academy_customizer_style_settings' );
		if ( isset( $customizer_settings[ $key ] ) ) {
			return $customizer_settings[ $key ];
		}

		return $default;
	}

	public static function get_column_size( $number_of_column ) {
		return ceil( 12 / $number_of_column );
	}

	public static function get_responsive_column( $columns ) {
		if ( is_array( $columns ) ) {
			$device  = array(
				'desktop' => 'lg',
				'tablet'  => 'md',
				'mobile'  => 'sm',
			);
			$classes = '';
			foreach ( $columns as $mode => $column ) {
				if ( $column ) {
					$classes .= ' academy-col-' . $device[ $mode ] . '-' . ceil( 12 / $column );
				}
			}

			return ltrim( $classes );
		}

		return '';
	}

	/**
	 * Get template part (for templates like the course-loop).
	 *
	 * ACADEMY_TEMPLATE_DEBUG_MODE will prevent overrides in themes from taking priority.
	 *
	 * @param mixed  $slug Template slug.
	 * @param string $name Template name (default: '').
	 */
	public static function get_template_part( $slug, $name = '' ) {
		$template = false;
		if ( $name ) {
			$template = ACADEMY_TEMPLATE_DEBUG_MODE ? '' : locate_template(
				array(
					"{$slug}-{$name}.php",
					self::template_path() . "{$slug}-{$name}.php",
				)
			);

			if ( ! $template ) {
				$fallback = self::plugin_path() . "/templates/{$slug}-{$name}.php";
				$template = file_exists( $fallback ) ? $fallback : '';
			}
		}

		if ( ! $template ) {
			// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/academy/slug.php.
			$template = ACADEMY_TEMPLATE_DEBUG_MODE ? '' : locate_template(
				array(
					"{$slug}.php",
					self::template_path() . "{$slug}.php",
				)
			);
		}
		// Allow 3rd party plugins to filter template file from their plugin.
		$template = apply_filters( 'academy/get_template_part', $template, $slug, $name );
		if ( $template ) {
			load_template( $template, false );
		}
	}

	/**
	 * Get the template path.
	 *
	 * @return string
	 */
	public static function template_path() {
		return apply_filters( 'academy/template_path', 'academy/' );
	}

	/**
	 * Get the template path.
	 *
	 * @return string
	 */
	public static function plugin_path() {
		return apply_filters( 'academy/plugin_path', ACADEMY_ROOT_DIR_PATH );
	}

	/**
	 * Get other templates (e.g. course attributes) passing attributes and including the file.
	 *
	 * @param string $template_name Template name.
	 * @param array  $args Arguments. (default: array).
	 * @param string $template_path Template path. (default: '').
	 * @param string $default_path Default path. (default: '').
	 */
	public static function get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
		$template = false;

		if ( ! $template ) {
			$template = self::locate_template( $template_name, $template_path, $default_path );
		}

		// Allow 3rd party plugin filter template file from their plugin.
		$filter_template = apply_filters( 'academy/get_template', $template, $template_name, $args, $template_path, $default_path );

		if ( $filter_template !== $template ) {
			if ( ! file_exists( $filter_template ) ) {
				/* translators: %s template */
				wc_doing_it_wrong( __FUNCTION__, sprintf( __( '%s does not exist.', 'academy' ), '<code>' . $filter_template . '</code>' ), '1.0.0' );

				return;
			}
			$template = $filter_template;
		}

		$action_args = array(
			'template_name' => $template_name,
			'template_path' => $template_path,
			'located'       => $template,
			'args'          => $args,
		);

		if ( ! empty( $args ) && is_array( $args ) ) {
			if ( isset( $args['action_args'] ) ) {
				wc_doing_it_wrong(
					__FUNCTION__,
					__( 'action_args should not be overwritten when calling academy/get_template.', 'academy' ),
					'1.0.0'
				);
				unset( $args['action_args'] );
			}
			extract( $args ); // @codingStandardsIgnoreLine
		}

		do_action( 'academy/before_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args'] );
		include $action_args['located'];

		do_action( 'academy/after_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args'] );
	}

	/**
	 * Locate a template and return the path for inclusion.
	 *
	 * This is the load order:
	 *
	 * yourtheme/$template_path/$template_name
	 * yourtheme/$template_name
	 * $default_path/$template_name
	 *
	 * @param string $template_name Template name.
	 * @param string $template_path Template path. (default: '').
	 * @param string $default_path Default path. (default: '').
	 *
	 * @return string
	 */
	public static function locate_template( $template_name, $template_path = '', $default_path = '' ) {
		if ( ! $template_path ) {
			$template_path = self::template_path();
		}

		if ( ! $default_path ) {
			$default_path = self::plugin_path() . 'templates/';
		}

		// Look within passed path within the theme - this is priority.
		if ( false !== strpos( $template_name, 'academy_courses_category' ) || false !== strpos( $template_name, 'academy_courses_tag' ) ) {
			$cs_template = str_replace( '_', '-', $template_name );
			$template    = locate_template(
				array(
					trailingslashit( $template_path ) . $cs_template,
					$cs_template,
				)
			);
		}

		if ( empty( $template ) ) {
			$template = locate_template(
				array(
					trailingslashit( $template_path ) . $template_name,
					$template_name,
				)
			);
		}

		// Get default template/.
		if ( ! $template || ACADEMY_TEMPLATE_DEBUG_MODE ) {
			if ( empty( $cs_template ) ) {
				$template = $default_path . $template_name;
			} else {
				$template = $default_path . $cs_template;
			}
		}

		// Return what we found.
		return apply_filters( 'academy/locate_template', $template, $template_name, $template_path );
	}

	/**
	 * Academy Date Format - Allows to change date format for everything Academy.
	 *
	 * @return string
	 */
	public static function get_date_format() {
		$date_format = get_option( 'date_format' );
		if ( empty( $date_format ) ) {
			// Return default date format if the option is empty.
			$date_format = 'F j, Y';
		}

		return apply_filters( 'academy/date_format', $date_format );
	}

	public static function string_to_array( $string ) {
		if ( empty( $string ) ) {
			return [];
		}
		$string = explode( "\n", $string );

		return array_filter( array_map( 'trim', $string ) );
	}

	public static function calculate_percentage( $total_count, $completed_count ) {
		if ( $total_count > 0 && $completed_count > 0 ) {
			return number_format( ( $completed_count * 100 ) / $total_count );
		}

		return 0;
	}

	public static function youtube_id_from_url( $url ) {
		$parts = wp_parse_url( $url );
		if ( isset( $parts['query'] ) ) {
			parse_str( $parts['query'], $qs );
			if ( isset( $qs['v'] ) ) {
				return $qs['v'];
			} elseif ( isset( $qs['vi'] ) ) {
				return $qs['vi'];
			}
		}
		if ( isset( $parts['path'] ) ) {
			$path = explode( '/', trim( $parts['path'], '/' ) );
			return $path[ count( $path ) - 1 ];
		}
		return false;
	}

	public static function parse_embedded_url( $string ) {
		if ( wp_http_validate_url( $string ) ) {
			$url = '';
			if ( str_contains( wp_parse_url( $string )['host'], 'canva.com' ) ) {
				$url = add_query_arg( 'embed', '', $string );
			} else {
				$oembed = _wp_oembed_get_object();
				$url = $oembed->get_provider( $string );
			}
			return array(
				'url' => $url ? $url : $string,
			);
		}
		preg_match( '/src=["\'](.*?)["\']/i', $string, $src );
		preg_match( '/allow=["\'](.*?)["\']/i', $string, $allow );
		preg_match( '/width=["\'](.*?)["\']/i', $string, $width );
		preg_match( '/height=["\'](.*?)["\']/i', $string, $height );
		return array(
			'url'   => ( isset( $src[1] ) ? $src[1] : '' ),
			'allow' => ( isset( $allow[1] ) ? $allow[1] : '' ),
			'width' => ( isset( $width[1] ) ? $width[1] : '' ),
			'height' => ( isset( $height[1] ) ? $height[1] : '' ),
		);
	}

	public static function vimeo_id_from_url( $url ) {
		if ( preg_match( '/(https?:\/\/)?(www\.)?(player\.)?vimeo\.com\/([a-z]*\/)*([0-9]{6,11})[?]?.*/', $url, $output_array ) ) {
			return $output_array[5];
		}
	}

	public static function generate_video_embed_url( $url ) {
		if ( strpos( $url, 'youtube' ) > 0 || strpos( $url, 'youtu.be' ) > 0 ) {
			return 'https://www.youtube.com/embed/' . self::youtube_id_from_url( $url );
		} elseif ( strpos( $url, 'vimeo' ) > 0 ) {
			return 'https://player.vimeo.com/video/' . self::vimeo_id_from_url( $url ) . '?title=0&byline=0';
		}
		return $url;
	}

	public static function is_active_woocommerce() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		return is_plugin_active( 'woocommerce/woocommerce.php' );
	}

	public static function sanitize_text_or_array_field( $array_or_string ) {
		$boolean = [ 'true', 'false', '1', '0' ];
		if ( is_string( $array_or_string ) ) {
			$array_or_string = in_array( $array_or_string, $boolean, true ) || is_bool( $array_or_string ) ? rest_sanitize_boolean( $array_or_string ) : sanitize_text_field( $array_or_string );
		} elseif ( is_array( $array_or_string ) ) {
			foreach ( $array_or_string as $key => &$value ) {
				if ( is_array( $value ) ) {
					$value = self::sanitize_text_or_array_field( $value );
				} else {
					$value = in_array( $value, $boolean, true ) || is_bool( $value ) ? rest_sanitize_boolean( $value ) : sanitize_text_field( $value );
				}
			}
		}

		return $array_or_string;
	}

	public static function sanitize_checkbox_field( $boolean ) {
		return filter_var( sanitize_text_field( $boolean ), FILTER_VALIDATE_BOOLEAN );
	}

	public static function sanitize_referer_url( $referer_url ) {
		$parse_url = wp_parse_url( $referer_url );
		if ( isset( $parse_url['query'] ) ) {
			// Parse query parameters
			parse_str( $parse_url['query'], $query_params );
			if ( isset( $query_params['redirect_to'] ) && ! empty( $query_params['redirect_to'] ) ) {
				$referer_url = $query_params['redirect_to'];
			}
			if ( isset( $query_params['redirect_url'] ) && ! empty( $query_params['redirect_url'] ) ) {
				$referer_url = $query_params['redirect_url'];
			}
		}
		// Sanitize the input URL
		$referer_url = esc_url_raw( $referer_url );
		if ( filter_var( $referer_url, FILTER_VALIDATE_URL ) !== false && wp_http_validate_url( $referer_url ) && strpos( $referer_url, home_url() ) === 0 ) {
			return esc_url( $referer_url );
		} elseif ( isset( $parse_url['path'] ) && ! empty( $parse_url['path'] ) ) {
			return esc_url( home_url( sanitize_text_field( $parse_url['path'] ) ) );
		}

		return esc_url( home_url( '/' ) );
	}

	public static function get_current_term_id() {
		$queried         = get_queried_object();
		$current_term_id = ( is_object( $queried ) && property_exists( $queried, 'term_id' ) ) ? $queried->term_id : false;

		return $current_term_id;
	}

	public static function get_basic_url_to_embed_url( $url ) {
		$embedObject = self::oembed_get( $url );
		if ( $embedObject ) {
			return self::parse_embedded_url( $embedObject->html );
		}

		return array(
			'url'   => self::generate_video_embed_url( $url ),
			'allow' => '',
		);
	}

	public static function oembed_get( $url, $args = '' ) {
		$oembed = _wp_oembed_get_object();

		return $oembed->get_data( $url, $args );
	}

	public static function minify_css( $css ) {
		$css = preg_replace( '/\/\*((?!\*\/).)*\*\//', '', $css );
		$css = preg_replace( '/\s{2,}/', ' ', $css );
		$css = preg_replace( '/\s*([:;{}])\s*/', '$1', $css );
		$css = preg_replace( '/;}/', '}', $css );

		return $css;
	}

	public static function get_permalink_structure() {
		$saved_permalinks = (array) get_option( 'academy_permalinks', array() );
		$permalinks       = wp_parse_args(
			array_filter( $saved_permalinks ),
			array(
				'course_base'            => _x( 'course', 'slug', 'academy' ),
				'category_base'          => _x( 'course-category', 'slug', 'academy' ),
				'tag_base'               => _x( 'course-tag', 'slug', 'academy' ),
				'use_verbose_page_rules' => false,
			)
		);

		if ( $saved_permalinks !== $permalinks ) {
			update_option( 'academy_permalinks', $permalinks );
		}

		$permalinks['course_rewrite_slug']   = untrailingslashit( $permalinks['course_base'] );
		$permalinks['category_rewrite_slug'] = untrailingslashit( $permalinks['category_base'] );
		$permalinks['tag_rewrite_slug']      = untrailingslashit( $permalinks['tag_base'] );

		return $permalinks;
	}

	public static function sanitize_permalink( $value ) {
		global $wpdb;

		$value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );

		if ( is_wp_error( $value ) ) {
			$value = '';
		}

		$value = esc_url_raw( trim( $value ) );
		$value = str_replace( 'http://', '', $value );

		return untrailingslashit( $value );
	}

	/**
	 * Recursively get page children.
	 *
	 * @param int $page_id Page ID.
	 *
	 * @return int[]
	 */
	public static function get_page_children( $page_id ) {
		$page_ids = get_posts(
			array(
				'post_parent' => $page_id,
				'post_type'   => 'page',
				'numberposts' => - 1, // @codingStandardsIgnoreLine
				'post_status' => 'any',
				'fields'      => 'ids',
			)
		);

		if ( ! empty( $page_ids ) ) {
			foreach ( $page_ids as $page_id ) {
				$page_ids = array_merge( $page_ids, self::get_page_children( $page_id ) );
			}
		}

		return $page_ids;
	}

	public static function is_plugin_installed( $basename ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			include_once ABSPATH . '/wp-admin/includes/plugin.php';
		}
		$installed_plugins = get_plugins();

		return isset( $installed_plugins[ $basename ] );
	}

	public static function get_client_ip_address() {
		$ip_address = '';
		if ( getenv( 'HTTP_CLIENT_IP' ) ) {
			$ip_address = getenv( 'HTTP_CLIENT_IP' );
		} elseif ( getenv( 'REMOTE_ADDR' ) ) {
			$ip_address = getenv( 'REMOTE_ADDR' );
		} elseif ( getenv( 'HTTP_FORWARDED_FOR' ) ) {
			$ip_address = getenv( 'HTTP_FORWARDED_FOR' );
		} elseif ( getenv( 'HTTP_FORWARDED' ) ) {
			$ip_address = getenv( 'HTTP_FORWARDED' );
		} elseif ( getenv( 'HTTP_X_FORWARDED_FOR' ) ) {
			$ip_address = getenv( 'HTTP_X_FORWARDED_FOR' );
		} elseif ( getenv( 'HTTP_X_FORWARDED' ) ) {
			$ip_address = getenv( 'HTTP_X_FORWARDED' );
		}

		return $ip_address;
	}

	public static function get_content_html( $content ) {
		global $wp_embed;
		$content = $wp_embed->run_shortcode( $content );
		$content = $wp_embed->autoembed( $content );
		$content = do_blocks( $content );
		$content = wptexturize( $content );
		$content = convert_smilies( $content );
		$content = shortcode_unautop( $content );
		$content = wp_filter_content_tags( $content );
		$content = do_shortcode( $content );
		$content = str_replace( ']]>', ']]&gt;', $content );

		return $content;
	}

	public static function is_html5_video_link( $link ) {
		$pattern = '/\.mp4$|\.webm$|\.ogg$/i';

		return preg_match( $pattern, $link );
	}

	public static function is_auto_load_next_lesson() {
		if ( self::is_active_academy_pro() ) {
			return self::get_settings( 'auto_load_next_lesson', false );
		}

		return false;
	}

	public static function is_auto_complete_topic() {
		if ( self::is_active_academy_pro() ) {
			return self::get_settings( 'auto_complete_topic', false );
		}

		return false;
	}

	public static function get_page_by_title( $page_title, $post_type = 'page' ) {
		global $wpdb;

		$page = $wpdb->get_var( $wpdb->prepare(
			"SELECT ID
			FROM $wpdb->posts
			WHERE post_title = %s
			AND post_type = %s",
			$page_title,
			$post_type
		) );

		if ( $page ) {
			return get_post( $page, OBJECT );
		}

		return null;
	}

	public static function generate_unique_username_from_email( $email ) {
		// Generate a username from the email address
		$username = sanitize_user( current( explode( '@', $email ) ) );

		// Check if the generated username already exists
		if ( username_exists( $username ) ) {
			$suffix = 1;

			// Modify the username to make it unique
			while ( username_exists( $username . $suffix ) ) {
				$suffix ++;
			}

			$username .= $suffix;
		}

		return $username;
	}

	public static function has_user_meta_exists( $author_id, $meta_key, $meta_value ) {
		global $wpdb;
		$has_meta = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(umeta_id) FROM {$wpdb->usermeta} 
				WHERE user_id = %d AND meta_key = %s AND meta_value = %s",
				$author_id,
				$meta_key,
				strval( $meta_value )
			)
		);

		return $has_meta;
	}

	public static function maybe_define_constant( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	public static function user_has_role( $user_id, $role_name ) {
		$user_meta  = get_userdata( $user_id );
		$user_roles = $user_meta->roles;

		return in_array( $role_name, $user_roles, true );
	}

	public static function get_lost_password_url() {
		$permalink = self::get_page_permalink( 'password_reset_page' );
		if ( $permalink ) {
			return $permalink;
		}

		return wp_lostpassword_url();
	}

	public static function get_page_permalink( $page, $fallback = null ) {
		$page_id   = self::get_settings( $page );
		$permalink = 0 < $page_id ? get_permalink( $page_id ) : '';
		if ( ! $permalink ) {
			$permalink = is_null( $fallback ) ? get_home_url() : $fallback;
		}

		return apply_filters( 'academy/get_' . $page . '_permalink', $permalink );
	}

	public static function get_form_builder_fields( $form_type ) {
		$form_settings = get_option( 'academy_form_builder_settings' );
		$form_settings = json_decode( $form_settings, true );
		$form_fields   = [];

		if ( ! empty( $form_settings[ $form_type ] ) ) {
			foreach ( $form_settings[ $form_type ] as $fields ) {
				foreach ( $fields['fields'] as $field ) {
					$common_fields = [
						'first-name',
						'last-name',
						'email',
						'confirm-email',
						'phone-number',
						'password',
						'confirm-password',
						'button',
					];
					if ( in_array( $field['name'], $common_fields, true ) ) {
						continue;
					}
					$form_fields[] = $field;
				}
			}
		}

		return $form_fields;
	}

	public static function prepare_user_meta_data( $form_fields, $user_id ) {
		$meta = [];
		if ( is_array( $form_fields ) && count( $form_fields ) ) {

			foreach ( $form_fields as $form_field ) {
				$value = get_user_meta( (int) $user_id, 'academy_' . $form_field['name'], true );
				if ( $value ) {
					if ( isset( $form_field['options'] ) ) {
						foreach ( $form_field['options'] as $option ) {
							if ( $option['value'] === $value ) {
								$value = $option['label'];
							}
						}
					}
					if ( 'time' === $form_field['type'] ) {
						$value = date_i18n( get_option( 'time_format' ), strtotime( $value ) );
					}

					$meta[] = [
						'label' => $form_field['label'],
						'value' => $value,
						'type'  => $form_field['type'],
					];
				}
			}//end foreach
		}//end if
		return $meta;
	}

	public static function get_edd_products(): array {
		$products = [];
		if ( self::is_active_easy_digital_downloads() ) {
			$products = get_posts(
				array(
					'post_type'      => 'download',
					'posts_per_page' => - 1,
				)
			);
		}

		return $products;
	}

	public static function is_active_easy_digital_downloads(): bool {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		return is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' );
	}

	public static function convert_camel_case_to_words( $camelCaseString ): string {
		$words = preg_split( '/(?=[A-Z])/', $camelCaseString );
		$words[0] = ucfirst( $words[0] );
		return implode( ' ', $words );
	}

	public static function get_topic_id_by_topic_name_and_topic_type( $slug, $topic_type ) {
		if ( 'lesson' === $topic_type ) {
			$lesson = self::get_lesson_by_slug( $slug );
			if ( $lesson ) {
				return $lesson->ID;
			}
			return 0;
		} elseif ( 'quiz' === $topic_type ) {
			$quiz = get_posts( array(
				'name' => $slug,
				'post_type' => 'academy_quiz',
				'post_status' => 'publish',
				'numberposts' => 1,
			) );

			return ( ! empty( $quiz ) ) ? current( $quiz )->ID : 0;
		} elseif ( 'assignment' === $topic_type ) {
			$assignment = get_posts( array(
				'name' => $slug,
				'post_type' => 'academy_assignments',
				'post_status' => 'publish',
				'numberposts' => 1,
			) );

			return ( ! empty( $assignment ) ) ? current( $assignment )->ID : 0;
		} elseif ( 'meeting' === $topic_type ) {
			$meeting = get_posts( array(
				'name' => $slug,
				'post_type' => 'academy_meeting',
				'post-status' => 'publish',
				'numberposts' => 1,
			) );

			return ( ! empty( $meeting ) ) ? current( $meeting )->ID : 0;
		} elseif ( 'booking' === $topic_type ) {
			$booking = get_posts( array(
				'name' => $slug,
				'post_type' => 'academy_booking',
				'post-status' => 'publish',
				'numberposts' => 1,
			) );

			return ( ! empty( $booking ) ) ? current( $booking )->ID : 0;
		}//end if

		return false;
	}

	public static function get_start_course_permalink( $course_id ): string {
		if ( self::get_settings( 'is_enabled_lessons_php_render' ) && self::get_settings( 'lessons_page' ) ) {
			$curriculums = self::get_course_curriculum( $course_id, false );
			$curriculum = ( ! empty( $curriculums ) ) ? current( $curriculums ) : '';
			if ( is_array( $curriculum ) && isset( $curriculum['topics'] ) ) {
				$topic = current( $curriculum['topics'] );
				if ( isset( $topic['type'] ) && 'sub-curriculum' === $topic['type'] ) {
					$sub_topic = current( $topic['topics'] );
					return self::get_topic_play_link( $sub_topic, $course_id );
				}
				if ( is_array( $topic ) && count( $topic ) ) {
					return self::get_topic_play_link( $topic, $course_id );
				}
			}
			return add_query_arg( array(), trailingslashit( get_the_permalink( $course_id ) ) . 'lesson/not-found' );
		}

		return add_query_arg( array( 'source' => 'curriculums' ), get_the_permalink( $course_id ) );
	}

	public static function get_prev_and_next_details_of_curriculum() {
		if ( self::get_settings( 'is_enabled_lessons_php_render' ) && self::get_settings( 'lessons_page' ) ) {
			$slug = get_query_var( 'name' );
			$type = get_query_var( 'curriculum_type' );
			$topic_id = self::get_topic_id_by_topic_name_and_topic_type( $slug, $type );
			$course_id = self::get_the_current_course_id();
			$curriculums = self::get_course_curriculum( $course_id, false );
			$all_topics = [];

			foreach ( $curriculums as $curriculum ) {
				foreach ( $curriculum['topics'] as $topics ) {
					if ( 'sub-curriculum' === $topics['type'] ) {
						foreach ( $topics['topics'] as $topic ) {
							$all_topics[] = $topic;
						}
					} else {
						$all_topics[] = $topics;
					}
				}
			}
			// phpcs:ignore
			$current_index = array_search( $topic_id, array_column( $all_topics, 'id' ) ); // don't do strict comparison
			$prev = ( $current_index > 0 ) ? $all_topics[ $current_index - 1 ] : '';
			$next = ( $current_index < count( $all_topics ) && array_key_exists( $current_index + 1, $all_topics ) ) ? $all_topics[ $current_index + 1 ] : '';
			$prev_link = ( ! empty( $prev ) ) ? self::get_topic_play_link( $prev ) : '';
			$next_link = ( ! empty( $next ) ) ? self::get_topic_play_link( $next ) : '';
			if ( ! $current_index && 0 === count( $all_topics ) ) {
				$prev_link = [];
				$next_link = [];
			}
			return array(
				'previous' => array(
					'link' => $prev_link,
					'name' => $prev['name'] ?? '',
				),
				'next' => array(
					'link' => $next_link,
					'name' => $next['name'] ?? '',
				),
			);
		}//end if
		return false;
	}

	public static function has_permission_to_access_curriculum( $course_id, $user_id = null, $topic_id = null, $topic_type = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		$is_administrator = current_user_can( 'administrator' );
		$is_instructor    = self::is_instructor_of_this_course( $user_id, $course_id );
		$enrolled         = self::is_enrolled( $course_id, $user_id );
		$is_public_course = self::is_public_course( $course_id );

		if ( $topic_type && $topic_id ) {
			$is_course_curriculum = self::is_course_curriculum( $course_id, $topic_id, $topic_type );
			return ( $is_administrator || $is_instructor || $enrolled || $is_public_course ) && $is_course_curriculum;
		}

		return $is_administrator || $is_instructor || $enrolled || $is_public_course;
	}

	public static function has_permission_to_access_lesson_curriculum( $course_id, $lesson_id, $user_id = null ) {
		return self::has_permission_to_access_curriculum( $course_id, $user_id, $lesson_id, 'lesson' ) || ( $lesson_id && self::get_lesson_meta( $lesson_id, 'is_previewable' ) );
	}

	public static function is_active_curriculum_content( $topic ) {
		$topic_name = get_query_var( 'name' );
		$topic_name = $topic_name ?? '';

		if ( $topic_name === $topic['slug'] ) {
			return true;
		}

		return false;
	}
	public static function get_frontend_dashboard_menu_items() {
		$items = array(
			'index'           => array(
				'label' => __( 'Dashboard', 'academy' ),
				'icon'  => 'academy-icon academy-icon--grid-two',
				'public' => true,
				'priority' => 5,
			),
			'profile'           => array(
				'label' => __( 'My Profile', 'academy' ),
				'icon'  => 'academy-icon academy-icon--profile-two',
				'public' => true,
				'priority' => 10,
			),
			'enrolled-courses'           => array(
				'label' => __( 'Enrolled Courses', 'academy' ),
				'icon'  => 'academy-icon academy-icon--enrollement',
				'public' => true,
				'priority' => 15,
			),
			'active-courses'           => array(
				'label' => __( 'Enrolled Courses', 'academy' ),
				'public' => false,
				'priority' => 15,
			),
			'complete-courses'           => array(
				'label' => __( 'Enrolled Courses', 'academy' ),
				'public' => false,
				'priority' => 15,
			),
			'wishlist'           => array(
				'label' => __( 'Wishlist', 'academy' ),
				'icon'  => 'academy-icon academy-icon--wishlist',
				'public' => true,
				'priority' => 20,
			),
			'reviews'           => array(
				'label' => __( 'Reviews', 'academy' ),
				'icon'  => 'academy-icon academy-icon--star-alt',
				'public' => true,
				'priority' => 25,
			),
			'received-reviews'           => array(
				'label' => __( 'Received Reviews', 'academy' ),
				'public' => false,
				'priority' => 25,
			),
			'settings'           => array(
				'label' => __( 'Settings', 'academy' ),
				'icon'  => 'academy-icon academy-icon--settings',
				'public' => true,
				'priority' => 50,
			),
			'reset-password'           => array(
				'label' => __( 'Reset Password', 'academy' ),
				'public' => false,
				'priority' => 50,
			),
			'logout'           => array(
				'label' => __( 'Log Out', 'academy' ),
				'icon'  => 'academy-icon academy-icon--logout',
				'priority' => 99,
				'public' => true,
			),
		);

		if ( self::get_settings( 'is_enable_apply_instructor_menu' ) ) {
			$items['become-an-instructor'] = array(
				'label' => __( 'Become An Instructor', 'academy' ),
				'icon'  => 'academy-icon academy-icon--instructor',
				'public' => ! current_user_can( 'manage_academy_instructor' ) ? true : false,
				'priority' => 2,
			);
		}

		if ( current_user_can( 'manage_academy_instructor' ) ) {
			$items['courses'] = array(
				'label' => __( 'Courses', 'academy' ),
				'icon'  => 'academy-icon academy-icon--course-cap',
				'public' => true,
				'priority' => 30,
				'child_items' => [
					'category'           => array(
						'label'         => __( 'Categories', 'academy' ),
						'public' => true,
						'priority' => 30,
					),
					'tag'           => array(
						'label'         => __( 'Tags', 'academy' ),
						'public' => true,
						'priority' => 30,
					),
				],
			);
			$items['lessons'] = array(
				'label' => __( 'All Lessons', 'academy' ),
				'icon'  => 'academy-icon academy-icon--Lesson',
				'public' => true,
				'priority' => 35,
			);
			$items['announcements'] = array(
				'label' => __( 'Announcements', 'academy' ),
				'icon'  => 'academy-icon academy-icon--announcement',
				'public' => true,
				'priority' => 45,
			);
			$items['question-answer'] = array(
				'label' => __( 'Question & Answer', 'academy' ),
				'icon'  => 'academy-icon academy-icon--question',
				'public' => true,
				'priority' => 40,
			);
			if ( self::get_addon_active_status( 'multi_instructor' ) && self::get_settings( 'is_enabled_earning' ) ) {
				$items['withdrawal']  = array(
					'label' => __( 'Withdrawal', 'academy' ),
					'public' => true,
					'icon'  => 'academy-icon academy-icon--wallet',
					'priority' => 49,
				);
				$items['withdraw']  = array(
					'label' => __( 'Withdraw', 'academy' ),
					'public' => false,
					'priority' => 50,
				);
			}
		}//end if

		if ( self::is_active_woocommerce() ) {
			$items['purchase-history'] = array(
				'label' => __( 'Purchase History', 'academy' ),
				'icon' => 'academy-icon academy-icon--purchase',
				'public' => true,
				'priority' => 26,
			);
		}

		return apply_filters( 'academy/frontend_dashboard_menu_items', $items );
	}

	public static function current_user_has_access_frontend_dashboard_menu( $menu_key ) {
		$menu = self::get_frontend_dashboard_menu_items();
		if ( isset( $menu[ $menu_key ] ) ) {
			return true;
		}
		return false;
	}

	public static function get_frontend_dashboard_page_title( $path, $sub_path ) {
		$menu = self::get_frontend_dashboard_menu_items();
		if ( $sub_path ) {
			return $menu[ $path ]['child_items'][ $sub_path ]['label'];
		}
		return $menu[ $path ]['label'];
	}

	public static function get_endpoint_url( $endpoint, $value = '', $permalink = '' ) {
		global $wp_query;
		if ( ! $permalink ) {
			$permalink = get_permalink();
		}

		// Map endpoint to options.
		$query_vars = $wp_query->query_vars;
		$endpoint = ! empty( $query_vars[ $endpoint ] ) ? $query_vars[ $endpoint ] : $endpoint;

		if ( get_option( 'permalink_structure' ) ) {
			if ( strstr( $permalink, '?' ) ) {
				$query_string = '?' . wp_parse_url( $permalink, PHP_URL_QUERY );
				$permalink = current( explode( '?', $permalink ) );
			} else {
				$query_string = '';
			}
			$url = trailingslashit( $permalink );

			if ( $value ) {
				$url .= trailingslashit( $endpoint ) . user_trailingslashit( $value );
			} else {
				$url .= user_trailingslashit( $endpoint );
			}

			$url .= $query_string;
		} else {
			$url = add_query_arg( $endpoint, $value, $permalink );
		}

		return apply_filters( 'academy/get_endpoint_url', $url, $endpoint, $value, $permalink );
	}

	public static function get_frontend_dashboard_endpoint_url( $endpoint ) {
		if ( 'index' === $endpoint ) {
			return self::get_page_permalink( 'frontend_dashboard_page' );
		}

		if ( 'logout' === $endpoint ) {
			return self::get_logout_url();
		}

		return self::get_endpoint_url( $endpoint, '', self::get_page_permalink( 'frontend_dashboard_page' ) );
	}

	public static function get_logout_url( $redirect = '' ) {
		$redirect = $redirect ? $redirect : apply_filters( 'academy/logout_default_redirect_url', self::get_page_permalink( 'dashboard_page' ) );

		return wp_logout_url( $redirect );
	}

	public static function get_current_user_full_name() {
		$user_info = wp_get_current_user();

		if ( $user_info->first_name ) {

			if ( $user_info->last_name ) {
				return $user_info->first_name . ' ' . $user_info->last_name;
			}

			return $user_info->first_name;
		}

		return $user_info->display_name;
	}

	public static function get_time_different_dynamically_for_any_time( $given_time ) : string {
		$current_time = new \DateTime();
		$given_time = new \DateTime( $given_time );
		$time_difference = $current_time->diff( $given_time );

		if ( $time_difference->y > 0 ) {
			return esc_html( $time_difference->y . ' years ago' );
		} elseif ( $time_difference->m > 0 ) {
			return esc_html( $time_difference->m . ' months ago' );
		} elseif ( $time_difference->d > 0 ) {
			return esc_html( $time_difference->d . ' days ago' );
		} elseif ( $time_difference->h > 0 ) {
			return esc_html( $time_difference->h . ' hours ago' );
		} elseif ( $time_difference->i > 0 ) {
			return esc_html( $time_difference->i . ' minutes ago' );
		} else {
			return esc_html( 'Just now' );
		}
	}


}
