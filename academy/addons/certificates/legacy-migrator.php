<?php
/**
 * Converts a legacy v1 (Ablocks/Gutenberg) certificate's `post_content` into
 * the v2 tree-builder's JSON shape, so opening an old certificate in the new
 * editor shows its original design instead of a blank canvas — before this,
 * `BuilderApi::get_item()` returned `tree: null` for any certificate saved
 * before the tree builder existed (no `_academy_certificate_tree` meta),
 * which the editor renders as an empty page with no indication anything was
 * lost.
 *
 * Read-only: never writes anything back. `BuilderApi::get_item()` hands the
 * converted tree to the editor for THIS load only — it's only actually
 * persisted if/when the user hits Save through the normal API path.
 *
 * Not a general Gutenberg parser — covers the specific Ablocks block types
 * this plugin's certificate templates actually emit (academy-certificate,
 * academy-certificate-text, academy-container, core/image, core/spacer).
 * Anything else (or content that doesn't parse as expected) is skipped
 * rather than guessed at, so a partial/odd legacy certificate still migrates
 * whatever it can instead of failing outright.
 */

namespace AcademyCertificates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LegacyMigrator {

	/**
	 * Convert legacy certificate content to a v2 tree.
	 *
	 * @param string $post_content The certificate's Gutenberg post_content.
	 * @return array|null The v2 tree, or null if `$post_content` isn't a recognisable legacy certificate.
	 */
	public static function convert( $post_content ) {
		$blocks     = parse_blocks( (string) $post_content );
		$root_block = self::find_block( $blocks, 'ablocks/academy-certificate' );
		if ( ! $root_block ) {
			return null;
		}

		$root_attrs = $root_block['attrs'] ?? array();
		$children   = array();
		self::walk( $root_block['innerBlocks'] ?? array(), $children );

		return array(
			'version' => 2,
			'root'    => array(
				'id'         => 'root',
				'type'       => 'certificate',
				'attributes' => array(
					'pageSize'    => 'A4',
					'orientation' => 'landscape',
					'fontFamily'  => 'Poppins',
					'bgColor'     => '#FFFFFF',
					'bgImage'     => $root_attrs['backgroundImage'] ?? '',
					'bgSize'      => 'cover',
					'contentBg'   => 'transparent',
					'padding'     => array( 48, 56, 48, 56 ),
				),
				'children'   => $children,
			),
		);
	}

	protected static function find_block( array $blocks, $name ) {
		foreach ( $blocks as $b ) {
			$block_name = $b['blockName'] ?? '';
			if ( $block_name === $name ) {
				return $b;
			}
		}
		return null;
	}

	protected static function walk( array $blocks, array &$out ) {
		$count = count( $blocks );
		for ( $i = 0; $i < $count; $i++ ) {
			$b = $blocks[ $i ];

			// A run of 2+ sibling `academy-container` blocks is how Ablocks
			// built a side-by-side row (each container floated, e.g. a Date /
			// Signature pair) — flattening every container individually (the
			// `default` case below) loses that and stacks them vertically
			// instead. Group the whole run into one `columns` node so the
			// layout survives too, not just the content.
			if ( 'ablocks/academy-container' === ( $b['blockName'] ?? '' ) ) {
				$run = array( $b );
				while ( $i + 1 < $count && 'ablocks/academy-container' === ( $blocks[ $i + 1 ]['blockName'] ?? '' ) ) {
					$run[] = $blocks[ ++$i ];
				}
				if ( count( $run ) > 1 ) {
					$out[] = self::columns_node( $run );
				} else {
					self::walk( $b['innerBlocks'] ?? array(), $out );
				}
				continue;
			}

			switch ( $b['blockName'] ?? '' ) {

				case 'core/spacer':
					$out[] = array(
						'id'         => self::uid(),
						'type'       => 'spacer',
						'attributes' => array( 'height' => max( 1, (int) ( $b['attrs']['height'] ?? 24 ) ) ),
					);
					break;

				case 'core/image':
					if ( preg_match( '/<img[^>]+src="([^"]+)"/', $b['innerHTML'] ?? '', $m ) ) {
						$out[] = array(
							'id'         => self::uid(),
							'type'       => 'image',
							'attributes' => array(
								'src'          => $m[1],
								'alt'          => '',
								'href'         => '',
								'openInNewTab' => true,
								// The renderer applies `width` as a hard size,
								// not a max-width-with-natural-fallback — a
								// generic default here (the manifest's 300)
								// stretches a small source image (e.g. a
								// 162px logo) well past its native
								// resolution, rendering blurry and
								// oversized. Use the file's own natural width
								// when it's resolvable, only falling back to
								// the manifest default for anything that
								// isn't a local file (a remote URL, a
								// filename that no longer exists, ...).
								'width'        => self::natural_width( $m[1] ) ?? 300,
								'alignment'    => $b['attrs']['align'] ?? 'center',
								'padding'      => array( 0, 0, 0, 0 ),
								'margin'       => array( 0, 0, 0, 0 ),
								'border'       => self::default_border(),
							),
						);
					}//end if
					break;

				case 'ablocks/academy-certificate-text':
					$node = self::text_node( $b );
					if ( $node ) {
						$out[] = $node;
					}
					break;

				default:
					// Unrecognised wrapper — descend in case it holds
					// something recognisable, otherwise it's silently
					// dropped rather than guessed at.
					if ( ! empty( $b['innerBlocks'] ) ) {
						self::walk( $b['innerBlocks'], $out );
					}
			}//end switch
		}//end for
	}

