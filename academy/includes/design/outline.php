<?php
namespace Academy\Design;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * What a course card or a course page is made of once it is edited in the block
 * editor.
 *
 * The Design screen's switches build these pages until someone opens them in
 * the block editor; from then on the pattern is the source of truth. This reads
 * that pattern back, so every block in it — including ones added by hand —
 * still has a row on the Design screen to reorder and switch off.
 *
 * Switching a block off lifts it out of the pattern and keeps it, with the
 * place it came from, in the pattern's own post meta. Nothing is rewritten
 * inside a block, so the editor never sees content it did not save.
 */
class Outline {

	/**
	 * Post meta holding the blocks that are switched off.
	 */
	const META = '_academy_design_hidden';

	/**
	 * What the Design screen shows for a part that has a pattern.
	 *
	 * @param string $piece card|page|catalog|course.
	 * @return array[]
	 */
	public static function get( $piece ) {
		$post = Patterns::post( $piece );
		if ( ! $post ) {
			return [];
		}

		$hidden = self::hidden( $post->ID );
		$used   = [];
		$tree   = self::tree( self::blocks( $post->post_content ), '', $hidden, $used );

		// A switched-off block whose place is gone — the block around it was
		// removed in the block editor, say — still gets a row, at the end,
		// rather than disappearing without a word.
		foreach ( $hidden as $index => $entry ) {
			if ( isset( $used[ $index ] ) ) {
				continue;
			}
			$tree[] = [
				'key'      => 'hidden:' . $index,
				'name'     => (string) ( $entry['name'] ?? '' ),
				'label'    => (string) ( $entry['label'] ?? '' ),
				'visible'  => false,
				'children' => [],
			];
		}

		return $tree;
	}

	/**
	 * Save a new order, and which blocks are switched off.
	 *
	 * @param string $piece card|page|catalog|course.
	 * @param array  $items The rows from the Design screen.
	 * @return bool
	 */
	public static function apply( $piece, array $items ) {
		$post = Patterns::post( $piece );
		if ( ! $post ) {
			return false;
		}

		$root   = self::blocks( $post->post_content );
		$hidden = self::hidden( $post->ID );
		$next   = [];
		$blocks = self::rebuild( $items, $root, $hidden, $next, '' );

		wp_update_post(
			[
				'ID'           => $post->ID,
				'post_content' => serialize_blocks( $blocks ),
			]
		);
		update_post_meta( $post->ID, self::META, $next );

		return true;
	}

	/**
	 * A pattern's blocks, without the whitespace between them, so a block's
	 * position is the same whether it is being read or written.
	 *
	 * @param string $content Pattern content.
	 * @return array
	 */
	private static function blocks( $content ) {
		return self::clean( parse_blocks( (string) $content ) );
	}

	/**
	 * Drop the empty blocks parse_blocks() makes out of whitespace.
	 *
	 * @param array $blocks Parsed blocks.
	 * @return array
	 */
	private static function clean( array $blocks ) {
		$clean = [];
		foreach ( $blocks as $block ) {
			if ( empty( $block['blockName'] ) ) {
				continue;
			}
			$block['innerBlocks'] = self::clean( $block['innerBlocks'] );
			$clean[]              = $block;
		}

		return $clean;
	}

	/**
	 * The rows for a list of blocks, with the switched-off ones back in place.
	 *
	 * @param array  $blocks Parsed blocks.
	 * @param string $path   Path of the parent block.
	 * @param array  $hidden Switched-off blocks.
	 * @param array  $used   Which of those found their place, by reference.
	 * @return array[]
	 */
	private static function tree( array $blocks, $path, array $hidden, array &$used ) {
		$items = [];
		foreach ( $blocks as $index => $block ) {
			$child   = '' === $path ? (string) $index : $path . '.' . $index;
			$items[] = [
				'key'      => $child,
				'name'     => $block['blockName'],
				'label'    => self::label( $block ),
				'visible'  => true,
				'children' => self::tree( $block['innerBlocks'], $child, $hidden, $used ),
			];
		}

		foreach ( $hidden as $index => $entry ) {
			if ( (string) ( $entry['parent'] ?? '' ) !== $path ) {
				continue;
			}
			$at             = max( 0, min( (int) ( $entry['index'] ?? 0 ), count( $items ) ) );
			$used[ $index ] = true;
			array_splice(
				$items,
				$at,
				0,
				[
					[
						'key'      => 'hidden:' . $index,
						'name'     => (string) ( $entry['name'] ?? '' ),
						'label'    => (string) ( $entry['label'] ?? '' ),
						'visible'  => false,
						'children' => [],
					],
				]
			);
		}//end foreach

		return $items;
	}

