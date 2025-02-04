<?php
namespace  Academy\Frontend\Template;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Block {
	public static function init() {
		$self = new self();
		add_filter( 'pre_get_block_file_template', [ $self, 'get_block_file_template' ], 10, 3 );
		add_filter( 'get_block_templates', [ $self, 'add_block_templates' ], 10, 3 );
	}

	public function get_block_file_template( $template, $id, $template_type ) {

		$template_name_parts = explode( '//', $id );

		if ( count( $template_name_parts ) < 2 ) {
			return $template;
		}

		list( $template_id, $template_slug ) = $template_name_parts;

		// If we are not dealing with a BetterDocs template let's return early and let it continue through the process.
		if ( ACADEMY_PLUGIN_SLUG !== $template_id ) {
			return $template;
		}

		// If we don't have a template let Gutenberg do its thing.
		if ( ! $this->block_template_is_available( $template_slug, $template_type ) ) {
			return $template;
		}

		$directory = ACADEMY_BLOCK_TEMPLATES_DIR_PATH;

		$template_file_path = $directory . '/' . $template_slug . '.html';

		$template_object = $this->create_new_block_template_object( $template_file_path, $template_type, $template_slug );

		$template_built = $this->build_template_result_from_file( $template_object, $template_type );

		if ( null !== $template_built ) {
			return $template_built;
		}

		// Hand back over to Gutenberg if we can't find a template.
		return $template;
	}

	public function block_template_is_available( $template_name, $template_type = 'wp_template' ) {
		if ( ! $template_name ) {
			return false;
		}
		$directory = ACADEMY_BLOCK_TEMPLATES_DIR_PATH . $template_name . '.html';

		return is_readable( $directory ) || $this->get_block_templates( [ $template_name ], $template_type );
	}

	public function get_block_templates( $slugs = [], $template_type = 'wp_template' ) {
		$templates_from_db         = $this->get_block_templates_from_db( $slugs, $template_type );
		$templates_from_academylms = $this->get_block_templates_from_academylms( $slugs, $templates_from_db, $template_type );
		$templates                 = array_merge( $templates_from_db, $templates_from_academylms );
		return $templates;
	}

	public function get_block_templates_from_db( $slugs = [], $template_type = 'wp_template' ) {
		$check_query_args = [
			'post_type'      => $template_type,
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'tax_query'      => [  // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				[
					'taxonomy' => 'wp_theme',
					'field'    => 'name',
					'terms'    => [ ACADEMY_PLUGIN_SLUG, get_stylesheet() ]
				]
			]
		];

		if ( is_array( $slugs ) && count( $slugs ) > 0 ) {
			$check_query_args['post_name__in'] = $slugs;
		}

		$check_query                = new \WP_Query( $check_query_args );
		$saved_academylms_templates = $check_query->posts;