	/**
	 * A run of sibling `academy-container` blocks → one `columns` node, each container's own content flattened into its own column.
	 *
	 * @param array $containers Sibling academy-container blocks.
	 */
	protected static function columns_node( array $containers ) {
		$columns = array();
		foreach ( $containers as $container ) {
			$children = array();
			self::walk( $container['innerBlocks'] ?? array(), $children );
			$columns[] = array(
				'id'         => self::uid(),
				'type'       => 'column',
				'attributes' => array( 'padding' => array( 0, 0, 0, 0 ) ),
				'children'   => $children,
			);
		}

		return array(
			'id'         => self::uid(),
			'type'       => 'columns',
			'attributes' => array(
				'columns'         => count( $columns ),
				'gap'             => 16,
				'valign'          => 'top',
				'backgroundColor' => 'transparent',
				'padding'         => array( 0, 0, 0, 0 ),
				'margin'          => array( 0, 0, 0, 0 ),
			),
			'children'   => $columns,
		);
	}

	protected static function text_node( array $b ) {
		$attrs   = $b['attrs'] ?? array();
		$is_text = 'span' === ( $attrs['headingTag'] ?? '' );

		if ( ! preg_match( '/<(span|h[1-6])[^>]*>(.*?)<\/\1>/s', $b['innerHTML'] ?? '', $m ) ) {
			return null;
		}
		$text = trim( html_entity_decode( wp_strip_all_tags( $m[2] ), ENT_QUOTES ) );
		if ( '' === $text ) {
			return null;
		}

		$typo       = $attrs['typography'] ?? array();
		$align      = $attrs['alignment']['value'] ?? 'left';
		$font_size  = (int) ( $typo['fontSize'] ?? ( $is_text ? 14 : 24 ) );
		$default_lh = $is_text ? 1.6 : 1.3;

		$typography = array(
			'fontFamily'     => $typo['fontFamily'] ?? 'inherit',
			'fontSize'       => $font_size,
			'fontWeight'     => (string) ( $typo['weight'] ?? '400' ),
			'fontStyle'      => 'normal',
			// The new schema's `lineHeight` is a unitless MULTIPLIER of
			// fontSize (CSS `line-height: 1.3`, per the block manifest's own
			// default) — but Ablocks stored it as a raw pixel-ish number
			// (e.g. `"lineHeight":50` on a 52px heading). Copying that value
			// straight across, as this once did, produces `52px * 50 =
			// 2600px` of line-height on ONE heading — which was observed
			// stretching the whole certificate PAGE to ~6000px tall in the
			// canvas (the page container grows to fit its content) and
			// breaking the background image's scaling along with it. A
			// legacy value only ever makes sense here as a multiplier when
			// it's already in the normal ~0.8–3 range; anything bigger is a
			// pixel value in disguise, so it's converted (px ÷ fontSize)
			// instead of trusted as-is.
			'lineHeight'     => self::normalize_line_height( $typo['lineHeight'] ?? null, $font_size, $default_lh ),
			'letterSpacing'  => (float) ( $typo['letterSpacing'] ?? 0 ),
			'textTransform'  => 'none',
			'textDecoration' => 'none',
			'color'          => $typo['color'] ?? ( $is_text ? '#333333' : '#111111' ),
		);

		$common = array(
			'alignment'  => $align,
			'typography' => $typography,
			'padding'    => array( 0, 0, 0, 0 ),
			'margin'     => array( 0, 0, 0, 0 ),
			'border'     => self::default_border(),
		);

		if ( $is_text ) {
			return array(
				'id'         => self::uid(),
				'type'       => 'text',
				'attributes' => array( 'text' => $text ) + $common,
			);
		}

		return array(
			'id'         => self::uid(),
			'type'       => 'heading',
			'attributes' => array(
				'text' => $text,
				'tag'  => 'h2',
			) + $common,
		);
	}