	/**
	 * Build the pattern again from the rows the Design screen sent back.
	 *
	 * @param array  $items       Rows.
	 * @param array  $root        The pattern's blocks.
	 * @param array  $hidden      Switched-off blocks as they were.
	 * @param array  $next        Switched-off blocks as they will be, by reference.
	 * @param string $parent_path Path of the parent block in the new pattern.
	 * @return array
	 */
	private static function rebuild( array $items, array $root, array $hidden, array &$next, $parent_path ) {
		$out = [];

		foreach ( $items as $item ) {
			$key      = (string) ( $item['key'] ?? '' );
			$visible  = ! empty( $item['visible'] );
			$children = isset( $item['children'] ) && is_array( $item['children'] ) ? $item['children'] : [];

			if ( 0 === strpos( $key, 'hidden:' ) ) {
				$entry = $hidden[ (int) substr( $key, 7 ) ] ?? null;
				if ( ! $entry ) {
					continue;
				}
				if ( ! $visible ) {
					$entry['parent'] = $parent_path;
					$entry['index']  = count( $out );
					$next[]          = $entry;
					continue;
				}
				foreach ( self::blocks( (string) $entry['markup'] ) as $block ) {
					$out[] = $block;
				}
				continue;
			}

			$block = self::at( $root, $key );
			if ( ! $block ) {
				continue;
			}

			if ( ! $visible ) {
				$next[] = [
					'parent' => $parent_path,
					'index'  => count( $out ),
					'markup' => serialize_block( $block ),
					'label'  => self::label( $block ),
					'name'   => $block['blockName'],
				];
				continue;
			}

			$path = '' === $parent_path ? (string) count( $out ) : $parent_path . '.' . count( $out );
			if ( $children ) {
				$inner                 = self::rebuild( $children, $root, $hidden, $next, $path );
				$block['innerContent'] = self::inner_content( $block, count( $inner ) );
				$block['innerBlocks']  = $inner;
			}

			$out[] = $block;
		}//end foreach

		return $out;
	}

	/**
	 * A block's own markup with the right number of slots for its blocks.
	 *
	 * @param array $block Parsed block.
	 * @param int   $count How many blocks sit inside it now.
	 * @return array
	 */
	private static function inner_content( array $block, $count ) {
		$before = [];
		$after  = [];
		$seen   = false;
		foreach ( $block['innerContent'] as $chunk ) {
			if ( null === $chunk ) {
				$seen = true;
				continue;
			}
			if ( $seen ) {
				$after[] = $chunk;
			} else {
				$before[] = $chunk;
			}
		}

		return array_merge( $before, array_fill( 0, max( 0, $count ), null ), $after );
	}

	/**
	 * The block at a path like "0.2.1".
	 *
	 * @param array  $blocks Parsed blocks.
	 * @param string $path   Path.
	 * @return array|null
	 */
	private static function at( array $blocks, $path ) {
		$block = null;
		foreach ( explode( '.', $path ) as $index ) {
			if ( ! isset( $blocks[ (int) $index ] ) ) {
				return null;
			}
			$block  = $blocks[ (int) $index ];
			$blocks = $block['innerBlocks'];
		}

		return $block;
	}

	/**
	 * The blocks that are switched off for a pattern.
	 *
	 * @param int $post_id Pattern ID.
	 * @return array
	 */
	private static function hidden( $post_id ) {
		$hidden = get_post_meta( $post_id, self::META, true );

		return is_array( $hidden ) ? array_values( $hidden ) : [];
	}

	/**
	 * What to call a block on the Design screen.
	 *
	 * @param array $block Parsed block.
	 * @return string
	 */
	private static function label( array $block ) {
		// A name given to the block in the editor says it best.
		if ( ! empty( $block['attrs']['metadata']['name'] ) ) {
			return (string) $block['attrs']['metadata']['name'];
		}

		$type  = \WP_Block_Type_Registry::get_instance()->get_registered( $block['blockName'] );
		$label = $type && $type->title ? $type->title : $block['blockName'];

		if ( 'core/post-terms' === $block['blockName'] ) {
			$terms = [
				'academy_courses_category' => __( 'Categories', 'academy' ),
				'academy_courses_tag'      => __( 'Tags', 'academy' ),
			];
			$term  = $block['attrs']['term'] ?? '';
			if ( isset( $terms[ $term ] ) ) {
				return $terms[ $term ];
			}
		}

		if ( in_array( $block['blockName'], [ 'core/group', 'core/columns' ], true ) && ! empty( $block['innerBlocks'] ) ) {
			return sprintf(
				/* translators: 1: block name, e.g. Group. 2: number of blocks inside it. */
				_n( '%1$s of %2$d block', '%1$s of %2$d blocks', count( $block['innerBlocks'] ), 'academy' ),
				$label,
				count( $block['innerBlocks'] )
			);
		}

		$text = trim( wp_strip_all_tags( (string) $block['innerHTML'] ) );
		if ( '' !== $text && in_array( $block['blockName'], [ 'core/heading', 'core/paragraph', 'core/button', 'core/list-item' ], true ) ) {
			$label .= ': ' . wp_html_excerpt( $text, 32, '…' );
		}

		return $label;
	}
}
