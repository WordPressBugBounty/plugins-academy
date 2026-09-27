<?php
namespace Academy;

use Academy\Blocks\CourseData;
use Academy\Blocks\CourseFilters;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Academy LMS blocks.
 *
 * Course pages and grids are built from core blocks (Query Loop, Post Title,
 * Featured Image, Post Terms) plus Academy blocks for course facts and course
 * page sections, so every part can be moved and styled with the editor's own
 * controls.
 */
class Blocks {

	/**
	 * Script handle shared by every block's editor UI.
	 */
	const EDITOR_SCRIPT = 'academy-blocks-editor';

	/**
	 * Front-end script that applies course filters and sorting on change.
	 */
	const FILTERS_VIEW_SCRIPT = 'academy-course-filters-view';

	/**
	 * Option: "blocks" for the block-based course templates, "legacy" for the
	 * original shortcode layout.
	 */
	const TEMPLATE_STYLE_KEY = 'templates';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		$self = new self();
		add_action( 'init', [ $self, 'register_blocks' ] );
		add_action( 'init', [ $self, 'maybe_set_template_style' ], 20 );
		add_action( 'enqueue_block_editor_assets', [ $self, 'editor_settings' ] );
		add_filter( 'block_categories_all', [ $self, 'register_category' ] );
		add_action( 'rest_api_init', [ $self, 'register_rest_fields' ] );
		add_filter( 'render_block', [ $self, 'enqueue_typography_style' ], 10, 2 );
		add_filter( 'pre_render_block', [ $self, 'start_featured_image' ], 10, 2 );
		add_filter( 'render_block_core/post-featured-image', [ $self, 'end_featured_image' ], 1 );
		add_filter( 'post_thumbnail_html', [ $self, 'course_image_placeholder' ], 10, 5 );
		add_filter( 'render_block_core/post-excerpt', [ $self, 'card_excerpt_setting' ], 10, 2 );
		add_filter( 'render_block_data', [ $self, 'archive_columns_setting' ], 10, 3 );
		add_filter( 'query_loop_block_query_vars', [ CourseFilters::class, 'filter_query_loop' ], 10, 2 );
		add_action( 'pre_get_posts', [ CourseFilters::class, 'filter_main_query' ], 40 );
		add_action( 'init', [ $self, 'register_patterns' ], 30 );
		add_action( 'enqueue_block_editor_assets', [ $self, 'hide_replaced_blocks' ] );
		add_filter( 'render_block_core/post-terms', [ $self, 'post_terms_category_images' ], 10, 3 );
		add_filter( 'posts_clauses', [ CourseFilters::class, 'sort_clauses' ], 10, 2 );
		add_filter( 'academy/shortcode/login_form_is_user_logged_in', [ $self, 'show_forms_in_preview' ] );
		add_filter( 'academy/shortcode/password_reset_form_is_user_logged_in', [ $self, 'show_forms_in_preview' ] );
		add_action( 'wp_enqueue_scripts', [ $self, 'early_course_assets' ], 9 );
		add_filter( 'pre_load_script_translations', [ $self, 'editor_script_translations' ], 10, 4 );
		add_action( 'admin_notices', [ $self, 'template_style_notice' ] );
		add_action( 'admin_post_academy_course_templates', [ $self, 'handle_template_style' ] );
		CourseData::register_cache_hooks();
	}

	/**
	 * Register shared assets and every block in includes/blocks/.
	 *
	 * @return void
	 */
	public function register_blocks() {
		$asset_file = ACADEMY_ASSETS_DIR_PATH . sprintf( 'build/blocks.%s.asset.php', ACADEMY_VERSION );
		if ( file_exists( $asset_file ) ) {
			$asset = include $asset_file;
			wp_register_script(
				self::EDITOR_SCRIPT,
				ACADEMY_ASSETS_URI . sprintf( 'build/blocks.%s.js', ACADEMY_VERSION ),
				$asset['dependencies'],
				$asset['version'],
				true
			);
			wp_set_script_translations( self::EDITOR_SCRIPT, 'academy', ACADEMY_ROOT_DIR_PATH . 'languages' );
		}

		wp_register_script(
			self::FILTERS_VIEW_SCRIPT,
			plugins_url( 'blocks/course-filters/view.js', __FILE__ ),
			[],
			ACADEMY_VERSION . '.' . filemtime( __DIR__ . '/blocks/course-filters/view.js' ),
			[
				'in_footer' => true,
				'strategy'  => 'defer',
			]
		);

		foreach ( (array) glob( __DIR__ . '/blocks/*/block.json' ) as $metadata ) {
			$requires = self::requires_shortcode( basename( dirname( $metadata ) ) );
			if ( $requires && ! shortcode_exists( $requires ) ) {
				continue;
			}
			register_block_type( dirname( $metadata ) );
		}

		register_block_style(
			'core/post-terms',
			[
				'name'         => 'academy-with-images',
				'label'        => __( 'With category images', 'academy' ),
				'inline_style' => '.is-style-academy-with-images a{display:inline-flex;align-items:center;gap:.35em}.is-style-academy-with-images .academy-post-terms__category-thumb{width:1.4em;height:1.4em;border-radius:50%;object-fit:cover}',
			]
		);
	}

	/**
	 * Blocks that wrap a feature from Academy Pro, by the shortcode that feature
	 * adds. They are only offered when that feature is switched on.
	 *
	 * @param string $block Block folder name.
	 * @return string Shortcode tag, or '' when the block always applies.
	 */
	public static function requires_shortcode( $block ) {
		$map = [
			'certificate-verification' => 'academy_pro_certificate_verification',
			'social-login'             => 'academy_social_login',
		];

		return isset( $map[ $block ] ) ? $map[ $block ] : '';
	}

	/**
	 * Block patterns for course grids and course pages.
	 *
	 * @return void
	 */
	public function register_patterns() {
		register_block_pattern_category( 'academy', [ 'label' => __( 'Academy LMS', 'academy' ) ] );

		$patterns = [
			'course-grid-list'    => [
				'title'       => __( 'Course list', 'academy' ),
				'description' => __( 'Courses in a single column with the image beside the details.', 'academy' ),
				'blockTypes'  => [ 'core/query' ],
			],
			'course-grid-minimal' => [
				'title'       => __( 'Course grid: minimal', 'academy' ),
				'description' => __( 'Borderless course cards with image, title, instructor and price.', 'academy' ),
				'blockTypes'  => [ 'core/query' ],
			],
			'featured-courses'    => [
				'title'       => __( 'Featured courses', 'academy' ),
				'description' => __( 'A heading and three featured courses.', 'academy' ),
			],
			'course-catalog'      => [
				'title'       => __( 'Course catalog with filters', 'academy' ),
				'description' => __( 'Course filters beside a sortable course grid, for a courses page.', 'academy' ),
			],
			'course-page'         => [
				'title'         => __( 'Course page: two columns', 'academy' ),
				'description'   => __( 'Course details and sections beside a sticky enroll box.', 'academy' ),
				'templateTypes' => [ 'single-academy_courses' ],
			],
		];

		foreach ( $patterns as $slug => $pattern ) {
			$file = __DIR__ . '/blocks/patterns/' . $slug . '.php';
			if ( file_exists( $file ) ) {
				register_block_pattern(
					'academy/' . $slug,
					array_merge(
						$pattern,
						[
							'categories' => [ 'academy' ],
							'filePath'   => $file,
						]
					)
				);
			}
		}
	}

	/**
	 * Hide aBlocks' Academy blocks from the inserter now that Academy LMS has
	 * its own. Content that already uses them keeps working.
	 *
	 * @return void
	 */
	public function hide_replaced_blocks() {
		$replaced = [
			'ablocks/academy-courses',
			'ablocks/academy-enroll-form',
			'ablocks/academy-student-registration-form',
			'ablocks/academy-course-search',
			'ablocks/academy-instructor-registration-form',
			'ablocks/academy-pdf',
			'ablocks/academy-password-reset-form',
			'ablocks/academy-login-form',
			'ablocks/academy-course-media',
			'ablocks/academy-course-curriculums',
			'ablocks/academy-course-reviews',
			'ablocks/academy-review-form',
			'ablocks/academy-review-list',
			'ablocks/academy-addition-info',
			'ablocks/academy-course-description',
			'ablocks/academy-course-instructor',
			'ablocks/academy-enroll-content',
		];

		wp_add_inline_script(
			'wp-blocks',
			sprintf(
				'wp.hooks.addFilter("blocks.registerBlockType","academy/hide-replaced-blocks",function(settings,name){return %s.indexOf(name)>-1?Object.assign({},settings,{supports:Object.assign({},settings.supports,{inserter:false})}):settings;});',
				wp_json_encode( $replaced )
			),
			'after'
		);
	}

	/**
	 * Show the login and password reset forms (not the "already logged in"
	 * message) in the editor's block previews.
	 *
	 * @param bool $logged_in Whether the visitor counts as logged in.
	 * @return bool
	 */
	public function show_forms_in_preview( $logged_in ) {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST && false !== strpos( $uri, 'block-renderer' ) ) {
			return false;
		}

		return $logged_in;
	}

	/**
	 * Add category images to Post Terms blocks that use the "With category
	 * images" style, in any context (course grids included).
	 *
	 * @param string    $block_content Rendered block.
	 * @param array     $block         Parsed block.
	 * @param \WP_Block $instance      Block instance.
	 * @return string
	 */
	public function post_terms_category_images( $block_content, $block, $instance ) {
		$class_name = isset( $block['attrs']['className'] ) ? (string) $block['attrs']['className'] : '';
		if ( false === strpos( $class_name, 'is-style-academy-with-images' ) || 'academy_courses_category' !== ( $block['attrs']['term'] ?? '' ) ) {
			return $block_content;
		}
		// Single course pages already get images from Frontend\Template.
		if ( false !== strpos( $block_content, 'academy-post-terms__category-thumb' ) ) {
			return $block_content;
		}

		$post_id = isset( $instance->context['postId'] ) ? (int) $instance->context['postId'] : (int) get_the_ID();
		$terms   = get_the_terms( $post_id, 'academy_courses_category' );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return $block_content;
		}

		foreach ( $terms as $term ) {
			$link = get_term_link( $term );
			if ( is_wp_error( $link ) ) {
				continue;
			}
			$image         = sprintf( '<img class="academy-post-terms__category-thumb" src="%s" alt="" loading="lazy" />', esc_url( Helper::get_the_course_category_image_url( $term->term_id ) ) );
			$block_content = (string) preg_replace( '/(<a\s+href="' . preg_quote( esc_url( $link ), '/' ) . '"[^>]*>)/', '$1' . $image, $block_content, 1 );
		}

		return $block_content;
	}

	/**
	 * Blocks that print Academy's own course page markup and need its styles
	 * and scripts.
	 */
	const ASSET_BLOCKS = [
		'academy/course-media',
		'academy/course-description',
		'academy/course-additional-info',
		'academy/course-curriculum',
		'academy/course-instructor-profiles',
		'academy/course-rating-summary',
		'academy/course-reviews',
		'academy/course-review-form',
		'academy/course-attachments',
		'academy/course-enroll-card',
		'academy/course-enroll-button',
		'academy/course-wishlist',
		'academy/login-form',
		'academy/student-registration-form',
		'academy/instructor-registration-form',
		'academy/password-reset-form',
		'academy/enroll-form',
		'academy/student-dashboard',
		'academy/enrolled-courses',
		'academy/pdf',
	];

	/**
	 * Source files of the editor script, for translations generated from them.
	 */
	const EDITOR_SOURCES = [
		'dev_academy/blocks/index.js',
		'dev_academy/blocks/course-price.js',
		'dev_academy/blocks/course-rating.js',
		'dev_academy/blocks/course-instructors.js',
		'dev_academy/blocks/course-meta.js',
		'dev_academy/blocks/course-enroll-button.js',
		'dev_academy/blocks/card-extras.js',
		'dev_academy/blocks/course-grid.js',
		'dev_academy/blocks/course-sections.js',
		'dev_academy/blocks/shared/use-course.js',
		'dev_academy/blocks/shared/icons.js',
	];

	/**
	 * Load Academy's styles in the page head when the post content uses a block
	 * that needs them, instead of late in the footer.
	 *
	 * @return void
	 */
	public function early_course_assets() {
		if ( ! is_singular() ) {
			return;
		}
		$post = get_queried_object();
		if ( ! $post instanceof \WP_Post || false === strpos( $post->post_content, '<!-- wp:academy/' ) ) {
			return;
		}
		foreach ( self::ASSET_BLOCKS as $name ) {
			if ( has_block( $name, $post ) ) {
				self::use_course_assets();
				return;
			}
		}
	}

	/**
	 * Translations for the editor script. Language packs name JSON files after
	 * the source file each string came from, while the script is one built
	 * file, so merge the files of all its sources.
	 *
	 * @param string|false|null $translations Translations JSON, or null to load the file.
	 * @param string|false      $file         Translation file path.
	 * @param string            $handle       Script handle.
	 * @param string            $domain       Text domain.
	 * @return string|false|null
	 */
	public function editor_script_translations( $translations, $file, $handle, $domain ) {
		if ( null !== $translations || self::EDITOR_SCRIPT !== $handle || 'academy' !== $domain ) {
			return $translations;
		}
		$locale = determine_locale();
		if ( 'en_US' === $locale ) {
			return $translations;
		}

		$merged = null;
		foreach ( self::EDITOR_SOURCES as $source ) {
			$name = 'academy-' . $locale . '-' . md5( $source ) . '.json';
			foreach ( [ WP_LANG_DIR . '/plugins/' . $name, ACADEMY_ROOT_DIR_PATH . 'languages/' . $name ] as $path ) {
				if ( ! is_readable( $path ) ) {
					continue;
				}
				// phpcs:disable WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- local file, not a remote URL
				$data = json_decode( (string) file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
				// phpcs:enable WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
				if ( empty( $data['locale_data']['messages'] ) ) {
					break;
				}
				if ( null === $merged ) {
					$merged = $data;
				} else {
					$merged['locale_data']['messages'] = array_merge( $merged['locale_data']['messages'], $data['locale_data']['messages'] );
				}
				break;
			}
		}

		return null === $merged ? $translations : wp_json_encode( $merged );
	}

	/**
	 * Settings the editor scripts need.
	 *
	 * @return void
	 */
	public function editor_settings() {
		wp_add_inline_script(
			self::EDITOR_SCRIPT,
			'window.academyBlocks = ' . wp_json_encode(
				[
					'courseTypes' => CourseData::post_types(),
					// Registration Form: profile fields beyond the account need Pro.
					'isPro'       => \Academy\Helper::is_active_academy_pro(),
					// Pro-dependent blocks the editor may offer on this site.
					'optional'    => array_values(
						array_filter(
							[ 'certificate-verification', 'social-login' ],
							function ( $block ) {
								return shortcode_exists( self::requires_shortcode( $block ) );
							}
						)
					),
					// Added to the editor canvas only when a block that prints
					// Academy's own markup is on the page, so they cannot restyle
					// anything else being edited.
					'styles'      => array_filter(
						[
							file_exists( ACADEMY_ASSETS_DIR_PATH . 'build/frontendCommon.css' ) ? add_query_arg( 'ver', filemtime( ACADEMY_ASSETS_DIR_PATH . 'build/frontendCommon.css' ), ACADEMY_ASSETS_URI . 'build/frontendCommon.css' ) : '',
							file_exists( ACADEMY_ASSETS_DIR_PATH . 'lib/css/academy-icon.css' ) ? add_query_arg( 'ver', filemtime( ACADEMY_ASSETS_DIR_PATH . 'lib/css/academy-icon.css' ), ACADEMY_ASSETS_URI . 'lib/css/academy-icon.css' ) : '',
							plugins_url( 'blocks/typography.css', __FILE__ ) . '?ver=' . filemtime( __DIR__ . '/blocks/typography.css' ),
						]
					),
					'learn'       => [
						'sidebar' => \Academy\LearnPage\Settings::get()['sidebar'],
						'width'   => \Academy\LearnPage\Settings::get()['width'],
						// Customize → Learn Page → Colours. The learn page block
						// prints these as CSS variables on the front; the editor
						// draws its own frame, so it has to print them too or the
						// canvas ignores every colour set there.
						'colors'  => \Academy\LearnPage\Settings::get()['colors'],
					],
					// The learn page blocks also show a real lesson.
					'learnStyles' => array_filter(
						[
							file_exists( ACADEMY_ASSETS_DIR_PATH . 'build/frontendPhpCurriculums.css' ) ? add_query_arg( 'ver', filemtime( ACADEMY_ASSETS_DIR_PATH . 'build/frontendPhpCurriculums.css' ), ACADEMY_ASSETS_URI . 'build/frontendPhpCurriculums.css' ) : '',
						]
					),
					'dashboard'   => [
						'sidebar'      => 'left',
						'sidebarWidth' => \Academy\FrontendDashboard\Settings::get()['sidebarWidth'],
					],
					// The dashboard blocks show the signed-in person's own dashboard.
					'dashboardStyles' => array_filter(
						[
							file_exists( ACADEMY_ASSETS_DIR_PATH . 'build/frontendDashboard.css' ) ? add_query_arg( 'ver', filemtime( ACADEMY_ASSETS_DIR_PATH . 'build/frontendDashboard.css' ), ACADEMY_ASSETS_URI . 'build/frontendDashboard.css' ) : '',
						]
					),
				]
			) . ';',
			'before'
		);

		// A 12px gap between a block toggle and its label (Gutenberg uses 8px).
		wp_add_inline_style( 'wp-components', '.block-editor-block-inspector .components-toggle-control .components-base-control__field > .components-h-stack{gap:12px}' );

		// Academy's settings panels (AcademyPanelBody): 16px under an open panel's
		// title, the same as between its controls (core leaves 5px).
		wp_add_inline_style( 'wp-components', '.components-panel__body.academy-panel-body.is-opened > .components-panel__body-title{margin-bottom:16px}' );

		// Their own bottom padding and an even 16px between controls. The
		// controls carry no bottom margin (__nextHasNoMarginBottom), so this
		// can't be left to the panel: other plugins' editor CSS zeroes
		// `.components-panel__body.is-opened` bottom padding, which put the
		// last control on top of the next panel's divider.
		wp_add_inline_style( 'wp-components', '.components-panel__body.academy-panel-body.is-opened{padding-bottom:16px}.components-panel__body.academy-panel-body.is-opened > :not(.components-panel__body-title):not(:last-child){margin-bottom:16px}.components-panel__body.academy-panel-body.is-opened > :last-child{margin-bottom:0}' );
	}

	/**
	 * Add the "Academy LMS" inserter category.
	 *
	 * @param array $categories Block categories.
	 * @return array
	 */
	public function register_category( $categories ) {
		return array_merge(
			[
				[
					'slug'  => 'academy',
					'title' => __( 'Academy LMS', 'academy' ),
					'icon'  => null,
				],
				// One course: its page sections and its card facts (price,
				// rating…), used on the course page and in course Query Loops.
				[
					'slug'  => 'academy-course',
					'title' => __( 'Academy LMS: Course', 'academy' ),
					'icon'  => null,
				],
			],
			$categories
		);
	}

	/**
	 * Expose course facts to the editor so block previews show real data.
	 * The field is filled only when requested with `_fields`, so other course
	 * REST requests do not pay for it.
	 *
	 * @return void
	 */
	public function register_rest_fields() {
		register_rest_field(
			array_values( array_filter( CourseData::post_types(), 'post_type_exists' ) ),
			'academy_card',
			[
				'get_callback' => function ( $course, $field_name, $request ) {
					$fields = $request instanceof \WP_REST_Request ? $request->get_param( '_fields' ) : '';
					$fields = is_array( $fields ) ? $fields : explode( ',', (string) $fields );
					if ( ! in_array( 'academy_card', array_map( 'trim', $fields ), true ) ) {
						return null;
					}

					return CourseData::get( (int) $course['id'] );
				},
				'schema'       => [
					'description' => __( 'Course facts used by the Academy LMS blocks. Request it with _fields=academy_card.', 'academy' ),
					'type'        => [ 'object', 'null' ],
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
				],
			]
		);
	}

	/**
	 * Which course templates the site uses.
	 *
	 * @return string blocks|legacy
	 */
	public static function template_style() {
		return 'legacy' === Options::get( Options::DESIGN_STATE, self::TEMPLATE_STYLE_KEY ) ? 'legacy' : 'blocks';
	}

	/**
	 * Sites that already have courses keep the original course page layout
	 * until an admin switches; new sites start with the block templates.
	 *
	 * @return void
	 */
	public function maybe_set_template_style() {
		if ( Options::has( Options::DESIGN_STATE, self::TEMPLATE_STYLE_KEY ) || ! post_type_exists( 'academy_courses' ) ) {
			return;
		}

		$counts = wp_count_posts( 'academy_courses' );
		$has    = isset( $counts->publish ) && (int) $counts->publish > 0;
		Options::set( Options::DESIGN_STATE, self::TEMPLATE_STYLE_KEY, $has ? 'legacy' : 'blocks' );
	}

	/**
	 * Whether the active theme can show the block-based course templates.
	 *
	 * @return bool
	 */
	private static function theme_can_use_block_templates() {
		return Helper::is_fse_theme() || (bool) apply_filters( 'academy/templates/use_block_templates', false );
	}

	/**
	 * Offer the block-based course templates to sites that kept the original layout.
	 *
	 * @return void
	 */
	public function template_style_notice() {
		if ( ! current_user_can( 'manage_options' ) || ! self::theme_can_use_block_templates() ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! ( in_array( $screen->id, [ 'dashboard', 'themes', 'plugins' ], true ) || false !== strpos( $screen->id, 'academy' ) ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
		$result = isset( $_GET['academy-course-templates'] ) ? sanitize_key( wp_unslash( $_GET['academy-course-templates'] ) ) : '';
		$action = function ( $style ) {
			return wp_nonce_url( admin_url( 'admin-post.php?action=academy_course_templates&style=' . $style ), 'academy_course_templates' );
		};

		if ( 'blocks' === $result ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%1$s %2$s <a href="%3$s">%4$s</a></p></div>',
				esc_html__( 'Course pages now use the block templates.', 'academy' ),
				Helper::is_fse_theme()
					? sprintf( '<a href="%1$s">%2$s</a> &middot;', esc_url( admin_url( 'site-editor.php?p=%2Ftemplate' ) ), esc_html__( 'Edit them in the Site Editor', 'academy' ) )
					: '',
				esc_url( $action( 'legacy' ) ),
				esc_html__( 'Switch back to the original layout', 'academy' )
			);
			return;
		}

		if ( 'legacy' !== self::template_style() || get_user_meta( get_current_user_id(), 'academy_course_templates_notice_dismissed', true ) ) {
			return;
		}

		printf(
			'<div class="notice notice-info"><p><strong>%1$s</strong></p><p>%2$s</p><p><a class="button button-primary" href="%3$s">%4$s</a> <a class="button" href="%5$s">%6$s</a></p></div>',
			esc_html__( 'Build your course pages with blocks', 'academy' ),
			esc_html__( 'Academy LMS has new course, course archive and category templates made of blocks, with course filters and a course grid you can restyle. Your course pages keep their current layout until you switch.', 'academy' ),
			esc_url( $action( 'blocks' ) ),
			esc_html__( 'Use the new templates', 'academy' ),
			esc_url( $action( 'dismiss' ) ),
			esc_html__( 'Keep the current layout', 'academy' )
		);
	}

	/**
	 * Switch course templates, or dismiss the offer.
	 *
	 * @return void
	 */
	/**
	 * A site moving to the block templates keeps a card that looks like the one
	 * it had: the preset nearest its classic card style, unless it has saved a
	 * design already.
	 *
	 * @return string|null The preset applied, if any.
	 */
	public static function adopt_card_style() {
		if ( false !== get_option( \Academy\Design\Settings::OPTION, false ) ) {
			return null;
		}
		$preset = \Academy\Design\Settings::preset_for_card_style( (string) Helper::get_settings( 'course_card_style', 'default' ) );
		$design = \Academy\Design\Presets::design( $preset );
		if ( ! $design ) {
			return null;
		}
		\Academy\Design\Settings::save( $design );

		return $preset;
	}

	public function handle_template_style() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to change course templates.', 'academy' ), 403 );
		}
		check_admin_referer( 'academy_course_templates' );

		$style    = isset( $_GET['style'] ) ? sanitize_key( wp_unslash( $_GET['style'] ) ) : '';
		$redirect = wp_get_referer() ? wp_get_referer() : admin_url();
		$redirect = remove_query_arg( 'academy-course-templates', $redirect );

		if ( 'dismiss' === $style ) {
			update_user_meta( get_current_user_id(), 'academy_course_templates_notice_dismissed', 1 );
		} elseif ( in_array( $style, [ 'blocks', 'legacy' ], true ) ) {
			Options::set( Options::DESIGN_STATE, self::TEMPLATE_STYLE_KEY, $style );
			if ( 'blocks' === $style ) {
				$redirect = add_query_arg( 'academy-course-templates', 'blocks', $redirect );
				self::adopt_card_style();
			} else {
				delete_user_meta( get_current_user_id(), 'academy_course_templates_notice_dismissed' );
			}
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * A width set in a block's settings as a CSS length (60%, 480px, 30rem; a
	 * bare number is pixels), or '' for none.
	 *
	 * @param mixed $value Attribute value.
	 * @return string
	 */
	public static function css_width( $value ) {
		if ( ! is_string( $value ) || ! preg_match( '/^(\d{1,4}(?:\.\d+)?)\s*(%|px|rem|em|vw)?$/', trim( $value ), $match ) ) {
			return '';
		}

		return $match[1] . ( ! empty( $match[2] ) ? $match[2] : 'px' );
	}

	/**
	 * Whether this render is the block editor's preview (the block renderer
	 * REST route, for someone who edits content).
	 *
	 * @return bool
	 */
	public static function is_editor_preview() {
		return defined( 'REST_REQUEST' ) && REST_REQUEST && current_user_can( 'edit_posts' );
	}

	/**
	 * Render a course page section from its existing shortcode, for the course
	 * in the block's context, inside the block's wrapper.
	 *
	 * @param string    $shortcode Shortcode tag.
	 * @param \WP_Block $block     Block instance.
	 * @param callable  $preview   Prints what students would see, for the editor
	 *                             preview when the section is empty.
	 * @param array     $wrapper   Extra wrapper attributes (class, style).
	 * @return string
	 */
	public static function render_course_section( $shortcode, $block, $preview = null, array $wrapper = [] ) {
		$course_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_the_ID();
		if ( ! $course_id || 'academy_courses' !== get_post_type( $course_id ) ) {
			return '';
		}

		self::use_course_assets();

		global $post;
		$previous  = $post;
		$switch_to = ! $previous || (int) $previous->ID !== $course_id;
		if ( $switch_to ) {
			$post = get_post( $course_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restored below.
			setup_postdata( $post );
		}

		$html = (string) do_shortcode( '[' . $shortcode . ']' );

		// Sections only students see (the review form, attachments) would be
		// empty for the person editing: show them what students get.
		if ( '' === trim( $html ) && is_callable( $preview ) && self::is_editor_preview() ) {
			ob_start();
			call_user_func( $preview, $course_id );
			$html = (string) ob_get_clean();
		}

		if ( $switch_to ) {
			$post = $previous; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
			if ( $previous ) {
				setup_postdata( $previous );
			}
		}

		if ( '' === trim( $html ) ) {
			return '';
		}

		$wrapper['style'] = self::academy_colors_style( $block->attributes ) . ( $wrapper['style'] ?? '' );

		return sprintf( '<div %1$s>%2$s</div>', get_block_wrapper_attributes( $wrapper ), $html );
	}

	/**
	 * Whether a value is a colour a block may print into a style attribute.
	 *
	 * @param string $value Colour.
	 * @return bool
	 */
	public static function is_color( $value ) {
		return is_string( $value ) && (bool) preg_match( '/^(#[0-9a-f]{3,8}|rgba?\([\d\s.,%]+\)|hsla?\([\d\s.,%deg]+\)|var\(--wp--preset--color--[a-z0-9-]+\))$/i', $value );
	}

	/**
	 * Academy colour variables for a block's "Academy colours" settings, so a
	 * section can be themed without touching the rest of the page. A block text
	 * colour also becomes Academy's text colour inside the block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function academy_colors_style( array $attributes ) {
		$map   = [
			'accentColor'     => '--academy-primary-color',
			'accentSoftColor' => '--academy-secondary-color',
			'surfaceColor'    => '--academy-surface-color',
			'lineColor'       => '--academy-border-color',
		];
		$style = '';
		foreach ( $map as $attribute => $variable ) {
			if ( ! empty( $attributes[ $attribute ] ) && self::is_color( $attributes[ $attribute ] ) ) {
				$style .= $variable . ':' . $attributes[ $attribute ] . ';';
			}
		}
		if ( ! empty( $attributes['textColor'] ) || ! empty( $attributes['style']['color']['text'] ) ) {
			$style .= '--academy-text-color:currentColor;--academy-text-color-black:currentColor;';
		}

		// A link colour picked in the block's Color panel.
		$link = isset( $attributes['style']['elements']['link']['color']['text'] ) ? (string) $attributes['style']['elements']['link']['color']['text'] : '';
		if ( 0 === strpos( $link, 'var:preset|color|' ) ) {
			$link = 'var(--wp--preset--color--' . sanitize_key( substr( $link, 17 ) ) . ')';
		}
		if ( $link && self::is_color( $link ) ) {
			$style .= '--academy-link-color:' . $link . ';';
		}

		// Font size, colour and alignment for named parts of a section.
		foreach ( [ 'title', 'price', 'list' ] as $group ) {
			if ( ! empty( $attributes[ $group . 'FontSize' ] ) ) {
				$style .= '--academy-' . $group . '-size:' . max( 8, min( 96, (int) $attributes[ $group . 'FontSize' ] ) ) . 'px;';
			}
			if ( ! empty( $attributes[ $group . 'Color' ] ) && self::is_color( $attributes[ $group . 'Color' ] ) ) {
				$style .= '--academy-' . $group . '-color:' . $attributes[ $group . 'Color' ] . ';';
			}
			if ( ! empty( $attributes[ $group . 'Align' ] ) && in_array( $attributes[ $group . 'Align' ], [ 'left', 'center', 'right' ], true ) ) {
				$style .= '--academy-' . $group . '-align:' . $attributes[ $group . 'Align' ] . ';';
			}
		}

		return $style;
	}

	/**
	 * Render an Academy shortcode as a block, inside the block's wrapper.
	 * Empty attributes are left out so the shortcode's own defaults apply.
	 *
	 * @param string    $tag   Shortcode tag.
	 * @param array     $atts  Shortcode attributes.
	 * @param \WP_Block $block Block instance.
	 * @return string
	 */
	public static function render_shortcode_block( $tag, array $atts, $block ) {
		self::use_course_assets();

		$pairs = '';
		foreach ( $atts as $name => $value ) {
			if ( '' === $value || null === $value ) {
				continue;
			}
			if ( is_bool( $value ) ) {
				$value = $value ? 'true' : 'false';
			}
			$pairs .= sprintf( ' %1$s="%2$s"', sanitize_key( $name ), str_replace( [ '"', '[', ']' ], [ '&quot;', '&#91;', '&#93;' ], (string) $value ) );
		}

		$html = (string) do_shortcode( '[' . $tag . $pairs . ']' );
		if ( '' === trim( $html ) ) {
			return '';
		}

		return sprintf( '<div %1$s>%2$s</div>', get_block_wrapper_attributes( [ 'style' => self::academy_colors_style( $block->attributes ) ] ), $html );
	}

	/**
	 * Load Academy's course page styles and scripts where a section block is
	 * used outside Academy's own pages.
	 *
	 * @return void
	 */
	public static function use_course_assets() {
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_style_is( 'academy-common-styles', 'enqueued' ) ) {
			return;
		}

		( new Assets() )->frontend_common_assets();
	}

	/**
	 * Print the rules that make a block's own font size, line height, link and
	 * section colours reach Academy's markup.
	 *
	 * @param string $block_content Rendered block.
	 * @param array  $block         Parsed block.
	 * @return string
	 */
	public function enqueue_typography_style( $block_content, $block ) {
		if ( '' !== $block_content && ! empty( $block['blockName'] ) && 0 === strpos( $block['blockName'], 'academy/' ) ) {
			if ( ! wp_style_is( 'academy-blocks-typography', 'registered' ) ) {
				wp_register_style( 'academy-blocks-typography', plugins_url( 'blocks/typography.css', __FILE__ ), [], ACADEMY_VERSION . '.' . filemtime( __DIR__ . '/blocks/typography.css' ) );
			}
			wp_enqueue_style( 'academy-blocks-typography' );
		}

		return $block_content;
	}

	/**
	 * Load the dashboard's styles and scripts when a dashboard block renders
	 * somewhere they were not loaded up front (a template, a pattern, a
	 * reusable block). They print in the footer.
	 */
	public static function use_dashboard_assets() {
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! is_user_logged_in() || wp_style_is( 'academy-frontend-dashboard-styles', 'enqueued' ) ) {
			return;
		}

		( new Assets() )->frontend_dashboard_assets();
	}

	/**
	 * Whether a Post Featured Image block is rendering right now.
	 *
	 * @var int
	 */
	private static $featured_image_depth = 0;

	/**
	 * Track Post Featured Image rendering, so the course placeholder is used
	 * only inside that block.
	 *
	 * @param string|null $pre_render   Short-circuit output.
	 * @param array       $parsed_block Parsed block.
	 * @return string|null
	 */
	public function start_featured_image( $pre_render, $parsed_block ) {
		if ( null === $pre_render && 'core/post-featured-image' === $parsed_block['blockName'] ) {
			++self::$featured_image_depth;
		}

		return $pre_render;
	}

	/**
	 * End of a Post Featured Image block.
	 *
	 * @param string $block_content Rendered block.
	 * @return string
	 */
	public function end_featured_image( $block_content ) {
		self::$featured_image_depth = max( 0, self::$featured_image_depth - 1 );

		return $block_content;
	}

	/**
	 * A course without a featured image shows Academy's course placeholder in
	 * the Post Featured Image block. The block builds its own markup around it,
	 * so width, aspect ratio, border radius and links still apply.
	 *
	 * @param string       $html              Thumbnail HTML.
	 * @param int|\WP_Post $post              Post.
	 * @param int|false    $post_thumbnail_id Attachment ID.
	 * @param string|int[] $size              Image size.
	 * @param string|array $attr              Image attributes.
	 * @return string
	 */
	public function course_image_placeholder( $html, $post, $post_thumbnail_id, $size, $attr ) {
		if ( '' !== $html || self::$featured_image_depth < 1 || ! CourseData::is_course( $post instanceof \WP_Post ? $post->ID : (int) $post ) ) {
			return $html;
		}

		$attr  = wp_parse_args( is_array( $attr ) ? $attr : [], [ 'style' => '' ] );
		$attrs = '';
		foreach ( $attr as $name => $value ) {
			if ( in_array( $name, [ 'src', 'srcset', 'sizes', 'class' ], true ) || '' === $value ) {
				continue;
			}
			$attrs .= sprintf( ' %1$s="%2$s"', esc_attr( $name ), esc_attr( $value ) );
		}

		return sprintf(
			'<img src="%1$s" class="wp-post-image academy-course-image-placeholder" alt="" loading="lazy" decoding="async"%2$s />',
			esc_url( ACADEMY_ASSETS_URI . 'images/thumbnail-placeholder.png' ),
			$attrs
		);
	}

	/**
	 * The "Course Excerpt" setting shows or hides the card excerpt (the Post
	 * Excerpt block with the academy-course-card__excerpt class).
	 *
	 * @param string $content Rendered block.
	 * @param array  $block   Parsed block.
	 * @return string
	 */
	public function card_excerpt_setting( $content, $block ) {
		$class_name = isset( $block['attrs']['className'] ) ? (string) $block['attrs']['className'] : '';
		if ( false === strpos( $class_name, 'academy-course-card__excerpt' ) ) {
			return $content;
		}
		// The Course Design screen decides once a design is saved.
		if ( false !== get_option( \Academy\Design\Settings::OPTION, false ) ) {
			return $content;
		}

		return Helper::get_settings( 'is_show_course_excerpt', false ) ? $content : '';
	}

	/**
	 * The "Course Archive Per Row" setting sets the columns of course grids
	 * that carry the academy-course-archive class (the archive templates).
	 *
	 * @param array          $parsed_block Parsed block.
	 * @param array          $source_block Source block.
	 * @param \WP_Block|null $parent_block Parent block.
	 * @return array
	 */
	public function archive_columns_setting( $parsed_block, $source_block, $parent_block = null ) {
		if ( 'core/post-template' !== $parsed_block['blockName'] || ! $parent_block instanceof \WP_Block || 'core/query' !== $parent_block->name ) {
			return $parsed_block;
		}
		$class_name = isset( $parent_block->parsed_block['attrs']['className'] ) ? (string) $parent_block->parsed_block['attrs']['className'] : '';
		if ( false === strpos( $class_name, 'academy-course-archive' ) ) {
			return $parsed_block;
		}

		// The Course Design screen owns the columns once a design is saved.
		if ( false !== get_option( \Academy\Design\Settings::OPTION, false ) ) {
			return $parsed_block;
		}

		$per_row = (array) Helper::get_settings( 'course_archive_courses_per_row', [] );
		$columns = isset( $per_row['desktop'] ) ? (int) $per_row['desktop'] : 0;
		if ( $columns < 1 ) {
			return $parsed_block;
		}

		$parsed_block['attrs']['layout'] = [
			'type'        => 'grid',
			'columnCount' => min( 6, $columns ),
		];

		return $parsed_block;
	}
}