	/**
	 * A legacy `typography.lineHeight` value is only usable as-is when it's
	 * already a plausible unitless multiplier (~0.8–3, matching real-world
	 * line-height values); anything bigger is a pixel value that needs
	 * converting to a multiplier, and anything absent falls back to the new
	 * schema's own per-block-type default.
	 *
	 * @param mixed $raw       Legacy line-height value.
	 * @param int   $font_size Font size in px.
	 * @param float $default   Default multiplier for the block type.
	 */
	protected static function normalize_line_height( $raw, $font_size, $default ) {
		if ( null === $raw || '' === $raw ) {
			return $default;
		}
		$value = (float) $raw;
		if ( $value <= 0 ) {
			return $default;
		}
		if ( $value <= 3 ) {
			return $value;
		}
		return $font_size > 0 ? round( $value / $font_size, 2 ) : $default;
	}

	/**
	 * The image's real pixel width if the URL resolves to a local file, capped at a sane display size; null otherwise.
	 *
	 * @param string $url Image URL from the legacy block.
	 * @return int|null
	 */
	protected static function natural_width( $url ) {
		// Stored URLs and site_url()/content_url() can disagree on http vs
		// https depending on when/how the content was saved (the same class
		// of mismatch documented on `image_source()` in renderer.php) — drop
		// the scheme from both sides of the prefix match so that doesn't
		// break path resolution.
		$scheme_agnostic = static function ( $u ) {
			return preg_replace( '#^https?://#i', '', $u );
		};
		$url_no_scheme = $scheme_agnostic( $url );

		$upload = wp_upload_dir();
		$roots  = array(
			content_url()       => WP_CONTENT_DIR,
			$upload['baseurl']  => $upload['basedir'],
		);
		$path = null;
		foreach ( $roots as $url_root => $dir_root ) {
			$root_no_scheme = $scheme_agnostic( $url_root );
			if ( 0 === strpos( $url_no_scheme, $root_no_scheme ) ) {
				$path = $dir_root . substr( $url_no_scheme, strlen( $root_no_scheme ) );
				break;
			}
		}
		if ( ! $path ) {
			return null;
		}
		// Built from a URL, so `../` in it must not reach outside the root it
		// was matched against.
		$real      = realpath( $path );
		$real_root = realpath( $dir_root );
		if ( false === $real || false === $real_root || 0 !== strpos( $real, trailingslashit( $real_root ) ) || ! is_file( $real ) ) {
			return null;
		}
		$size = wp_getimagesize( $real );
		if ( ! $size ) {
			return null;
		}
		// Cap at 400px so a genuinely large source photo doesn't dominate the
		// certificate at its full resolution — legacy certificates only ever
		// used this for small logo/seal images.
		return min( (int) $size[0], 400 );
	}

	protected static function default_border() {
		return array(
			'width'  => 0,
			'style'  => 'solid',
			'color'  => '#000000',
			'radius' => 0,
		);
	}

	protected static function uid() {
		return 'n_legacy_' . substr( md5( uniqid( '', true ) ), 0, 8 );
	}

