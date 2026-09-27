<?php

namespace Academy;

use Academy;
use DateInterval;
use DateTime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helper {

	/**
	 * Meta key holding the per-course "feature this course" flag.
	 */
	const STICKY_META_KEY = 'academy_course_is_sticky';

	/**
	 * Query var a course query sets to opt in to sticky-first ordering.
	 *
	 * Ordering is strictly opt-in rather than applied to every
	 * `academy_courses` query, because plenty of them must NOT be reordered:
	 * the admin list table (an admin clicking a column header expects that
	 * column to sort), REST listings behind the React admin, and any embed
	 * that asked for an explicit order.
	 */
	const STICKY_QUERY_VAR = 'academy_sticky_first';


	use Traits\Courses;
	use Traits\Lessons;
	use Traits\Instructor;
	use Traits\Student;
	use Traits\Earning;
	use Traits\Withdrawals;

	/**
	 * Schedule rewrite rule flushing on next reload.
	 *
	 * @return void
	 * @since 3.3.8
	 */
	public static function flush_rewrite_rules() {
		update_option( 'academy_required_rewrite_flush', 'yes' );
	}

	/**
	 * Wraps attachment_url_to_postid(), using WordPress VIP's cached variant when available.
	 *
	 * @param string $url Attachment URL.
	 * @return int Attachment ID, or 0.
	 */
	public static function attachment_url_to_postid( $url ) {
		if ( function_exists( 'wpcom_vip_attachment_url_to_postid' ) ) {
			return (int) wpcom_vip_attachment_url_to_postid( $url );
		}
		return (int) attachment_url_to_postid( $url ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.attachment_url_to_postid_attachment_url_to_postid -- non-VIP fallback.
	}

	/**
	 * Wraps count_user_posts(), using WordPress VIP's cached variant when available.
	 *
	 * @param int    $user_id   User ID.
	 * @param string $post_type Post type.
	 * @return int
	 */
	public static function count_user_posts( $user_id, $post_type = 'post' ) {
		if ( function_exists( 'wpcom_vip_count_user_posts' ) ) {
			return (int) wpcom_vip_count_user_posts( $user_id, $post_type );
		}
		return (int) count_user_posts( $user_id, $post_type ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.count_user_posts_count_user_posts -- non-VIP fallback.
	}

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
				// The page's route tabs (not a submenu). Kept apart from
				// `sub_items` so a group can still use the page as its link;
				// global search indexes both.
				'tab_items'   => [
					[
						'slug'  => '',
						'title' => __( 'All Quizzes', 'academy' ),
					],
					[
						'slug'  => 'questions',
						'title' => __( 'Questions', 'academy' ),
					],
					[
						'slug'  => 'attempts',
						'title' => __( 'Quiz Attempts', 'academy' ),
					]
				]
			];
		}//end if
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
					// The page's route tabs (not a submenu). Kept apart from
					// `sub_items` so a group can still use the page as its link;
					// global search indexes both.
					'tab_items'   => [
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
					// The page's route tabs (not a submenu). Kept apart from
					// `sub_items` so a group can still use the page as its link;
					// global search indexes both.
					'tab_items'   => [
						[
							'slug'  => '',
							'title' => __( 'All Assignments', 'academy' ),
						],
						[
							'slug'  => 'submitted-assignments',
							'title' => __( 'Submitted Assignments', 'academy' ),
						]
					]
				];
			}
			if ( self::get_addon_active_status( 'course-bundle' ) && ( self::is_active_woocommerce() || class_exists( \StoreEngine::class ) ) ) {
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
			if ( self::get_addon_active_status( 'group-plus' ) ) {
				$menu[ ACADEMY_PLUGIN_SLUG . '-group-plus' ] = [
					'parent_slug' => ACADEMY_PLUGIN_SLUG,
					'title'       => __( 'Group Plus', 'academy' ),
					'capability'  => 'manage_options',
				];
			}//end if
			if ( self::get_addon_active_status( 'course-active-timer' ) ) {
				$menu[ ACADEMY_PLUGIN_SLUG . '-course-timer' ]    = [
					'parent_slug' => ACADEMY_PLUGIN_SLUG,
					'title'       => __( 'Course Active Timer', 'academy' ),
					'capability'  => 'manage_options',
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
				'title'       => __( 'Gradebook', 'academy' ),
				'capability'  => 'manage_options',
				// The page's route tabs (not a submenu). Kept apart from
				// `sub_items` so a group can still use the page as its link;
				// global search indexes both.
				'tab_items'   => [
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
		if ( self::is_active_academy_pro() && self::get_addon_active_status( 'attendance' ) ) {
			$menu[ ACADEMY_PLUGIN_SLUG . '-attendance' ] = [
				'parent_slug' => ACADEMY_PLUGIN_SLUG,
				'title'       => __( 'Attendance', 'academy' ),
				'capability'  => 'manage_options',
			];
		}
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
		// Incoming webhooks are the Incoming tab of the Webhooks screen, not a
		// menu entry of their own.
		$menu[ ACADEMY_PLUGIN_SLUG . '-addons' ]      = [
			'parent_slug' => ACADEMY_PLUGIN_SLUG,
			'title'       => __( 'Add-ons', 'academy' ),
			'capability'  => 'manage_options',
		];
		// "What's new!" is no longer a visible menu item — it is a hidden page
		// shown once per plugin update (see Menu::register_hidden_pages() and
		// Menu::maybe_redirect_whats_new()).
		$menu[ ACADEMY_PLUGIN_SLUG . '-tools' ]       = [
			'parent_slug' => ACADEMY_PLUGIN_SLUG,
			'title'       => __( 'Tools', 'academy' ),
			'capability'  => 'manage_options',
		];
		// How the site looks sits next to how it behaves.
		$menu[ ACADEMY_PLUGIN_SLUG . '-design' ]      = [
			'parent_slug' => ACADEMY_PLUGIN_SLUG,
			'title'       => __( 'Customize', 'academy' ),
			'capability'  => 'manage_options',
			// Shown beside the menu name.
			'badge'       => __( 'New', 'academy' ),
		];
		$menu[ ACADEMY_PLUGIN_SLUG . '-settings' ]    = [
			'parent_slug' => ACADEMY_PLUGIN_SLUG,
			'title'       => __( 'Settings', 'academy' ),
			'capability'  => 'manage_options',
		];
		$menu[ ACADEMY_PLUGIN_SLUG . '-discover' ]    = [
			'parent_slug' => ACADEMY_PLUGIN_SLUG,
			'title'       => __( 'Discover', 'academy' ),
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

		return self::order_admin_menu( self::nest_admin_menu_pages( apply_filters( 'academy/admin_menu_list', $menu ) ) );
	}

	/**
	 * Show some pages inside another item's submenu instead of as their own
	 * top-level menu item.
	 *
	 * - Course Bundle and Certificates go under Courses.
	 * - Groups (People, Engagement) have no page of their own: the first
	 *   available page is the group's link, renamed via `menu_title`, and
	 *   the rest become its children. A group with one available page stays
	 *   a plain item.
	 *
	 * The pages stay in the list, so they are still registered, keep their
	 * `?page=` URLs and stay searchable; they are only marked `menu_parent`,
	 * which hides them from the menu (see Menu::nested_pages_css() and the
	 * React AdminMenu). Runs after the `academy/admin_menu_list` filter, so a
	 * page removed there (e.g. by role permissions) is never linked.
	 *
	 * @param array $menu Admin menu list.
	 * @return array
	 */
	public static function nest_admin_menu_pages( array $menu ) {
		$slug    = ACADEMY_PLUGIN_SLUG;
		$courses = $slug . '-courses';
		$nested  = [
			$slug . '-course-bundle' => __( 'Bundles', 'academy' ),
			$slug . '-certificates'  => __( 'Certificates', 'academy' ),
		];
		if ( ! empty( $menu[ $courses ] ) ) {
			foreach ( $nested as $page => $title ) {
				if ( empty( $menu[ $page ] ) ) {
					continue;
				}
				$menu[ $courses ]['sub_items'][] = [
					'page'  => $page,
					'title' => $title,
				];
				$menu[ $page ]['menu_parent']    = $courses;
			}
		}

		$groups = [
			[
				'title' => __( 'Content', 'academy' ),
				'pages' => [
					$slug . '-lessons'       => __( 'Lessons', 'academy' ),
					$slug . '-quizzes'       => __( 'Quizzes', 'academy' ),
					$slug . '-meeting'       => __( 'Meeting', 'academy' ),
					$slug . '-assignments'   => __( 'Assignments', 'academy' ),
					$slug . '-tutor-booking' => __( 'Tutor Bookings', 'academy' ),
				],
			],
			[
				'title' => __( 'People', 'academy' ),
				'pages' => [
					$slug . '-students'    => __( 'Students', 'academy' ),
					$slug . '-instructors' => __( 'Instructors', 'academy' ),
					$slug . '-group-plus'  => __( 'Groups', 'academy' ),
					$slug . '-withdraw'    => __( 'Payouts', 'academy' ),
				],
			],
			[
				'title' => __( 'Engagement', 'academy' ),
				'pages' => [
					$slug . '-announcements'   => __( 'Announcements', 'academy' ),
					$slug . '-question_answer' => __( 'Q&A', 'academy' ),
					$slug . '-course-timer'    => __( 'Time Tracking', 'academy' ),
				],
			],
		];
		foreach ( $groups as $group ) {
			$pages = array_filter(
				$group['pages'],
				function ( $page ) use ( $menu ) {
					return ! empty( $menu[ $page ] ) && empty( $menu[ $page ]['menu_parent'] );
				},
				ARRAY_FILTER_USE_KEY
			);
			if ( count( $pages ) < 2 ) {
				continue;
			}
			$anchor    = array_key_first( $pages );
			$sub_items = [];
			foreach ( $pages as $page => $title ) {
				$sub_items[] = [
					'page'  => $page,
					'title' => $title,
				];
				if ( $page !== $anchor ) {
					$menu[ $page ]['menu_parent'] = $anchor;
				}
			}
			$menu[ $anchor ]['menu_title'] = $group['title'];
			$menu[ $anchor ]['sub_items']  = $sub_items;
			$menu = self::move_admin_menu_item( $menu, $anchor, array_keys( $pages ) );
		}//end foreach
		return $menu;
	}

	/**
	 * Order the top-level menu into sections and mark where the divider
	 * lines go.
	 *
	 * Each section lists page keys; a key that is nested in a group (or
	 * missing: addon off, removed by role permissions) is skipped, so a
	 * group is placed by whichever of its pages is its link. The last item
	 * placed in each section gets `separator_after` (drawn by
	 * Menu::separators_css()). Keys in no section (e.g. added by another
	 * plugin through the filter) keep their order and go at the end.
	 *
	 * @param array $menu Admin menu list.
	 * @return array
	 */
	public static function order_admin_menu( array $menu ) {
		$slug     = ACADEMY_PLUGIN_SLUG;
		$sections = [
			// Overview.
			[ $slug ],
			// What you teach.
			[ $slug . '-courses', $slug . '-lessons', $slug . '-quizzes', $slug . '-meeting', $slug . '-assignments', $slug . '-tutor-booking', $slug . '-google-classroom' ],
			// Who learns, and how they do.
			[ $slug . '-students', $slug . '-instructors', $slug . '-group-plus', $slug . '-withdraw', $slug . '-announcements', $slug . '-question_answer', $slug . '-course-timer', $slug . '-grade-book', $slug . '-attendance' ],
			// Extend and maintain.
			[ $slug . '-addons', $slug . '-webhooks', $slug . '-tools' ],
			// Look and configuration.
			[ $slug . '-design', $slug . '-settings', $slug . '-discover' ],
		];
		$ordered  = [];
		foreach ( $sections as $section ) {
			$last = null;
			foreach ( $section as $key ) {
				if ( empty( $menu[ $key ] ) || ! empty( $menu[ $key ]['menu_parent'] ) || isset( $ordered[ $key ] ) ) {
					continue;
				}
				$ordered[ $key ] = $menu[ $key ];
				$last            = $key;
			}
			if ( $last ) {
				$ordered[ $last ]['separator_after'] = true;
			}
		}
		// Nothing after the final section's line.
		if ( $ordered ) {
			$keys = array_keys( $ordered );
			unset( $ordered[ end( $keys ) ]['separator_after'] );
		}
		// Everything not placed: nested pages and items from other code.
		return $ordered + $menu;
	}

	/**
	 * Move a group's link to where its earliest page sat in the menu, so a
	 * group shows up where its first member used to be.
	 *
	 * @param array  $menu    Admin menu list.
	 * @param string $anchor  Key to move.
	 * @param array  $members Keys of every page in the group.
	 * @return array
	 */
	private static function move_admin_menu_item( array $menu, $anchor, array $members ) {
		$keys     = array_keys( $menu );
		$position = min( array_map( function ( $key ) use ( $keys ) {
			return array_search( $key, $keys, true );
		}, $members ) );
		$item     = [ $anchor => $menu[ $anchor ] ];
		unset( $menu[ $anchor ] );
		return array_slice( $menu, 0, $position, true ) + $item + array_slice( $menu, $position, null, true );
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

	public static function is_active_ecm() {
		return class_exists( 'EasyContentManager' );
	}

	/**
	 * QuizPress is an "Extensions & Integrations" companion plugin, not a
	 * toggleable addon — the integration is on whenever the plugin is active.
	 */
	public static function is_active_quizpress() {
		return self::is_plugin_active( 'quizpress/quizpress.php' );
	}

	public static function is_active_ablocks() {
		$ablocks = 'ablocks/ablocks.php';

		return self::is_plugin_active( $ablocks );
	}

	public static function is_active_storeengine() {
		$storeengine = 'storeengine/storeengine.php';

		return self::is_plugin_active( $storeengine );
	}

	public static function is_active_zencommunity() {
		$zencommunity = 'zencommunity/zencommunity.php';
		return self::is_plugin_active( $zencommunity );
	}

	public static function is_active_gemsecurity() {
		$gemsecurity = 'gemsecurity/gemsecurity.php';
		return self::is_plugin_active( $gemsecurity );
	}

	/**
	 * Whether GemSecurity is active AND its own Social Login module reports
	 * active via the `gemsecurity/module_active` filter. Backs the
	 * both-active escalation in the Social Login deprecation notices —
	 * default `false` (unlike `is_active_gemsecurity()`'s callers, which
	 * default `true` for the Login Security check) so an ambiguous or
	 * absent signal never triggers a false "you have both enabled" claim.
	 *
	 * @return bool
	 */
	public static function is_gemsecurity_social_login_active() {
		return self::is_active_gemsecurity()
			&& has_filter( 'gemsecurity/module_active' )
			&& (bool) apply_filters( 'gemsecurity/module_active', false, 'social-login' );
	}

	public static function is_plugin_active( $basename ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			include_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $basename );
	}

	/**
	 * Whether course pages use block templates: always in block themes, and in
	 * classic themes that opt in with `academy/templates/use_block_templates`
	 * (unless the site kept the original course page layout).
	 *
	 * @return bool
	 */
	public static function use_block_templates() {
		if ( self::is_fse_theme() ) {
			return true;
		}

		return 'legacy' !== \Academy\Blocks::template_style() && (bool) apply_filters( 'academy/templates/use_block_templates', false );
	}

	public static function is_fse_theme() {
		if ( function_exists( 'wp_is_block_theme' ) ) {
			return (bool) \wp_is_block_theme();
		}
		if ( function_exists( 'gutenberg_is_fse_theme' ) ) {
			return (bool) \gutenberg_is_fse_theme();
		}

		return false;
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

			// get_current_screen() returns null when called before the
			// `current_screen` hook has fired (e.g. while the setup wizard page
			// renders), so guard before reading ->base.
			if ( ! $screen instanceof \WP_Screen ) {
				return false;
			}

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
		if ( is_string( $hook ) && strpos( $hook, '_page_' . ACADEMY_PLUGIN_SLUG ) !== false ) {
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
			return min( number_format( ( $completed_count / $total_count ) * 100 ), 100 );
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

	/**
	 * Resolve a bare URL down to the canonical provider KodezenPlayer natively
	 * plays, or null if it doesn't match one — an opaque third-party embed
	 * (Canva, Kaltura, …) with no native provider, kept on the iframe fallback.
	 *
	 * @param string $resolved An already-unwrapped URL — e.g. the `url` field
	 *                          parse_embedded_url() returns for an "embedded"
	 *                          source, or a raw "external" URL.
	 * @return string|null
	 */
	public static function resolve_trackable_provider( $resolved ) {
		if ( empty( $resolved ) ) {
			return null;
		}

		if ( self::is_html5_video_link( $resolved ) ) {
			return 'html5';
		}

		$host = wp_parse_url( $resolved, PHP_URL_HOST );
		if ( $host && ( str_contains( $host, 'youtube.com' ) || str_contains( $host, 'youtu.be' ) ) ) {
			return 'youtube';
		}

		if ( self::vimeo_id_from_url( $resolved ) ) {
			return 'vimeo';
		}

		return null;
	}

	/**
	 * Rewrite a video_source array in place to the native provider shape
	 * (`youtube`/`vimeo`/`html5`) once `$resolved` has been matched by
	 * resolve_trackable_provider() — or leave `$video['url']` as the opaque
	 * `$fallback_url` shape (e.g. parse_embedded_url()'s {url,allow,...})
	 * if nothing matched. Shared by the `embedded` and `external` branches
	 * of Lesson::render_lesson() so both stay in sync as hosts are added.
	 *
	 * @param array  $video        The video_source array (by reference).
	 * @param string $resolved     The already-unwrapped URL to resolve —
	 *                             for a host WP's oEmbed recognizes, this may
	 *                             already be an iframe embed src rather than
	 *                             the canonical page URL.
	 * @param mixed  $fallback_url What to leave `$video['url']` as when
	 *                             nothing matches (kept opaque/untracked).
	 */
	public static function apply_resolved_video_provider( array &$video, $resolved, $fallback_url ) {
		$provider = self::resolve_trackable_provider( $resolved );

		switch ( $provider ) {
			case 'youtube':
				$video['type'] = 'youtube';
				$video['url']  = self::youtube_id_from_url( $resolved );
				break;
			case 'vimeo':
				$video['type'] = 'vimeo';
				$video['url']  = self::vimeo_id_from_url( $resolved );
				break;
			case 'html5':
				$video['type'] = 'html5';
				$video['url']  = $resolved;
				break;
			default:
				$video['url'] = $fallback_url;
				break;
		}
	}

	/**
	 * Every video_source type KodezenPlayer natively plays + tracks. Kept as
	 * one list so is_trackable_video_source() and anything else that needs
	 * "is this a native player type" stay in sync as providers are added.
	 */
	public static function native_player_provider_types() {
		return [ 'html5', 'youtube', 'vimeo' ];
	}

	/**
	 * Whether a lesson's video source can actually be watch-tracked — i.e. it
	 * plays through a player that reports progress back to the server
	 * (a native provider type, or a direct match for one pasted as an
	 * "external"/"embedded" source), as opposed to an opaque third-party
	 * iframe with no completion signal at all. Used to gate both the
	 * frontend's resume/lock-seek data and the server's watch-percentage
	 * completion check consistently.
	 *
	 * @param string       $type The stored video_source type.
	 * @param string|array $url  The stored/resolved URL — a plain string, or
	 *                           (for an already-resolved "embedded" source)
	 *                           the array shape returned by parse_embedded_url().
	 */
	public static function is_trackable_video_source( $type, $url ) {
		if ( in_array( $type, self::native_player_provider_types(), true ) ) {
			return true;
		}

		if ( ! in_array( $type, [ 'external', 'embedded' ], true ) ) {
			return false;
		}

		if ( 'embedded' === $type ) {
			$resolved = is_array( $url ) ? ( $url['url'] ?? '' ) : ( self::parse_embedded_url( (string) $url )['url'] ?? '' );
		} else {
			$resolved = is_array( $url ) ? ( $url['url'] ?? '' ) : $url;
		}

		return (bool) self::resolve_trackable_provider( $resolved );
	}

	/**
	 * Whether a lesson's video source is the opaque third-party iframe
	 * fallback (Wistia, Vidyard, Twitch, SoundCloud, Mixcloud, Facebook,
	 * Kaltura, or any other embed/external source with no native provider
	 * match) that nonetheless has a real src to play. A cross-origin iframe
	 * reports no playback position or duration to the parent page, so these
	 * lessons get a coarse dwell-time (seconds the lesson stayed open and
	 * visible) watch gate instead of the real percent-of-duration one.
	 *
	 * @param string       $type The stored video_source type.
	 * @param string|array $url  The stored/resolved URL — same shape accepted
	 *                           by is_trackable_video_source().
	 */
	public static function is_dwell_trackable_video_source( $type, $url ) {
		if ( ! in_array( $type, [ 'external', 'embedded' ], true ) ) {
			return false;
		}

		if ( 'embedded' === $type ) {
			$resolved = is_array( $url ) ? ( $url['url'] ?? '' ) : ( self::parse_embedded_url( (string) $url )['url'] ?? '' );
		} else {
			$resolved = is_array( $url ) ? ( $url['url'] ?? '' ) : $url;
		}

		// A native match means the real percent-of-duration gate applies instead.
		if ( self::resolve_trackable_provider( $resolved ) ) {
			return false;
		}

		return ! empty( $resolved );
	}

	public static function gumlet_id_from_url( $url ) {
		if ( preg_match( '/src="([^"]+)"/', $url, $srcMatch ) ) {
			$src = $srcMatch[1];

			// Step 2: extract the ID from the src URL after /embed/
			if ( preg_match( '#/embed/([^?]+)#', $src, $idMatch ) ) {
				$id = $idMatch[1];
				return $id;
			}
		}
	}

	/**
	 * Extract ScreenPal video ID from embed markup or URL.
	 *
	 * @param string $content Embed markup or URL.
	 * @return string|null Video ID on success, null on failure.
	 */
	public static function screenpal_id_from_url( $content ) {

		if ( empty( $content ) || ! is_string( $content ) ) {
			return null;
		}

		$pattern = '#data-id="([^"]+)"|(?:player|watch)/([A-Za-z0-9]+)#';

		if ( preg_match( $pattern, $content, $matches ) ) {

			// First capturing group (data-id) OR second (player/)
			$video_id = ! empty( $matches[1] ) ? $matches[1] : $matches[2];

			return sanitize_text_field( $video_id );
		}

		return null;
	}

	public static function generate_video_embed_url( $url ) {
		if ( strpos( $url, 'youtube' ) > 0 || strpos( $url, 'youtu.be' ) > 0 ) {
			return 'https://www.youtube.com/embed/' . self::youtube_id_from_url( $url );
		} elseif ( strpos( $url, 'vimeo' ) > 0 ) {
			return 'https://player.vimeo.com/video/' . self::vimeo_id_from_url( $url ) . '?title=0&byline=0';
		} elseif ( strpos( $url, 'gumlet' ) > 0 ) {
			return 'https://play.gumlet.io/embed/' . self::gumlet_id_from_url( $url );
		} elseif ( strpos( $url, 'screenpal' ) > 0 ) {
			return 'https://go.screenpal.com/player/' . self::screenpal_id_from_url( $url );
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
		$embedObject = wp_oembed_get( $url, $args = '' );
		if ( $embedObject ) {
			return self::parse_embedded_url( $embedObject );
		}

		return array(
			'url'   => self::generate_video_embed_url( $url ),
			'allow' => '',
		);
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

	/**
	 * Whether a curriculum item's content must be rendered through the
	 * isolated full-page frame (vs. inline in the SPA). True when it contains
	 * a registered shortcode or produces inline <script> — cases whose
	 * enqueued assets never load over AJAX.
	 *
	 * @param mixed  $raw
	 * @param string $rendered
	 */
	public static function content_needs_frame( $raw, $rendered = '' ) {
		if ( is_string( $rendered ) && preg_match( '/<script[\\s>]/i', $rendered ) ) {
			return true;
		}
		if ( is_string( $raw ) && '' !== trim( $raw ) ) {
			$pattern = get_shortcode_regex();
			if ( preg_match( '/' . $pattern . '/', $raw, $matches ) && ! empty( $matches[2] ) ) {
				return true;
			}
		}
		return false;
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
		// .m3u8 (HLS — e.g. Mux) plays through the same native <video> element
		// as mp4/webm/ogg; KodezenPlayer's html5 provider picks hls.js for it.
		$pattern = '/\.mp4$|\.webm$|\.ogg$|\.m3u8$/i';

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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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

	public static function get_post_by_name( $page_name, $post_type = 'page' ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$page = $wpdb->get_var( $wpdb->prepare(
			"SELECT ID
			FROM $wpdb->posts
			WHERE post_name = %s
			AND post_type = %s",
			$page_name,
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
				++$suffix;
			}

			$username .= $suffix;
		}

		return $username;
	}

	public static function has_user_meta_exists( $author_id, $meta_key, $meta_value ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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

	public static function get_page_by_slug( $page_slug, $post_type = 'page' ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$page = $wpdb->get_var( $wpdb->prepare(
			"SELECT ID
			FROM $wpdb->posts
			WHERE post_name = %s
			AND post_type = %s",
			$page_slug,
			$post_type
		) );

		if ( $page ) {
			return get_post( $page, OBJECT );
		}

		return null;
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
				return (int) $lesson['ID'];
			}

			// A lesson still kept as a post, from before Academy's lessons table.
			$post = self::get_post_by_name( $slug, 'academy_lessons' );

			return $post ? (int) $post->ID : 0;
		} elseif ( 'quiz' === $topic_type ) {
			$quiz = self::get_post_by_name( $slug, 'academy_quiz' );

			return $quiz ? $quiz->ID : 0;
		} elseif ( 'assignment' === $topic_type ) {
			$assignment = self::get_post_by_name( $slug, 'academy_assignments' );

			return ( ! empty( $assignment ) ) ? $assignment->ID : 0;
		} elseif ( 'meeting' === $topic_type ) {
			$meeting = self::get_post_by_name( $slug, 'academy_meeting' );

			return ! empty( $meeting ) ? $meeting->ID : 0;
		} elseif ( 'booking' === $topic_type ) {
			$booking = self::get_post_by_name( $slug, 'academy_booking' );

			return $booking ? $booking->ID : 0;
		} elseif ( 'quizpress_quiz' === $topic_type ) {
			$quizpress_quiz = self::get_post_by_name( $slug, 'quizpress_quiz' );

			return $quizpress_quiz ? $quizpress_quiz->ID : 0;
		}//end if

		return false;
	}

	/**
	 * Whether lessons are served as their own server-rendered pages
	 * (/course/{course}/{type}/{topic}) — the PHP learn page or the block
	 * learn page — rather than inside the React player.
	 *
	 * @return bool
	 */
	public static function is_server_learn_page() {
		return 'blocks' === self::get_settings( 'learn_page_engine', 'react' )
			|| (bool) self::get_settings( 'is_enabled_lessons_php_render' );
	}

	/**
	 * Whether topic links use the server-rendered page URLs. The PHP learn page
	 * also needs its lessons page picked; the block learn page does not.
	 *
	 * @return bool
	 */
	public static function uses_server_learn_links() {
		return 'blocks' === self::get_settings( 'learn_page_engine', 'react' )
			|| ( self::get_settings( 'is_enabled_lessons_php_render' ) && self::get_settings( 'lessons_page' ) );
	}

	public static function get_start_course_permalink( $course_id ): string {
		if ( self::uses_server_learn_links() ) {
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
		if ( self::uses_server_learn_links() ) {
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
		$is_administrator = current_user_can( 'manage_options' );
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

		if ( is_string( $topic_name ) && isset( $topic['slug'] ) && $topic_name === (string) $topic['slug'] ) {
			return true;
		}

		return false;
	}
	public static function get_frontend_dashboard_menu_items() {
		$items = array(
			'index'           => array(
				'label' => __( 'Dashboard', 'academy' ),
				'area'  => 'all',
				'icon'  => 'academy-icon academy-icon--grid-two',
				'public' => true,
				'priority' => 5,
			),
			'profile'           => array(
				'label' => __( 'My Profile', 'academy' ),
				'area'  => 'account',
				'icon'  => 'academy-icon academy-icon--profile-two',
				'public' => true,
				'priority' => 10,
			),
			'enrolled-courses'           => array(
				'label' => __( 'Enrolled Courses', 'academy' ),
				'area'  => 'learning',
				'icon'  => 'academy-icon academy-icon--enrollement',
				'public' => true,
				'priority' => 15,
			),
			'active-courses'           => array(
				'label' => __( 'Enrolled Courses', 'academy' ),
				'area'  => 'learning',
				'public' => false,
				'priority' => 15,
			),
			'complete-courses'           => array(
				'label' => __( 'Enrolled Courses', 'academy' ),
				'area'  => 'learning',
				'public' => false,
				'priority' => 15,
			),
			'wishlist'           => array(
				'label' => __( 'Wishlist', 'academy' ),
				'area'  => 'learning',
				'icon'  => 'academy-icon academy-icon--wishlist',
				'public' => true,
				'priority' => 20,
			),
			'reviews'           => array(
				'label' => __( 'My Reviews', 'academy' ),
				'area'  => 'learning',
				'icon'  => 'academy-icon academy-icon--star-alt',
				'public' => true,
				'priority' => 25,
			),
			'received-reviews'           => array(
				'label' => __( 'Received Reviews', 'academy' ),
				'area'  => 'teaching',
				'icon'  => 'academy-icon academy-icon--star-alt',
				'public' => true,
				'priority' => 25,
			),
			'settings'           => array(
				'label' => __( 'Settings', 'academy' ),
				'area'  => 'account',
				'icon'  => 'academy-icon academy-icon--settings',
				'public' => true,
				'priority' => 50,
			),
			'reset-password'           => array(
				'label' => __( 'Reset Password', 'academy' ),
				'area'  => 'account',
				'public' => false,
				'priority' => 50,
			),
			'logout'           => array(
				'label' => __( 'Log Out', 'academy' ),
				'area'  => 'account',
				'icon'  => 'academy-icon academy-icon--logout',
				'priority' => 99,
				'public' => true,
			),
		);

		if ( self::get_settings( 'is_enable_apply_instructor_menu' ) ) {
			$items['become-an-instructor'] = array(
				'label' => __( 'Become An Instructor', 'academy' ),
				'area' => 'learning',
				'icon'  => 'academy-icon academy-icon--instructor',
				'public' => ! current_user_can( 'manage_academy_instructor' ) ? true : false,
				'priority' => 2,
			);
		}

		if ( current_user_can( 'manage_academy_instructor' ) ) {
			$items['courses'] = array(
				'label' => __( 'Courses', 'academy' ),
				'area' => 'teaching',
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
				'area' => 'teaching',
				'icon'  => 'academy-icon academy-icon--Lesson',
				'public' => true,
				'priority' => 35,
			);
			$items['announcements'] = array(
				'label' => __( 'Announcements', 'academy' ),
				'area' => 'teaching',
				'icon'  => 'academy-icon academy-icon--announcement',
				'public' => true,
				'priority' => 45,
			);
			$items['question-answer'] = array(
				'label' => __( 'Question & Answer', 'academy' ),
				'area' => 'teaching',
				'icon'  => 'academy-icon academy-icon--question',
				'public' => true,
				'priority' => 40,
			);
			$items['students'] = array(
				'label' => __( 'Students', 'academy' ),
				'area' => 'teaching',
				'icon'  => 'academy-icon academy-icon--students-two',
				'public' => true,
				'priority' => 42,
			);
			if ( self::get_addon_active_status( 'multi_instructor' ) && self::get_settings( 'is_enabled_earning' ) ) {
				$items['withdrawal']  = array(
					'label' => __( 'Withdrawal', 'academy' ),
					'area' => 'teaching',
					'public' => true,
					'icon'  => 'academy-icon academy-icon--wallet',
					'priority' => 49,
				);
				$items['withdraw']  = array(
					'label' => __( 'Withdraw', 'academy' ),
					'area' => 'teaching',
					'public' => false,
					'priority' => 50,
				);
			}
		}//end if

		if ( self::is_active_woocommerce() || self::is_active_storeengine() ) {
			$items['purchase-history'] = array(
				'label' => __( 'Purchase History', 'academy' ),
				'area' => 'learning',
				'icon' => 'academy-icon academy-icon--purchase',
				'public' => true,
				'priority' => 26,
			);
		}
		// Grades is a quiz-results page, so it lives and dies with the quizzes
		// addon: without it every row reads "No quizzes" and the page is just a
		// worse copy of Enrolled Courses. Gated the same way `download-certificate`
		// depends on `certificates`. This also drops the /grades/ rewrite rule,
		// which PermalinkRewrite builds from this same list — and the quizzes
		// addon already flushes rules on both activation and deactivation, so
		// the route appears and disappears with it.
		if ( self::get_addon_active_status( 'quizzes' ) ) {
			$items['grades'] = array(
				'label' => __( 'Grades', 'academy' ),
				'area'  => 'learning',
				'icon'  => 'academy-icon academy-icon--gradebook',
				'public' => true,
				'priority' => 16,
			);
		}

		if ( self::get_addon_active_status( 'certificates' ) ) {
			$items['download-certificate'] = array(
				'label' => __( 'Download Certificates', 'academy' ),
				'area' => 'learning',
				'icon' => 'academy-icon academy-icon--certificate',
				'public' => true,
				'priority' => 17,
			);
		}

		return apply_filters( 'academy/frontend_dashboard_menu_items', $items );
	}

	/**
	 * The areas a dashboard can be viewed as.
	 *
	 * The dashboard used to be one flat list holding everything a person could
	 * possibly do — an instructor got their learner items and their teaching
	 * items interleaved by priority number, and an administrator got the union
	 * of every role at once. An area is "what am I here to do right now":
	 * Learning, Teaching, or Family. Each menu item declares which one (or
	 * ones) it belongs to, and the sidebar shows a single area at a time.
	 *
	 * `account` is not an area you switch to — Profile, Settings and Log Out
	 * belong to the person, not the role, so they show in every view.
	 */
	public static function dashboard_areas() {
		$areas = array(
			'learning' => array(
				'label'     => __( 'Learning', 'academy' ),
				'icon'      => 'academy-icon academy-icon--course-cap',
				'order'     => 10,
				// Everyone can learn, so this is always somewhere to stand.
				'available' => true,
			),
			'teaching' => array(
				'label'     => __( 'Teaching', 'academy' ),
				'icon'      => 'academy-icon academy-icon--instructor',
				'order'     => 20,
				'available' => current_user_can( 'manage_academy_instructor' ),
			),
			'family'   => array(
				'label'     => __( 'Family', 'academy' ),
				'icon'      => 'academy-icon academy-icon--profile-two',
				'order'     => 30,
				'available' => self::has_family_area(),
			),
		);

		/**
		 * Register a dashboard area, or change whether one is available.
		 *
		 * @param array $areas key => [ label, icon, order, available ].
		 */
		return apply_filters( 'academy/frontend_dashboard_areas', $areas );
	}

	/** Whether this person has anyone to be a guardian of. */
	private static function has_family_area() {
		// The Store class autoloads whether or not the addon is on — its table
		// only exists once Guardian::init() has actually installed it (which it
		// skips entirely while the addon is off, see includes/guardian.php).
		// class_exists() alone let this query a table that was never created.
		if ( ! is_user_logged_in() || ! self::get_addon_active_status( 'guardian' ) || ! class_exists( '\Academy\Guardian\Store' ) ) {
			return false;
		}
		return (bool) \Academy\Guardian\Store::get_children( get_current_user_id() );
	}

	/** The areas this user may switch between, in order. */
	public static function available_dashboard_areas() {
		$areas = array_filter( self::dashboard_areas(), function ( $area ) {
			return ! empty( $area['available'] );
		} );
		uasort( $areas, function ( $a, $b ) {
			return ( $a['order'] ?? 50 ) <=> ( $b['order'] ?? 50 );
		} );
		return $areas;
	}

	/**
	 * Which area the dashboard is being viewed as.
	 *
	 * `?view=` wins and is remembered, so the switcher is a plain link and the
	 * choice survives the next visit. An area the user no longer has — a
	 * revoked instructor, say — falls back to the first one they do.
	 */
	public static function current_dashboard_view() {
		if ( self::$forced_dashboard_view ) {
			return self::$forced_dashboard_view;
		}

		static $resolved = null;
		if ( null !== $resolved ) {
			return $resolved;
		}

		$available = self::available_dashboard_areas();
		$user_id   = get_current_user_id();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$requested = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : '';
		if ( $requested && isset( $available[ $requested ] ) ) {
			if ( $user_id ) {
				update_user_meta( $user_id, 'academy_dashboard_view', $requested );
			}
			$resolved = $requested;
			return $resolved;
		}

		$saved = $user_id ? get_user_meta( $user_id, 'academy_dashboard_view', true ) : '';
		if ( $saved && isset( $available[ $saved ] ) ) {
			$resolved = $saved;
			return $resolved;
		}

		$resolved = self::default_dashboard_view( $available );
		return $resolved;
	}

	/**
	 * Where someone lands before they have ever chosen.
	 *
	 * The most specific hat they wear, not the first in the list: an instructor
	 * opens on Teaching, a guardian who doesn't teach opens on Family, and
	 * everyone else opens on Learning.
	 *
	 * @param mixed $available
	 */
	public static function default_dashboard_view( $available = null ) {
		$available = null === $available ? self::available_dashboard_areas() : $available;
		foreach ( array( 'teaching', 'family', 'learning' ) as $preferred ) {
			if ( isset( $available[ $preferred ] ) ) {
				return $preferred;
			}
		}
		return (string) array_key_first( $available );
	}

	/**
	 * Force the view for the rest of this request.
	 *
	 * Opening a teaching page from a link while standing in Learning should
	 * move the sidebar with you, rather than showing a menu that doesn't
	 * contain the page you are looking at.
	 *
	 * @param mixed $area
	 */
	public static function set_dashboard_view( $area ) {
		if ( isset( self::available_dashboard_areas()[ $area ] ) ) {
			self::$forced_dashboard_view = $area;
		}
	}

	/** @var string Set only by set_dashboard_view(), for this request. */
	private static $forced_dashboard_view = '';

	/**
	 * Whether a menu item belongs in the area currently being viewed.
	 *
	 * @param array $item
	 * @param mixed $view
	 */
	public static function dashboard_item_in_view( $item, $view = null ) {
		$view = $view ? $view : self::current_dashboard_view();
		// An item with no area is a third-party addition that predates areas.
		// Leave it where everyone can still reach it rather than hiding it.
		$area = $item['area'] ?? 'all';
		if ( 'all' === $area || 'account' === $area ) {
			return true;
		}
		return in_array( $view, (array) $area, true );
	}

	/**
	 * The area a given dashboard page belongs to, or '' when it spans them.
	 *
	 * @param string $menu_key
	 */
	public static function dashboard_area_of( $menu_key ) {
		$menu = self::get_frontend_dashboard_menu_items();
		$area = $menu[ $menu_key ]['area'] ?? 'all';
		if ( 'all' === $area || 'account' === $area ) {
			return '';
		}
		$area = (array) $area;
		return 1 === count( $area ) ? $area[0] : '';
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
			return $menu[ $path ]['child_items'][ $sub_path ]['label'] ?? '';
		}
		return $menu[ $path ]['label'] ?? '';
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
		// Back to the dashboard page, which shows the login form once logged out
		// (the setting is frontend_dashboard_page; "dashboard_page" doesn't exist,
		// so this always fell back to the home page).
		$redirect = $redirect ? $redirect : apply_filters( 'academy/logout_default_redirect_url', self::get_page_permalink( 'frontend_dashboard_page' ) );

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

	public static function get_time_different_dynamically_for_any_time( $given_time ): string {
		$current_time = new \DateTime();
		$given_time = new \DateTime( $given_time );
		$time_difference = $current_time->diff( $given_time );

		if ( $time_difference->y > 0 ) {
			return esc_html(
				sprintf(
					/* translators: %d: number of years */
					__( '%d years ago', 'academy' ),
					$time_difference->y
				)
			);
		} elseif ( $time_difference->m > 0 ) {
			return esc_html(
				sprintf(
					/* translators: %d: number of months */
					__( '%d months ago', 'academy' ),
					$time_difference->m
				)
			);
		} elseif ( $time_difference->d > 0 ) {
			return esc_html(
				sprintf(
					/* translators: %d: number of days */
					__( '%d days ago', 'academy' ),
					$time_difference->d
				)
			);
		} elseif ( $time_difference->h > 0 ) {
			return esc_html(
				sprintf(
					/* translators: %d: number of hours */
					__( '%d hours ago', 'academy' ),
					$time_difference->h
				)
			);
		} elseif ( $time_difference->i > 0 ) {
			return esc_html(
				sprintf(
					/* translators: %d: number of minutes */
					__( '%d minutes ago', 'academy' ),
					$time_difference->i
				)
			);
		} else {
			return esc_html__( 'Just now', 'academy' );
		}//end if
	}
	public static function get_course_curriculum_array( $course_id ) {
		$course_curriculum = get_post_meta( $course_id, 'academy_course_curriculum', true );
		$prepare_curriculum = array();
		if ( is_array( $course_curriculum ) ) {
			foreach ( $course_curriculum as $curriculum ) {
				if ( is_array( $curriculum['topics'] ) ) {
					foreach ( $curriculum['topics'] as $topic ) {
						$prepare_curriculum[] = $topic;
					}
				}
			}
		}
		return $prepare_curriculum;
	}
	public static function get_next_topic( $topics, $topic_id, $topic_type ) {
		foreach ( $topics as $i => $topic ) {
			$subs = $topic['topics'] ?? [];

			foreach ( $subs as $j => $sub ) {
				if ( (int) $sub['id'] === (int) $topic_id && $sub['type'] === $topic_type ) {
					return $subs[ $j + 1 ] ?? $topics[ $i + 1 ] ?? false;
				}
			}

			if ( (int) $topic['id'] === (int) $topic_id && $topic['type'] === $topic_type ) {
				return $topics[ $i + 1 ] ?? $subs[0] ?? false;
			}
		}
		return false;
	}

	/**
	 * Build the `orderby` a course query needs to rank featured courses
	 * among themselves, and flag the query for `filter_sticky_posts_orderby()`
	 * to pin them above everything else.
	 *
	 * Priority is `menu_order` — the course editor's featured drag-list writes
	 * it, and `maybe_assign_sticky_priority()` appends a new featured course to
	 * the end. It is a tiebreak *within* the featured group only: whether a
	 * course is featured at all is decided solely by the meta flag, so a
	 * leftover priority on an un-featured course can never float it up.
	 *
	 * The sticky-first boolean is deliberately NOT expressed as a named
	 * meta_query orderby clause. WP_Meta_Query gives the first clause in a
	 * query an unaliased JOIN whose ON checks only `post_id` (meta_key is
	 * tested in WHERE), so once GROUP BY collapses a post's joined meta rows,
	 * ORDER BY on that column reads an arbitrary row rather than the flag —
	 * featured courses came out scattered. `filter_sticky_posts_orderby()`
	 * prepends a correlated EXISTS instead, which needs no JOIN, no GROUP BY
	 * and no meta_query at all.
	 *
	 * @param string $orderby The caller's own orderby key (e.g. 'date', 'post_title', 'menu_order').
	 * @param string $order   The caller's own order direction ('ASC'/'DESC').
	 * @return array{orderby: array, academy_sticky_first: bool} Query args to merge into the caller's own.
	 */
	public static function apply_sticky_course_ordering( $orderby, $order = 'DESC' ) {
		$order = 'ASC' === strtoupper( (string) $order ) ? 'ASC' : 'DESC';

		// A caller already ordering by menu_order keeps its own direction —
		// re-adding the key would be a duplicate, and silently flipping a
		// user-chosen "menu order" sort to ASC is not this function's call.
		if ( 'menu_order' === $orderby ) {
			$sticky_orderby = array( 'menu_order' => $order );
		} else {
			$sticky_orderby = array( 'menu_order' => 'ASC' );

			// Guard against orderby keys WP_Query does not accept (the course
			// search passes 'publish_date', for one). Left in, WP drops the
			// invalid key and the query silently degrades to menu_order only;
			// dropped here, WP's own "no valid key" fallback of post_date
			// still applies as it did before sticky ordering existed.
			if ( in_array( $orderby, self::get_supported_course_orderby_keys(), true ) ) {
				$sticky_orderby[ $orderby ] = $order;
			} else {
				$sticky_orderby['date'] = $order;
			}
		}

		return array(
			'orderby'              => $sticky_orderby,
			self::STICKY_QUERY_VAR => true,
		);
	}

	/**
	 * The `orderby` keys WP_Query accepts that a course listing can pass
	 * through. Anything else is normalised to `date` by
	 * `apply_sticky_course_ordering()` rather than being silently dropped.
	 */
	public static function get_supported_course_orderby_keys() {
		return array(
			'ID',
			'author',
			'title',
			'post_title',
			'name',
			'date',
			'post_date',
			'modified',
			'post_modified',
			'parent',
			'rand',
			'comment_count',
			'menu_order',
			'relevance',
		);
	}

	/**
	 * `posts_orderby` filter: prepends "is this course featured?" to the
	 * query's own ORDER BY, so featured courses lead each listing while the
	 * caller's ordering still applies within the featured and non-featured
	 * groups.
	 *
	 * Hooked once, globally, in Template::dispatch_hook(), but it acts only on
	 * queries that opted in via `apply_sticky_course_ordering()` — see
	 * STICKY_QUERY_VAR. Opting in by query var rather than by sniffing
	 * `post_type` matters for taxonomy archives: WP resolves a tax archive's
	 * post type into a local variable inside WP_Query::get_posts() and never
	 * writes it back, so `$query->get( 'post_type' )` is an empty string
	 * there and a post-type check would skip the very listings the category
	 * pages render.
	 *
	 * @param mixed            $orderby_sql
	 * @param \WP_Query|string $query
	 */
	public static function filter_sticky_posts_orderby( $orderby_sql, $query ) {
		if ( ! $query->get( self::STICKY_QUERY_VAR ) ) {
			return $orderby_sql;
		}

		global $wpdb;
		$sticky_expr = $wpdb->prepare(
			"EXISTS ( SELECT 1 FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID AND {$wpdb->postmeta}.meta_key = %s AND {$wpdb->postmeta}.meta_value = '1' ) DESC",
			self::STICKY_META_KEY
		);

		return $orderby_sql ? $sticky_expr . ', ' . $orderby_sql : $sticky_expr;
	}

	/**
	 * Whether a course carries the featured flag.
	 *
	 * @param int $course_id
	 */
	public static function is_course_sticky( $course_id ) {
		return (bool) get_post_meta( $course_id, self::STICKY_META_KEY, true );
	}

	/**
	 * Highest priority currently in use across featured courses, or 0 when
	 * none has one yet. Used to append a newly-featured course to the end of
	 * the list instead of dropping it in at an arbitrary position.
	 */
	public static function get_max_sticky_priority() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX( p.menu_order ) FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
				WHERE p.post_type = %s AND p.post_status != 'trash'
				AND pm.meta_key = %s AND pm.meta_value = '1'",
				'academy_courses',
				self::STICKY_META_KEY
			)
		);
	}

	public static function minify_js( ?string $js ): string {
		$js = preg_replace( '/\/\*.*?\*\//s', '', $js );
		$js = preg_replace( '/\/\/.*?[\r\n]/', '', $js );
		$js = preg_replace( '/\s+/', ' ', $js );
		$js = preg_replace( '/\s*([{}();,:])\s*/', '$1', $js );
		$js = trim( $js );
		return $js;
	}

	public static function get_course_expire_duration( $course_id ) {
		$user_id = get_current_user_id();
		// course expire enrollment time
		$expire_enrollment = (int) get_post_meta( $course_id, 'academy_course_expire_enrollment', true );
		// get extend time
		$extend_time = 0;
		if ( self::is_enrolled( $course_id, $user_id ) ) {
			$extend_time = (int) get_user_meta( $user_id, "academy_course_extended_expire_time_{$course_id}", true );
		}
		// total expire time
		$total_duration = $expire_enrollment + $extend_time;
		$current_time = current_time( 'timestamp' );
		$expire_time = strtotime( "+{$total_duration} days", $current_time );
		return $expire_time > $current_time ? wp_date( 'M j, Y', $expire_time ) : 0;
	}
}
