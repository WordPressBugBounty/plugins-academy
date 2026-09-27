<?php
namespace Academy\Design;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns the course design into the blocks the course templates are made of, and
 * into the CSS the course pages use.
 */
class Generator {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		$self = new self();
		add_filter( 'academy/blocks/template_content', [ $self, 'apply_to_template' ], 10, 2 );
		add_action( 'wp_head', [ $self, 'print_tokens' ], 20 );
		add_action( 'enqueue_block_assets', [ $self, 'print_editor_tokens' ] );
		add_action( 'init', [ $self, 'register_card_style' ] );
	}

	/**
	 * Build the course templates from the design.
	 *
	 * @param string $content Template content.
	 * @param string $slug    Template slug.
	 * @return string
	 */
	public function apply_to_template( $content, $slug ) {
		// A whole template someone edited in the block editor replaces what the
		// design builds. Only themes without a Site Editor get there.
		if ( ! Patterns::$seeding && ! \Academy\Helper::is_fse_theme() ) {
			$pattern = Patterns::template_post( $slug );
			if ( $pattern ) {
				return Patterns::block_markup( $pattern );
			}
		}

		$design = Settings::get();

		if ( in_array( $slug, [ 'archive-academy_courses', 'taxonomy-academy_courses_category' ], true ) ) {
			return $this->apply_to_catalog( $content, $design );
		}
		if ( 'single-academy_courses' === $slug ) {
			return $this->apply_to_course_page( $content, $design );
		}

		return $content;
	}

	/**
	 * Replace the card in a course grid, and set its columns.
	 *
	 * @param string $content Template content.
	 * @param array  $design  Design.
	 * @return string
	 */
	private function apply_to_catalog( $content, array $design ) {
		$blocks  = parse_blocks( $content );
		$columns = (int) $design['catalog']['columns']['desktop'];
		$card    = $this->card_blocks( $design );

		$this->walk(
			$blocks,
			function ( &$block ) use ( $card, $columns ) {
				if ( 'core/post-template' !== $block['blockName'] ) {
					return;
				}
				$block['attrs']['layout'] = [
					'type'        => 'grid',
					'columnCount' => $columns,
				];
				$this->replace_inner( $block, $card );
			}
		);

		return $this->serialize( $blocks );
	}

	/**
	 * Rebuild a course page from the design: section order, what is switched
	 * off, and which side the enroll box is on.
	 *
	 * @param string $content Template content.
	 * @param array  $design  Design.
	 * @return string
	 */
	private function apply_to_course_page( $content, array $design ) {
		$blocks   = parse_blocks( $content );
		$sections = $this->section_blocks( $design );

		$this->walk(
			$blocks,
			function ( &$block ) use ( $sections, $design ) {
				if ( 'core/columns' !== $block['blockName'] || count( $block['innerBlocks'] ) < 2 ) {
					return;
				}

				$main    = $block['innerBlocks'][0];
				$sidebar = $block['innerBlocks'][1];
				$this->replace_inner( $main, $sections );

				if ( 'none' === $design['single']['sidebar'] ) {
					$main['attrs']['width'] = '100%';
					$this->replace_inner( $block, [ $main ] );
					return;
				}

				$columns = 'left' === $design['single']['sidebar'] ? [ $sidebar, $main ] : [ $main, $sidebar ];
				$this->replace_inner( $block, $columns );
			}
		);

		return $this->serialize( $blocks );
	}

	/**
	 * The blocks of a course card, in the design's order.
	 *
	 * @param array $design Design.
	 * @return array Parsed blocks.
	 */
	public function card_blocks( array $design ) {
		$pattern = Patterns::post( 'card' );
		if ( $pattern ) {
			return Patterns::block( $pattern );
		}

		return parse_blocks( $this->card_markup( $design ) );
	}

	/**
	 * The markup of a course card, in the design's order.
	 *
	 * @param array $design Design.
	 * @return string
	 */
	public function card_markup( array $design ) {
		$radius = (int) $design['style']['radius'];
		$markup = [
			// These sizes are still baked in here (the value at the moment this
			// pattern is (re)generated) — Gutenberg's style engine mangles a raw
			// var(--x) reference used as a border/typography JSON attribute
			// value into invalid content (confirmed: it corrupts "--" into the
			// literal text "u002d", which then fails the block's own save()
			// round-trip and shows "Block contains unexpected or invalid
			// content" for every nested block). Actually following Shape
			// changes after the fact is handled instead by the plain CSS
			// override in tokens() below, which never touches block attributes.
			'image'       => sprintf(
				'<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"%1$s","style":{"border":{"radius":{"topLeft":"%2$dpx","topRight":"%2$dpx","bottomLeft":"0px","bottomRight":"0px"}}}} /-->',
				esc_attr( $design['catalog']['imageRatio'] ),
				max( 0, $radius - 1 )
			),
			'badge'       => '<!-- wp:academy/course-badge /-->',
			'categories'  => '<!-- wp:post-terms {"term":"academy_courses_category","className":"is-style-academy-with-images","style":{"typography":{"fontSize":"0.8125rem"}}} /-->',
			// No font size here on purpose: the Shape title size is a CSS
			// default in tokens() instead, so moving that slider reaches cards
			// already on a page and a size set on this block still wins.
			'title'       => '<!-- wp:post-title {"isLink":true,"level":3,"style":{"typography":{"lineHeight":"1.35"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} /-->',
			'excerpt'     => '<!-- wp:post-excerpt {"className":"academy-course-card__excerpt","excerptLength":18,"style":{"typography":{"fontSize":"0.875rem"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} /-->',
			'instructors' => '<!-- wp:academy/course-instructors {"style":{"typography":{"fontSize":"0.875rem"}}} /-->',
			'meta'        => '<!-- wp:academy/course-meta {"style":{"typography":{"fontSize":"0.8125rem"}}} /-->',
			'rating'      => '<!-- wp:academy/course-rating {"hideWhenEmpty":true,"style":{"typography":{"fontSize":"0.875rem"}}} /-->',
			'price'       => '<!-- wp:academy/course-price {"style":{"typography":{"fontSize":"1rem"}}} /-->',
			'button'      => '<!-- wp:academy/course-enroll-button /-->',
			'wishlist'    => '<!-- wp:academy/course-wishlist /-->',
		];

		$visible = [];
		foreach ( $design['catalog']['elements'] as $element ) {
			if ( ! empty( $element['visible'] ) && isset( $markup[ $element['key'] ] ) ) {
				$visible[] = $element['key'];
			}
		}

		$image = '';
		if ( $visible && 'image' === $visible[0] ) {
			$image = $markup['image'];
			array_shift( $visible );
		}

		// Price, rating and the button share one row at the foot of the card
		// when they are the last parts; anywhere else they follow the order.
		$row    = [ 'rating', 'price', 'button', 'wishlist' ];
		$footer = '';
		while ( $visible && in_array( end( $visible ), $row, true ) ) {
			$footer = $markup[ array_pop( $visible ) ] . $footer;
		}

		$body = '';
		foreach ( $visible as $key ) {
			if ( 'image' !== $key ) {
				$body .= $markup[ $key ];
			}
		}

		if ( '' !== $footer ) {
			$footer = '<!-- wp:group {"style":{"spacing":{"margin":{"top":"auto"},"padding":{"top":"10px"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->'
				. '<div class="wp-block-group">' . $footer . '</div>'
				. '<!-- /wp:group -->';
		}

		$card = sprintf(
			'<!-- wp:group {"className":"academy-course-card","style":{"border":{"radius":"%1$dpx","width":"1px","style":"solid","color":"var(--academy-border-color)"},"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"},"blockGap":"0"},"dimensions":{"minHeight":"100%%"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->'
			. '<div class="wp-block-group academy-course-card has-border-color" style="border-color:var(--academy-border-color);border-style:solid;border-width:1px;border-radius:%1$dpx;min-height:100%%;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">'
			. '%2$s'
			. '<!-- wp:group {"style":{"spacing":{"padding":{"top":"16px","right":"18px","bottom":"18px","left":"18px"},"blockGap":"10px"},"layout":{"selfStretch":"fill","flexSize":null}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->'
			. '<div class="wp-block-group" style="padding-top:16px;padding-right:18px;padding-bottom:18px;padding-left:18px">%3$s%4$s</div>'
			. '<!-- /wp:group -->'
			. '</div>'
			. '<!-- /wp:group -->',
			max( 0, $radius ),
			$image,
			$body,
			$footer
		);

		return $card;
	}

	/**
	 * The blocks of a course page's main column, in the design's order.
	 *
	 * @param array $design Design.
	 * @return array Parsed blocks.
	 */
	public function section_blocks( array $design ) {
		$pattern = Patterns::post( 'page' );
		if ( $pattern ) {
			return Patterns::block( $pattern );
		}

		return parse_blocks( $this->section_markup( $design ) );
	}

	/**
	 * The markup of a course page's main column, in the design's order.
	 *
	 * @param array $design Design.
	 * @return string
	 */
	public function section_markup( array $design ) {
		$markup = [
			'media'             => '<!-- wp:academy/course-media /-->',
			'categories'        => '<!-- wp:post-terms {"term":"academy_courses_category","className":"is-style-academy-with-images academy-course-page__categories"} /-->',
			'title'             => '<!-- wp:post-title {"level":1} /-->',
			'meta'              => '<!-- wp:group {"className":"academy-course-page__meta","style":{"spacing":{"blockGap":"12px 28px"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->'
				. '<div class="wp-block-group academy-course-page__meta">'
				. '<!-- wp:academy/course-instructors {"avatarSize":32} /-->'
				. '<!-- wp:academy/course-rating {"hideWhenEmpty":true} /-->'
				. '<!-- wp:academy/course-meta {"showEnrolled":true} /-->'
				. '</div><!-- /wp:group -->',
			'description'       => '<!-- wp:academy/course-description /-->',
			'benefits'          => '<!-- wp:academy/course-additional-info /-->',
			'curriculum'        => '<!-- wp:academy/course-curriculum /-->',
			'attachments'       => '<!-- wp:academy/course-attachments /-->',
			'instructorProfile' => '<!-- wp:academy/course-instructor-profiles /-->',
			'ratingSummary'     => '<!-- wp:academy/course-rating-summary /-->',
			'reviewForm'        => '<!-- wp:academy/course-review-form /-->',
			'reviews'           => '<!-- wp:academy/course-reviews /-->',
		];

		$content = '';
		foreach ( $design['single']['sections'] as $section ) {
			if ( ! empty( $section['visible'] ) && isset( $markup[ $section['key'] ] ) ) {
				$content .= $markup[ $section['key'] ];
			}
		}

		return $content;
	}

	/**
	 * Colours and sizes from the design, as CSS.
	 *
	 * @param array $design Design.
	 * @return string
	 */
	public function tokens( array $design ) {
		$style = $design['style'];
		$css   = '';

		// Colours come from Academy's palette (Customize → Colours & Style), which
		// prints for every page; the design only adds shape.
		$css .= '--academy-card-radius:' . (int) $style['radius'] . 'px;';
		$css .= '--academy-button-radius:' . (int) $style['buttonRadius'] . 'px;';

		$columns = $design['catalog']['columns'];
		$grid    = sprintf(
			'@media(max-width:1024px){.academy-course-archive .wp-block-post-template{grid-template-columns:repeat(%1$d,minmax(0,1fr))!important}}'
			. '@media(max-width:600px){.academy-course-archive .wp-block-post-template{grid-template-columns:repeat(%2$d,minmax(0,1fr))!important}}',
			(int) $columns['tablet'],
			(int) $columns['mobile']
		);

		// The card pattern bakes its radius in as plain px at the moment it is
		// (re)generated (see card_markup()) rather than as a var(--x) block
		// attribute, because Gutenberg's style engine mangles that into invalid
		// block content. A later Shape change reaches already-generated cards
		// through this override instead.
		//
		// The title size is not baked in, so it needs no !important. It is a
		// plain two-class rule: that beats the theme's own heading / Post Title
		// styles (element or :root :where() rules), so the Shape slider sets the
		// card title size, while a size set on that Post Title block in the
		// editor still wins — a preset class carries !important and a custom
		// value is an inline style. (Wrapped in :where() it had no specificity
		// and the theme's h3 size won, so the slider did nothing.)
		$shape = sprintf(
			'.academy-course-card{border-radius:%1$dpx!important}'
			. '.academy-course-card .wp-block-post-featured-image img{border-radius:%2$dpx %2$dpx 0 0!important}'
			. '.academy-course-card .wp-block-post-title{font-size:%3$dpx}',
			max( 0, (int) $style['radius'] ),
			max( 0, (int) $style['radius'] - 1 ),
			max( 0, (int) $style['titleSize'] )
		);

		return ':root{' . $css . '}' . $grid . $shape . $this->classic_shape( $style );
	}

	/**
	 * Card corners, button corners and title size for the classic course cards
	 * (`.academy-course`, drawn by PHP templates). Block cards carry these in
	 * their own markup, so without this the Shape settings do nothing on a site
	 * that still uses the classic templates.
	 *
	 * Only once the design has been saved (or is being previewed): the design's
	 * defaults are not the classic card's own look, and a site that never
	 * touched Customize should not change on update.
	 *
	 * @param array $style Design style.
	 * @return string
	 */
	private function classic_shape( array $style ) {
		if ( '__none__' === get_option( Settings::OPTION, '__none__' ) ) {
			return '';
		}

		// `:root body` outweighs the card's own nested rules without !important.
		return sprintf(
			':root body .academy-course{border-radius:%1$dpx;overflow:hidden}'
			. ':root body .academy-course .academy-course__title{font-size:%2$dpx}'
			. ':root body .academy-course .academy-btn{border-radius:%3$dpx}',
			(int) $style['radius'],
			(int) $style['titleSize'],
			(int) $style['buttonRadius']
		);
	}

	/**
	 * Print the design's CSS on the front end.
	 *
	 * @return void
	 */
	public function print_tokens() {
		printf( "<style id='academy-course-design'>%s</style>\n", $this->tokens( Settings::get() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- values validated in Settings::sanitize().
	}

	/**
	 * The same CSS in the editor, so previews match the site.
	 *
	 * @return void
	 */
	public function print_editor_tokens() {
		if ( ! is_admin() ) {
			return;
		}
		wp_register_style( 'academy-course-design', false, [], ACADEMY_VERSION );
		wp_enqueue_style( 'academy-course-design' );
		wp_add_inline_style( 'academy-course-design', $this->tokens( Settings::get() ) );
	}

	/**
	 * Academy's look for course cards and the top of a course page, loaded
	 * wherever those blocks render, in the editor as well as on the site.
	 *
	 * @return void
	 */
	public function register_card_style() {
		$style = [
			'handle' => 'academy-course-card',
			'src'    => plugins_url( 'course-card.css', __FILE__ ),
			'path'   => __DIR__ . '/course-card.css',
			'ver'    => (string) filemtime( __DIR__ . '/course-card.css' ),
		];
		// Course grids, and the categories heading a course page.
		wp_enqueue_block_style( 'core/post-template', $style );
		wp_enqueue_block_style( 'core/post-terms', $style );
	}

	/**
	 * Walk a block tree, letting the callback change blocks in place.
	 *
	 * @param array    $blocks   Parsed blocks.
	 * @param callable $callback Callback receiving each block by reference.
	 * @return void
	 */
	private function walk( array &$blocks, callable $callback ) {
		foreach ( $blocks as &$block ) {
			$callback( $block );
			if ( ! empty( $block['innerBlocks'] ) ) {
				$this->walk( $block['innerBlocks'], $callback );
			}
		}
		unset( $block );
	}

	/**
	 * Put a new set of inner blocks in a block, keeping the markup around them
	 * in step: serialize_block() walks innerContent and pulls one inner block
	 * for each null placeholder.
	 *
	 * @param array $block Parsed block.
	 * @param array $inner New inner blocks.
	 * @return void
	 */
	private function replace_inner( array &$block, array $inner ) {
		$before = '';
		$after  = '';
		$seen   = false;
		foreach ( $block['innerContent'] as $chunk ) {
			if ( null === $chunk ) {
				$seen = true;
				continue;
			}
			if ( $seen ) {
				$after .= $chunk;
			} else {
				$before .= $chunk;
			}
		}

		$block['innerBlocks']  = array_values( $inner );
		$block['innerContent'] = array_merge(
			[ $before ],
			array_fill( 0, count( $inner ), null ),
			[ $after ]
		);
	}

	/**
	 * Serialize a block tree.
	 *
	 * @param array $blocks Parsed blocks.
	 * @return string
	 */
	private function serialize( array $blocks ) {
		$content = '';
		foreach ( $blocks as $block ) {
			$content .= serialize_block( $block );
		}

		return $content;
	}
}