	/**
	 * Convert legacy `post_content` straight to a `version: 3` canvas tree,
	 * for the actual PDF render path (`Helper::render_certificate()`) —
	 * no editor round-trip needed.
	 *
	 * `convert()` above already turns the legacy blocks into a clean
	 * `version: 2` flow tree; the only reason that isn't rendered directly
	 * is that nothing server-side can render a *flow* tree without the
	 * pre-rendered HTML the JS builder produces on Save
	 * (`render_tree_certificate()` needs `_academy_certificate_html`, which
	 * only exists once an author has opened and saved the design). Rather
	 * than build a second flow-tree HTML renderer, this stacks the flow
	 * tree's blocks into absolute x/y/w/h canvas nodes — the same
	 * left-to-right, top-to-bottom accounting `convertFlowTree.js` does
	 * client-side (identical ~0.55em text-height estimate, since heights
	 * aren't measurable before layout there either) — and hands the result
	 * to `CanvasRenderer`, the one renderer every certificate (old or new)
	 * can go through without touching ABlocks at all.
	 *
	 * Scoped to exactly the block types `convert()` ever actually emits
	 * (heading/text/image/spacer/columns) — the full flow model has more
	 * block types, but the legacy Ablocks parser above never produces them,
	 * so there's nothing to stack for them here.
	 *
	 * @param string $post_content The certificate's legacy Gutenberg post_content.
	 * @return array|null A `version: 3` tree, or null if `$post_content` isn't a recognisable legacy certificate.
	 */
	public static function convert_to_canvas( $post_content ) {
		$flow = self::convert( $post_content );
		if ( null === $flow ) {
			return null;
		}

		$root_attrs = $flow['root']['attributes'];
		// `convert()` always sets pageSize:'A4' — the only size the legacy
		// builder ever offered — so the long/short pair matches
		// pageSetup.js's PAGE_SIZES.A4 (CSS px at 96dpi, same figures
		// Renderer::PAGE_SIZES hands to mPDF in mm).
		$landscape   = 'portrait' !== ( $root_attrs['orientation'] ?? 'landscape' );
		$page_width  = $landscape ? 1123 : 794;
		$padding     = $root_attrs['padding'];
		$content_x   = (float) ( $padding[3] ?? 0 );
		$content_w   = max( 1, $page_width - (float) ( $padding[1] ?? 0 ) - $content_x );

		$y        = (float) ( $padding[0] ?? 0 );
		$children = array();
		foreach ( $flow['root']['children'] as $node ) {
			$stacked  = self::stack_node( $node, $content_x, $content_w, $y );
			$children = array_merge( $children, $stacked['nodes'] );
			$y       += $stacked['height'];
		}

		return array(
			'version' => 3,
			'root'    => array(
				'id'         => 'root',
				'type'       => 'certificate-canvas',
				'attributes' => array(
					'pageSize'    => $root_attrs['pageSize'],
					'orientation' => $root_attrs['orientation'],
					'fontFamily'  => $root_attrs['fontFamily'],
					'bgColor'     => $root_attrs['bgColor'],
					'bgImage'     => $root_attrs['bgImage'],
					'bgSize'      => $root_attrs['bgSize'],
				),
				'children'   => $children,
			),
		);
	}