		return array_map(
			function ( $saved_academylms_template ) {
				return $this->build_template_result_from_post( $saved_academylms_template );
			},
			$saved_academylms_templates
		);
	}

	public static function build_template_result_from_post( $post ) {
		$terms = get_the_terms( $post, 'wp_theme' );

		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		if ( ! $terms ) {
			return new \WP_Error( 'template_missing_theme', __( 'No theme is defined for this template.', 'academy' ) );
		}

		$theme          = $terms[0]->name;
		$has_theme_file = true;

		$template                 = new \WP_Block_Template();
		$template->wp_id          = $post->ID;
		$template->id             = $theme . '//' . $post->post_name;
		$template->theme          = $theme;
		$template->content        = $post->post_content;
		$template->slug           = $post->post_name;
		$template->source         = 'custom';
		$template->type           = $post->post_type;
		$template->description    = $post->post_excerpt;
		$template->title          = $post->post_title;
		$template->status         = $post->post_status;
		$template->has_theme_file = $has_theme_file;
		$template->is_custom      = false;
		$template->post_types     = array(); // Don't appear in any Edit Post template selector dropdown.

		if ( 'wp_template_part' === $post->post_type ) {
			$type_terms = get_the_terms( $post, 'wp_template_part_area' );
			if ( ! is_wp_error( $type_terms ) && false !== $type_terms ) {
				$template->area = $type_terms[0]->name;
			}
		}

		// We are checking 'woocommerce' to maintain classic templates which are saved to the DB,
		// prior to updating to use the correct slug.
		// More information found here: https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/5423.
		if ( ACADEMY_PLUGIN_SLUG === $theme ) {
			$template->origin = 'plugin';
		}

		/*
		* Run the block hooks algorithm introduced in WP 6.4 on the template content.
		*/
		if ( function_exists( 'inject_ignored_hooked_blocks_metadata_attributes' ) ) {
			$hooked_blocks = get_hooked_blocks();
			if ( ! empty( $hooked_blocks ) || has_filter( 'hooked_block_types' ) ) {
				$before_block_visitor = make_before_block_visitor( $hooked_blocks, $template );
				$after_block_visitor  = make_after_block_visitor( $hooked_blocks, $template );
				$blocks               = parse_blocks( $template->content );
				$template->content    = traverse_and_serialize_blocks( $blocks, $before_block_visitor, $after_block_visitor );
			}
		}

		return $template;
	}

	public function get_block_templates_from_academylms( $slugs, $already_found_templates, $template_type = 'wp_template' ) {

		$template_files = $this->get_templates_fils_from_academylms( $template_type );
		$templates      = [];

		foreach ( $template_files as $template_file ) {
			$template_slug = $this->generate_template_slug_from_path( $template_file );

			// This template does not have a slug we're looking for. Skip it.
			if ( is_array( $slugs ) && count( $slugs ) > 0 && ! in_array( $template_slug, $slugs, true ) ) {
				continue;
			}

			// If the the template is already in the list (i.e. it came from the
			// database) then we should not overwrite it with the one from the filesystem.
			if (
				count(
					array_filter(
						$already_found_templates,
						function ( $template ) use ( $template_slug ) {
							$template_obj = (object) $template; //phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.Found
							return $template_obj->slug === $template_slug;
						}
					)
				) > 0 ) {
				continue;
			}

			// At this point the template only exists in the Blocks filesystem and has not been saved in the DB,
			// or superseded by the theme.
			$templates[] = $this->create_new_block_template_object( $template_file, $template_type, $template_slug );
		}//end foreach
		return $templates;
	}

	public function get_templates_fils_from_academylms( $template_type ) {
		$directory      = ACADEMY_BLOCK_TEMPLATES_DIR_PATH;
		$template_files = $this->get_template_paths( $directory );
		return $template_files;
	}

	public function generate_template_slug_from_path( $path ) {
		$template_extension = '.html';

		return basename( $path, $template_extension );
	}

	public function get_template_paths( $base_directory ) {
		$path_list = [];
		if ( file_exists( $base_directory ) ) {
			$nested_files      = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $base_directory ) );
			$nested_html_files = new \RegexIterator( $nested_files, '/^.+\.html$/i', \RecursiveRegexIterator::GET_MATCH );
			foreach ( $nested_html_files as $path => $file ) {
				$path_list[] = $path;
			}
		}
		return $path_list;
	}

	public function create_new_block_template_object( $template_file, $template_type, $template_slug, $template_is_from_theme = false ) {
		$theme_name = wp_get_theme()->get( 'TextDomain' );

		$new_template_item = [
			'slug'        => $template_slug,
			'id'          => $template_is_from_theme ? $theme_name . '//' . $template_slug : ACADEMY_PLUGIN_SLUG . '//' . $template_slug,
			'path'        => $template_file,
			'type'        => $template_type,
			'theme'       => $template_is_from_theme ? $theme_name : ACADEMY_PLUGIN_SLUG,
			'source'      => $template_is_from_theme ? 'theme' : 'plugin',
			'title'       => $this->convert_slug_to_title( $template_slug ),
			'description' => '',
			'post_types'  => [] // Don't appear in any Edit Post template selector dropdown.
		];

		return (object) $new_template_item;
	}
	public function convert_slug_to_title( $template_slug ) {
		switch ( $template_slug ) {
			case 'single-academy_courses':
				return __( 'Single Courses', 'academy' );
			case 'archive-academy_courses':
				return __( 'Archive Courses', 'academy' );
			default:
				// Replace all hyphens and underscores with spaces.
				return ucwords( preg_replace( '/[\-_]/', ' ', $template_slug ) );
		}
	}

	public function build_template_result_from_file( $template_file, $template_type ) {
		$template_file = (object) $template_file;

		// If the theme has an archive-products.html template but does not have product taxonomy templates
		// then we will load in the archive-product.html template from the theme to use for product taxonomies on the frontend.
		$template_is_from_theme = 'theme' === $template_file->source;
		$theme_name             = wp_get_theme()->get( 'TextDomain' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$template_content  = file_get_contents( $template_file->path );
		$template          = new \WP_Block_Template();
		$template->id      = $template_is_from_theme ? $theme_name . '//' . $template_file->slug : ACADEMY_PLUGIN_SLUG . '//' . $template_file->slug;
		$template->theme   = $template_is_from_theme ? $theme_name : ACADEMY_PLUGIN_SLUG;
		$template->content = self::inject_theme_attribute_in_content( $template_content );
		// Remove the term description block from the archive-product template
		// as the Product Catalog/Shop page doesn't have a description.
		if ( 'archive-academy_courses' === $template_file->slug ) {
			$template->content = str_replace( '<!-- wp:term-description {"align":"wide"} /-->', '', $template->content );
		}
		// Plugin was agreed as a valid source value despite existing inline docs at the time of creating: https://github.com/WordPress/gutenberg/issues/36597#issuecomment-976232909.
		$template->source         = $template_file->source ? $template_file->source : 'plugin';
		$template->slug           = $template_file->slug;
		$template->type           = $template_type;
		$template->title          = ! empty( $template_file->title ) ? $template_file->title : $this->get_block_template_title( $template_file->slug );
		$template->description    = ! empty( $template_file->description ) ? $template_file->description : $this->get_block_template_description( $template_file->slug );
		$template->status         = 'publish';
		$template->has_theme_file = true;
		$template->origin         = $template_file->source;
		$template->is_custom      = false; // Templates loaded from the filesystem aren't custom, ones that have been edited and loaded from the DB are.
		$template->post_types     = array(); // Don't appear in any Edit Post template selector dropdown.
		$template->area           = 'uncategorized';

		/*
		* Run the block hooks algorithm introduced in WP 6.4 on the template content.
		*/
		if ( function_exists( 'inject_ignored_hooked_blocks_metadata_attributes' ) ) {
			$before_block_visitor = '_inject_theme_attribute_in_template_part_block';
			$after_block_visitor  = null;
			$hooked_blocks        = get_hooked_blocks();
			if ( ! empty( $hooked_blocks ) || has_filter( 'hooked_block_types' ) ) {
				$before_block_visitor = make_before_block_visitor( $hooked_blocks, $template );
				$after_block_visitor  = make_after_block_visitor( $hooked_blocks, $template );
			}
			$blocks            = parse_blocks( $template->content );
			$template->content = traverse_and_serialize_blocks( $blocks, $before_block_visitor, $after_block_visitor );
		}

		return $template;
	}

	public function inject_theme_attribute_in_content( $template_content ) {
		$has_updated_content = false;
		$new_content         = '';
		$template_blocks     = parse_blocks( $template_content );

		$blocks = $this->flatten_blocks( $template_blocks );
		foreach ( $blocks as &$block ) {
			if (
				'core/template-part' === $block['blockName'] &&
				! isset( $block['attrs']['theme'] )
			) {
				$block['attrs']['theme'] = wp_get_theme()->get_stylesheet();
				$has_updated_content     = true;
			}
		}

		if ( $has_updated_content ) {
			foreach ( $template_blocks as &$block ) {
				$new_content .= serialize_block( $block );
			}

			return $new_content;
		}

		return $template_content;
	}

	public function flatten_blocks( &$blocks ) {
		$all_blocks = [];
		$queue      = [];
		foreach ( $blocks as &$block ) {
			$queue[] = &$block;
		}
		$queue_count = count( $queue );

		while ( $queue_count > 0 ) {
			$block = &$queue[0];
			array_shift( $queue );
			$all_blocks[] = &$block;

			if ( ! empty( $block['innerBlocks'] ) ) {
				foreach ( $block['innerBlocks'] as &$inner_block ) {
					$queue[] = &$inner_block;
				}
			}

			$queue_count = count( $queue );
		}

		return $all_blocks;
	}

	public function get_block_template_title( $template_slug ) {
		$plugin_template_types = $this->get_plugin_block_template_types();
		if ( isset( $plugin_template_types[ $template_slug ] ) ) {
			return $plugin_template_types[ $template_slug ]['title'];
		} else {
			// Human friendly title converted from the slug.
			return ucwords( preg_replace( '/[\-_]/', ' ', $template_slug ) );
		}
	}

	public function get_plugin_block_template_types() {
		$plugin_template_types = [
			'single-course'           => [
				'title'       => _x( 'Single Course', 'Template name', 'academy' ),
				'description' => __( 'Template used to display the single courses.', 'academy' )
			],
			'archive-course'           => [
				'title'       => _x( 'Archive Course', 'Template name', 'academy' ),
				'description' => __( 'Template used to display the archive courses.', 'academy' )
			]
		];

		return $plugin_template_types;
	}

	public function get_block_template_description( $template_slug ) {
		$plugin_template_types = $this->get_plugin_block_template_types();
		if ( isset( $plugin_template_types[ $template_slug ] ) ) {
			return $plugin_template_types[ $template_slug ]['description'];
		}
		return '';
	}

	public function add_block_templates( $query_result, $query, $template_type ) {

		$post_type = isset( $query['post_type'] ) ? $query['post_type'] : '';
		$slugs     = isset( $query['slug__in'] ) ? $query['slug__in'] : [];

		$template_files = $this->get_block_templates( $slugs, $template_type );

		// @todo: Add apply_filters to _gutenberg_get_template_files() in Gutenberg to prevent duplication of logic.
		foreach ( $template_files as $template_file ) {

			if ( $post_type &&
				isset( $template_file->post_types ) &&
				! in_array( $post_type, $template_file->post_types, true )
			) {
				continue;
			}

			// It would be custom if the template was modified in the editor, so if it's not custom we can load it from
			// the filesystem.
			if ( 'custom' !== $template_file->source ) {
				$template = $this->build_template_result_from_file( $template_file, $template_type );
			} else {
				$template_file->title       = $this->get_block_template_title( $template_file->slug );
				$template_file->description = $this->get_block_template_description( $template_file->slug );
				$query_result[]             = $template_file;
				continue;
			}

			$is_not_custom   = false === array_search(
				wp_get_theme()->get_stylesheet() . '//' . $template_file->slug,
				array_column( $query_result, 'id' ),
				true
			);
			$fits_slug_query =
			! isset( $query['slug__in'] ) || in_array( $template_file->slug, $query['slug__in'], true );
			$fits_area_query =
			! isset( $query['area'] ) || $template_file->area === $query['area'];
			$should_include  = $is_not_custom && $fits_slug_query && $fits_area_query;
			if ( $should_include ) {
				$query_result[] = $template;
			}
		}//end foreach

		return $query_result;
	}
}