	/**
	 * Stack one flow node (and, for `columns`, its whole subtree) into
	 * canvas node(s) positioned at `$x,$y` within `$width` — mirrors
	 * `convertNode()`/`flatten()` in `convertFlowTree.js`, scoped to the
	 * block types `convert()` produces.
	 *
	 * @param array $node  Flow node (`{id,type,attributes[,children]}`).
	 * @param float $x
	 * @param float $width
	 * @param float $y
	 * @return array{nodes: array[], height: float}
	 */
	protected static function stack_node( array $node, $x, $width, $y ) {
		$type = $node['type'] ?? '';
		$a    = $node['attributes'] ?? array();

		switch ( $type ) {
			case 'heading':
			case 'text':
				$typo = $a['typography'] ?? array();
				$size = (float) ( $typo['fontSize'] ?? ( 'heading' === $type ? 24 : 14 ) );
				$lh   = (float) ( $typo['lineHeight'] ?? ( 'heading' === $type ? 1.3 : 1.6 ) );
				$h    = self::estimate_text_height( (string) ( $a['text'] ?? '' ), $size, $lh, $width );
				$font = ( ! empty( $typo['fontFamily'] ) && 'inherit' !== $typo['fontFamily'] ) ? $typo['fontFamily'] : '';
				return array(
					'nodes'  => array(
						array(
							'id'         => self::uid(),
							'type'       => $type,
							'attributes' => array(
								'rotation'      => 0,
								'zIndex'        => 1,
								'opacity'       => 1,
								'text'          => (string) ( $a['text'] ?? '' ),
								'fontFamily'    => $font,
								'fontSize'      => $size,
								'fontWeight'    => (string) ( $typo['fontWeight'] ?? '400' ),
								'lineHeight'    => $lh,
								'letterSpacing' => (float) ( $typo['letterSpacing'] ?? 0 ),
								'color'         => $typo['color'] ?? ( 'heading' === $type ? '#111111' : '#333333' ),
								'textAlign'     => $a['alignment'] ?? 'left',
								'verticalAlign' => 'top',
								'x'             => $x,
								'y'             => $y,
								'w'             => $width,
								'h'             => $h,
							),
							'children'   => array(),
						),
					),
					'height' => $h,
				);

			case 'image':
				$w     = min( $width, (float) ( $a['width'] ?? $width ) );
				$h     = round( $w / 1.5 );
				$align = $a['alignment'] ?? 'center';
				$ix    = 'center' === $align ? $x + ( $width - $w ) / 2 : ( 'right' === $align ? $x + $width - $w : $x );
				return array(
					'nodes'  => array(
						array(
							'id'         => self::uid(),
							'type'       => 'image',
							'attributes' => array(
								'rotation' => 0,
								'zIndex'   => 1,
								'opacity'  => 1,
								'src'      => $a['src'] ?? '',
								'fit'      => 'contain',
								'x'        => $ix,
								'y'        => $y,
								'w'        => $w,
								'h'        => $h,
							),
							'children'   => array(),
						),
					),
					'height' => $h,
				);

			case 'columns':
				$cols   = $node['children'] ?? array();
				$count  = max( 1, count( $cols ) );
				$col_w  = floor( $width / $count );
				$max_h  = 0;
				$nodes  = array();
				foreach ( array_values( $cols ) as $i => $col ) {
					$cy = $y;
					foreach ( $col['children'] ?? array() as $inner ) {
						$stacked = self::stack_node( $inner, $x + $i * $col_w, $col_w, $cy );
						$nodes   = array_merge( $nodes, $stacked['nodes'] );
						$cy     += $stacked['height'];
					}
					$max_h = max( $max_h, $cy - $y );
				}
				return array(
					'nodes' => $nodes,
					'height' => $max_h
				);

			case 'spacer':
				return array(
					'nodes' => array(),
					'height' => (float) ( $a['height'] ?? 24 )
				);

			default:
				// Not a type `convert()` above ever emits (it only produces
				// heading/text/image/spacer/columns) — nothing to stack.
				return array(
					'nodes' => array(),
					'height' => 0
				);
		}//end switch
	}

	/**
	 * Rough rendered height of a plain-text (already tag-stripped) run at a
	 * given width — the same ~0.55em average glyph-advance estimate
	 * `convertFlowTree.js`'s `estimateTextHeight()` uses, since neither side
	 * has layout to measure against yet (this runs before the canvas tree
	 * exists at all).
	 *
	 * @param string $text
	 * @param float  $font_size
	 * @param float  $line_height
	 * @param float  $width
	 * @return float
	 */
	protected static function estimate_text_height( $text, $font_size, $line_height, $width ) {
		$size     = $font_size > 0 ? $font_size : 14;
		$lh       = $line_height > 0 ? $line_height : 1.4;
		$per_line = max( 1, (int) floor( $width / ( $size * 0.55 ) ) );

		$total = 0;
		foreach ( explode( "\n", $text ) as $line ) {
			$len    = function_exists( 'mb_strlen' ) ? mb_strlen( $line ) : strlen( $line );
			$total += max( 1, (int) ceil( ( $len ? $len : 1 ) / $per_line ) );
		}
		return ceil( max( 1, $total ) * $size * $lh );
	}
}
