<?php
/**
 * Certificate CANVAS renderer — absolute-position model (`version: 3` trees).
 *
 * Unlike the flow-based `Renderer`, which only wraps a client-rendered HTML
 * string (the mail-builder's table output) into a page shell, this class is
 * authoritative: it walks the tree's children itself and emits the block
 * markup directly, so nothing here depends on trusting client-supplied HTML.
 * That's the fix for the "builder blocks inject garbage/empty markup" class
 * of bug — this renderer only ever emits what IT writes.
 *
 * It's a PHP mirror of the JS renderer
 * (dev_emb/library/certificate-canvas/renderer.js in the easy-mail-builder
 * plugin) for the same reason `Renderer`/`style-builder.js` already mirror
 * each other in this codebase: the in-editor preview must match the real
 * mPDF output. Change one side, change the other.
 *
 * Every block is one `<div class="acdcert-<id>">` styled by ONE flat,
 * non-nested CSS rule — no descendant selectors, no `!important`, no tables.
 * Extends `Renderer` to reuse its page shell (`page_css`/`page_dims`), local
 * image path resolution (`image_source`), and QR PNG generation
 * (`generate_qr_png_file`) — none of that is renderer-model-specific.
 *
 * @package Academy\Certificates
 */

namespace AcademyCertificates\Render;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CanvasRenderer extends Renderer {

	/**
	 * The page's default font family, used by any block that leaves its own
	 * `fontFamily` empty (meaning "inherit the page font").
	 *
	 * This must be a REAL family name, never the CSS keyword `inherit`: the
	 * JS renderer resolves the same fallback to the page font, so emitting
	 * `inherit` here made the PDF disagree with both the live canvas and the
	 * preview — text landed on mPDF's built-in default (a serif) instead of
	 * the certificate's own font.
	 *
	 * @var string
	 */
	protected $ctx_font = 'Poppins';

	/**
	 * Render a `version: 3` tree straight from its data — no pre-rendered
	 * HTML string involved anywhere in this path.
	 *
	 * @param array $root Tree root (attributes = page settings, children = blocks).
	 * @return array { css, html, size, orientation }
	 */
	public function render_canvas( array $root ) {
		$dims     = self::page_dims( $root );
		$children = isset( $root['children'] ) && is_array( $root['children'] ) ? $root['children'] : array();

		$root_attrs = isset( $root['attributes'] ) && is_array( $root['attributes'] ) ? $root['attributes'] : array();
		if ( ! empty( $root_attrs['fontFamily'] ) ) {
			$this->ctx_font = (string) $root_attrs['fontFamily'];
		}

		$html = '';
		$css  = '';
		foreach ( $children as $node ) {
			if ( ! is_array( $node ) || empty( $node['type'] ) ) {
				continue; // Malformed node: skip rather than emit garbage markup.
			}
			$out   = $this->render_node( $node );
			$html .= $out['html'];
			$css  .= $out['css'];
		}

		return array(
			'css'         => $this->page_css( isset( $root['attributes'] ) && is_array( $root['attributes'] ) ? $root['attributes'] : array() ) . $css,
			'html'        => $html,
			'size'        => $dims['size'],
			'orientation' => $dims['orientation'],
		);
	}

	/**
	 * Dispatch one node to its type renderer. Unknown types are skipped.
	 *
	 * @param array $node Tree node.
	 */
	protected function render_node( array $node ) {
		$a    = isset( $node['attributes'] ) && is_array( $node['attributes'] ) ? $node['attributes'] : array();
		$id   = isset( $node['id'] ) ? (string) $node['id'] : '';
		$type = $node['type'];

		switch ( $type ) {
			case 'text':
			case 'heading':
			case 'date':
				return $this->render_text_like( $id, $a );
			case 'image':
				return $this->render_image_like( $id, $a );
			case 'shape':
				return $this->render_shape( $id, $a );
			case 'divider':
				return $this->render_divider( $id, $a );
			case 'qr':
				return $this->render_qr( $id, $a );
			case 'list':
				return $this->render_list( $id, $a );
			case 'social':
				return $this->render_social( $id, $a );
			case 'address':
				return $this->render_lines( $id, $a, $a['lines'] ?? '', 12, '#64748B' );
			case 'signature':
				return $this->render_signature( $id, $a );
			default:
				return array(
					'html' => '',
					'css' => ''
				);
		}//end switch
	}

	/**
	 * Layout rules every block gets — position/size/layer.
	 *
	 * No `opacity` here on purpose: mPDF has no general CSS opacity support
	 * outside a few specific object types (images, backgrounds, borders) — a
	 * plain `opacity` rule on block content is silently dropped, and
	 * `color: rgba()` fares no better (verified empirically against real
	 * mPDF output: neither has any visible effect on block text). Exposing
	 * the control in the editor when the PDF can never honour it would
	 * violate this builder's own capability contract (only offer what mPDF
	 * can actually render), so the attribute is intentionally ignored here —
	 * the editor UI is dropping the opacity control to match.
	 *
	 * Blocks are never rotated: the builder has no rotate control, and a
	 * `rotation` left in older saved certificates is ignored so they render
	 * straight, the same as the canvas.
	 *
	 * @param array $a Block attributes.
	 */
	protected function layout_rules( array $a ) {
		$rules = array(
			'position' => 'absolute',
			'left'     => self::px( $a['x'] ?? 0 ),
			'top'      => self::px( $a['y'] ?? 0 ),
			'width'    => self::px( $a['w'] ?? 0 ),
			'height'   => self::px( $a['h'] ?? 0 ),
		);
		if ( isset( $a['zIndex'] ) ) {
			$rules['z-index'] = (string) intval( $a['zIndex'] );
		}
		return $rules;
	}

	protected function border_rules( array $a ) {
		$rules = array();
		$w     = floatval( $a['borderWidth'] ?? 0 );
		if ( $w > 0 ) {
			$rules['border'] = self::px( $w ) . ' ' . self::css_keyword( $a['borderStyle'] ?? '', array( 'solid', 'dashed', 'dotted', 'double', 'none' ), 'solid' ) . ' ' . self::css_color( $a['borderColor'] ?? '', '#000000' );
		}
		$r = floatval( $a['borderRadius'] ?? 0 );
		if ( $r > 0 ) {
			$rules['border-radius'] = self::px( $r );
		}
		return $rules;
	}

	protected function shadow_rules( array $a ) {
		$x    = floatval( $a['shadowX'] ?? 0 );
		$y    = floatval( $a['shadowY'] ?? 0 );
		$blur = floatval( $a['shadowBlur'] ?? 0 );
		if ( ! $x && ! $y && ! $blur ) {
			return array();
		}
		return array(
			'box-shadow' => self::px( $x ) . ' ' . self::px( $y ) . ' ' . self::px( $blur ) . ' ' . self::css_color( $a['shadowColor'] ?? '', 'rgba(0,0,0,.25)' ),
		);
	}

	/**
	 * Background-image rules for image/signature blocks, resolving local paths like page backgrounds do.
	 *
	 * @param string $src Image URL.
	 * @param string $fit cover|contain|stretch.
	 */
	protected function background_image_rules( $src, $fit ) {
		if ( empty( $src ) ) {
			return array();
		}
		$resolved = $this->image_source( (string) $src );
		return array(
			'background-image'    => "url('" . str_replace( "'", "\\'", $resolved ) . "')",
			'background-size'     => self::bg_resize_keyword( $fit ),
			'background-position' => 'center center',
			'background-repeat'   => 'no-repeat',
		);
	}

	/**
	 * Cover/contain/stretch → CSS background-size (this renderer never sits inside a <td>, so plain CSS keywords work — no need for mPDF's numeric background-image-resize codes `page_css()` uses for the @page rule).
	 *
	 * @param string $fit cover|contain|stretch.
	 */
	protected static function bg_resize_keyword( $fit ) {
		return 'contain' === $fit ? 'contain' : ( 'stretch' === $fit ? '100% 100%' : 'cover' );
	}

	/**
	 * A CSS font-family value rewritten to the key mPDF actually registers.
	 *
	 * The mPDF library resolves a family by lowercasing it (`Mpdf::SetFont`) and looking
	 * it up in `fontdata`, whose keys are space-free — `dmsans`,
	 * `librebaskerville`, `greatvibes`, `alexbrush`, `abhayalibre`. It does
	 * NOT strip spaces itself, so every multi-word family the builder offers
	 * ("DM Sans", "Libre Baskerville", …) failed to match and silently fell
	 * back to mPDF's default font — the PDF then disagreed with both the
	 * canvas and the preview, while single-word families like Poppins
	 * happened to work and masked the problem.
	 *
	 * Only the PDF needs this rewrite; the browser wants the real name.
	 *
	 * @param string $family Font family (possibly a stack, possibly quoted).
	 * @return string
	 */
	protected static function pdf_font_family( $family ) {
		$family = trim( (string) $family );
		if ( '' === $family ) {
			return '';
		}
		// mPDF applies ONE family, so a stack ("Arial, sans-serif") reduces
		// to its first entry.
		$parts = explode( ',', $family );
		$first = trim( trim( $parts[0] ), "'\"" );
		return preg_replace( '/[^a-z0-9_-]/', '', strtolower( str_replace( ' ', '', $first ) ) );
	}

	protected function render_text_like( $id, array $a ) {
		$outer = $this->layout_rules( $a );
		$outer['display']  = 'table';
		$outer['overflow'] = ! empty( $a['autoFit'] ) ? 'auto' : 'visible';
		if ( ! empty( $a['backgroundColor'] ) && 'transparent' !== $a['backgroundColor'] ) {
			$outer['background'] = self::css_color( $a['backgroundColor'], 'transparent' );
		}
		$outer += $this->border_rules( $a );
		$outer += $this->shadow_rules( $a );

		$inner = array(
			'display'        => 'table-cell',
			'vertical-align' => self::css_keyword( $a['verticalAlign'] ?? '', array( 'top', 'middle', 'bottom' ), 'top' ),
			'text-align'     => self::css_keyword( $a['textAlign'] ?? '', array( 'left', 'center', 'right', 'justify' ), 'left' ),
			'font-family'    => self::pdf_font_family( ! empty( $a['fontFamily'] ) ? $a['fontFamily'] : $this->ctx_font ),
			'font-size'      => self::px( $a['fontSize'] ?? 14 ),
			'font-weight'    => self::css_font_weight( $a['fontWeight'] ?? '', '400' ),
			'line-height'    => self::num_str( $a['lineHeight'] ?? 1.4 ),
			'color'          => self::css_color( $a['color'] ?? '', '#111827' ),
		);
		if ( ! empty( $a['letterSpacing'] ) ) {
			$inner['letter-spacing'] = self::px( $a['letterSpacing'] );
		}

		$cls  = self::css_class( $id );
		$css  = ".{$cls}{" . self::rule_string( $outer ) . "}.{$cls}__in{" . self::rule_string( $inner ) . '}';
		$text = nl2br( esc_html( $a['text'] ?? '' ) );
		$html = "<div class=\"{$cls}\"><div class=\"{$cls}__in\">{$text}</div></div>";
		return array(
			'html' => $html,
			'css' => $css
		);
	}

	protected function render_image_like( $id, array $a ) {
		$rules  = $this->layout_rules( $a );
		$rules += $this->border_rules( $a );
		$rules += $this->shadow_rules( $a );
		$rules += $this->background_image_rules( $a['src'] ?? '', $a['fit'] ?? 'cover' );
		$cls    = self::css_class( $id );
		return array(
			'html' => "<div class=\"{$cls}\"></div>",
			'css' => ".{$cls}{" . self::rule_string( $rules ) . '}'
		);
	}

	protected function render_shape( $id, array $a ) {
		$rules  = $this->layout_rules( $a );
		$rules['background'] = self::css_color( $a['backgroundColor'] ?? '', 'transparent' );
		$rules += $this->border_rules( $a );
		$rules += $this->shadow_rules( $a );
		$cls    = self::css_class( $id );
		return array(
			'html' => "<div class=\"{$cls}\"></div>",
			'css' => ".{$cls}{" . self::rule_string( $rules ) . '}'
		);
	}

	protected function render_divider( $id, array $a ) {
		$rules = $this->layout_rules( $a );
		$rules['background'] = self::css_color( $a['backgroundColor'] ?? '', '#000000' );
		$cls   = self::css_class( $id );
		return array(
			'html' => "<div class=\"{$cls}\"></div>",
			'css' => ".{$cls}{" . self::rule_string( $rules ) . '}'
		);
	}

	/**
	 * QR block — a REAL scannable code, unlike the JS live-preview (which
	 * shows a value-box placeholder since there's no bundled JS QR encoder in
	 * the string renderer path). This is the actual PDF, so it reuses the
	 * same `generate_qr_png_file()` GD-based encoder `Renderer::swap_qr()`
	 * already relies on for the flow-based path. Not inside a `<td>` here
	 * (this renderer never emits tables), so a plain `<img>` sized directly
	 * is reliable — no need for the width-attribute workaround `swap_qr()`
	 * needed for the table-cell case.
	 *
	 * @param string $id Block id.
	 * @param array  $a  Block attributes.
	 */
	protected function render_qr( $id, array $a ) {
		$rules  = $this->layout_rules( $a );
		$rules += $this->border_rules( $a );
		$rules['background'] = self::css_color( $a['bg'] ?? '', '#FFFFFF' );
		$cls    = self::css_class( $id );
		$value  = (string) ( $a['value'] ?? '' );

		if ( '' === trim( $value ) ) {
			return array(
				'html' => "<div class=\"{$cls}\"></div>",
				'css' => ".{$cls}{" . self::rule_string( $rules ) . '}'
			);
		}

		$path = $this->generate_qr_png_file( $value, $a['fg'] ?? '#000000', $a['bg'] ?? '#FFFFFF' );
		if ( null !== $path ) {
			// A local filesystem path for mPDF, not a URL — esc_url() would mangle Windows paths.
			$html = "<div class=\"{$cls}\"><img src=\"" . esc_attr( $path ) . '" style="display:block;width:100%;height:100%" /></div>'; // phpcs:ignore WordPressVIPMinimum.Security.ProperEscapingFunction.hrefSrcEscUrl
			return array(
				'html' => $html,
				'css' => ".{$cls}{" . self::rule_string( $rules ) . '}'
			);
		}

		// GD unavailable: fall back to a bordered value box, same as the flow renderer's swap_qr().
		$inner = array(
			'display' => 'table-cell',
			'vertical-align' => 'middle',
			'text-align' => 'center',
			'font-size' => '6px',
			'line-height' => '1.2',
			'word-wrap' => 'break-word',
		);
		$rules['display'] = 'table';
		$css  = ".{$cls}{" . self::rule_string( $rules ) . "}.{$cls}__in{" . self::rule_string( $inner ) . '}';
		$html = "<div class=\"{$cls}\"><div class=\"{$cls}__in\">" . esc_html( $value ) . '</div></div>';
		return array(
			'html' => $html,
			'css' => $css
		);
	}

	/**
	 * `text-align` on a list only re-flows the <li> text — list markers
	 * (list-style-position:outside, the default) stay pinned to the list
	 * box's own left edge regardless. To move the whole list, markers
	 * included, as one unit, the list itself is shrink-to-fit
	 * (display:inline-block) and the alignment is applied to the OUTER
	 * full-width container instead — same fix as the JS mirror
	 * (renderer.js's renderList / CanvasBlock.js's list branch).
	 *
	 * @param string $id Block id.
	 * @param array  $a  Block attributes.
	 */
	protected function render_list( $id, array $a ) {
		$outer = $this->layout_rules( $a );
		$outer['overflow']   = ! empty( $a['autoFit'] ) ? 'auto' : 'visible';
		$outer['text-align'] = self::css_keyword( $a['textAlign'] ?? '', array( 'left', 'center', 'right', 'justify' ), 'left' );
		if ( ! empty( $a['backgroundColor'] ) && 'transparent' !== $a['backgroundColor'] ) {
			$outer['background'] = self::css_color( $a['backgroundColor'], 'transparent' );
		}
		$outer += $this->border_rules( $a );

		$ordered = 'ordered' === ( $a['listType'] ?? 'unordered' );
		$tag     = $ordered ? 'ol' : 'ul';
		$marker  = ( ! empty( $a['markerStyle'] ) && 'default' !== $a['markerStyle'] ) ? self::css_keyword( $a['markerStyle'], array( 'disc', 'circle', 'square', 'decimal', 'decimal-leading-zero', 'lower-alpha', 'upper-alpha', 'lower-latin', 'upper-latin', 'lower-roman', 'upper-roman', 'none' ), 'disc' ) : ( $ordered ? 'decimal' : 'disc' );
		$inner   = array(
			'display'         => 'inline-block',
			'margin'          => '0',
			'padding'         => '0 0 0 ' . self::px( $a['indent'] ?? 20 ),
			'list-style-type' => 'none' === $marker ? 'none' : $marker,
			'font-family'     => self::pdf_font_family( ! empty( $a['fontFamily'] ) ? $a['fontFamily'] : $this->ctx_font ),
			'font-size'       => self::px( $a['fontSize'] ?? 14 ),
			'font-weight'     => self::css_font_weight( $a['fontWeight'] ?? '', '400' ),
			'line-height'     => self::num_str( $a['lineHeight'] ?? 1.7 ),
			'color'           => self::css_color( $a['color'] ?? '', '#334155' ),
			'text-align'      => 'left',
		);
		$items = array_filter( array_map( 'trim', explode( "\n", (string) ( $a['items'] ?? '' ) ) ), function ( $l ) {
			return '' !== $l;
		} );
		$item_style = 'margin-bottom:' . self::px( $a['itemSpacing'] ?? 6 );
		$li = '';
		foreach ( $items as $item ) {
			$li .= '<li style="' . esc_attr( $item_style ) . '">' . esc_html( $item ) . '</li>';
		}
		$cls  = self::css_class( $id );
		$css  = ".{$cls}{" . self::rule_string( $outer ) . "}.{$cls}__in{" . self::rule_string( $inner ) . '}';
		$html = "<div class=\"{$cls}\"><{$tag} class=\"{$cls}__in\">{$li}</{$tag}></div>";
		return array(
			'html' => $html,
			'css' => $css
		);
	}

	/** Real brand vector icons — JS mirror: dev_emb/library/socialIcons.js. Keep both in sync. */
	const SOCIAL_ICONS = array(
		'facebook'  => array(
			'bg' => '#1877F2',
			'viewBox' => '0 0 24 24',
			'path' => 'M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036 26.805 26.805 0 0 0-.733-.009c-.707 0-1.259.096-1.675.309a1.686 1.686 0 0 0-.679.622c-.258.42-.374.995-.374 1.752v1.297h3.919l-.386 2.103-.287 1.564h-3.246v8.245C19.396 23.238 24 18.179 24 12.044c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.628 3.874 10.35 9.101 11.647Z'
		),
		'twitter'   => array(
			'bg' => '#000000',
			'viewBox' => '0 0 24 24',
			'path' => 'M14.234 10.162 22.977 0h-2.072l-7.591 8.824L7.251 0H.258l9.168 13.343L.258 24H2.33l8.016-9.318L16.749 24h6.993zm-2.837 3.299-.929-1.329L3.076 1.56h3.182l5.965 8.532.929 1.329 7.754 11.09h-3.182z'
		),
		'instagram' => array(
			'bg' => '#E4405F',
			'viewBox' => '0 0 24 24',
			'path' => 'M7.0301.084c-1.2768.0602-2.1487.264-2.911.5634-.7888.3075-1.4575.72-2.1228 1.3877-.6652.6677-1.075 1.3368-1.3802 2.127-.2954.7638-.4956 1.6365-.552 2.914-.0564 1.2775-.0689 1.6882-.0626 4.947.0062 3.2586.0206 3.6671.0825 4.9473.061 1.2765.264 2.1482.5635 2.9107.308.7889.72 1.4573 1.388 2.1228.6679.6655 1.3365 1.0743 2.1285 1.38.7632.295 1.6361.4961 2.9134.552 1.2773.056 1.6884.069 4.9462.0627 3.2578-.0062 3.668-.0207 4.9478-.0814 1.28-.0607 2.147-.2652 2.9098-.5633.7889-.3086 1.4578-.72 2.1228-1.3881.665-.6682 1.0745-1.3378 1.3795-2.1284.2957-.7632.4966-1.636.552-2.9124.056-1.2809.0692-1.6898.063-4.948-.0063-3.2583-.021-3.6668-.0817-4.9465-.0607-1.2797-.264-2.1487-.5633-2.9117-.3084-.7889-.72-1.4568-1.3876-2.1228C21.2982 1.33 20.628.9208 19.8378.6165 19.074.321 18.2017.1197 16.9244.0645 15.6471.0093 15.236-.005 11.977.0014 8.718.0076 8.31.0215 7.0301.0839m.1402 21.6932c-1.17-.0509-1.8053-.2453-2.2287-.408-.5606-.216-.96-.4771-1.3819-.895-.422-.4178-.6811-.8186-.9-1.378-.1644-.4234-.3624-1.058-.4171-2.228-.0595-1.2645-.072-1.6442-.079-4.848-.007-3.2037.0053-3.583.0607-4.848.05-1.169.2456-1.805.408-2.2282.216-.5613.4762-.96.895-1.3816.4188-.4217.8184-.6814 1.3783-.9003.423-.1651 1.0575-.3614 2.227-.4171 1.2655-.06 1.6447-.072 4.848-.079 3.2033-.007 3.5835.005 4.8495.0608 1.169.0508 1.8053.2445 2.228.408.5608.216.96.4754 1.3816.895.4217.4194.6816.8176.9005 1.3787.1653.4217.3617 1.056.4169 2.2263.0602 1.2655.0739 1.645.0796 4.848.0058 3.203-.0055 3.5834-.061 4.848-.051 1.17-.245 1.8055-.408 2.2294-.216.5604-.4763.96-.8954 1.3814-.419.4215-.8181.6811-1.3783.9-.4224.1649-1.0577.3617-2.2262.4174-1.2656.0595-1.6448.072-4.8493.079-3.2045.007-3.5825-.006-4.848-.0608M16.953 5.5864A1.44 1.44 0 1 0 18.39 4.144a1.44 1.44 0 0 0-1.437 1.4424M5.8385 12.012c.0067 3.4032 2.7706 6.1557 6.173 6.1493 3.4026-.0065 6.157-2.7701 6.1506-6.1733-.0065-3.4032-2.771-6.1565-6.174-6.1498-3.403.0067-6.156 2.771-6.1496 6.1738M8 12.0077a4 4 0 1 1 4.008 3.9921A3.9996 3.9996 0 0 1 8 12.0077'
		),
		'linkedin'  => array(
			'bg' => '#0A66C2',
			'viewBox' => '0 0 16 16',
			'path' => 'M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854zm4.943 12.248V6.169H2.542v7.225zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248S2.4 3.226 2.4 3.934c0 .694.521 1.248 1.327 1.248zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016l.016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225z'
		),
		'youtube'   => array(
			'bg' => '#FF0000',
			'viewBox' => '0 0 24 24',
			'path' => 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z'
		),
		'github'    => array(
			'bg' => '#181717',
			'viewBox' => '0 0 24 24',
			'path' => 'M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12',
		),
	);

	protected function render_social( $id, array $a ) {
		$size  = (int) ( $a['iconSize'] ?? 36 );
		$gap   = (int) ( $a['gap'] ?? 8 );
		$shape = $a['shape'] ?? 'circle';
		$radius = 'circle' === $shape ? $size / 2 : ( 'rounded' === $shape ? max( 4, floor( $size / 4 ) ) : 0 );
		$inset  = $size * 0.28;
		$glyph  = $size - $inset * 2;

		$icons = '';
		$i = 0;
		foreach ( self::SOCIAL_ICONS as $key => $meta ) {
			// esc_url drops `javascript:` and other unsafe schemes.
			$url = esc_url( trim( (string) ( $a[ $key ] ?? '' ) ) );
			if ( '' === $url ) {
				continue;
			}
			$margin = $i > 0 ? 'margin-left:' . self::px( $gap ) . ';' : '';
			// `<g transform>`, NOT a nested `<svg viewBox>`: mPDF's SVG parser
			// doesn't implement nested-viewBox mapping, so the brand glyphs
			// came out at the wrong scale and offset in the PDF (the squashed,
			// garbled icon row). Mirrors socialIconSvg() in the JS library.
			$vb_parts = preg_split( '/\s+/', trim( (string) $meta['viewBox'] ) );
			$vb       = isset( $vb_parts[2] ) ? (float) $vb_parts[2] : 24.0;
			$scale    = $vb > 0 ? $glyph / $vb : 1;
			$svg = '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '" xmlns="http://www.w3.org/2000/svg" style="display:block">'
				. '<rect width="' . $size . '" height="' . $size . '" rx="' . $radius . '" ry="' . $radius . '" fill="' . esc_attr( $meta['bg'] ) . '"/>'
				. '<g transform="translate(' . self::num_str( $inset ) . ',' . self::num_str( $inset ) . ') scale(' . self::num_str( $scale ) . ')">'
				. '<path d="' . esc_attr( $meta['path'] ) . '" fill="#FFFFFF"/></g></svg>';
			$icons .= '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer" style="display:inline-block;vertical-align:top;' . $margin . 'text-decoration:none">' . $svg . '</a>';
			++$i;
		}//end foreach
		$cls = self::css_class( $id );
		$css = ".{$cls}{" . self::rule_string( $this->layout_rules( $a ) ) . '}';
		return array(
			'html' => "<div class=\"{$cls}\">{$icons}</div>",
			'css' => $css
		);
	}

	protected function render_signature( $id, array $a ) {
		$lines = array_filter( array( $a['greeting'] ?? '', $a['name'] ?? '', $a['title'] ?? '', $a['company'] ?? '' ), function ( $l ) {
			return '' !== trim( (string) $l );
		} );
		$out = $this->render_lines( $id, $a, implode( "\n", $lines ), 14, '#334155' );
		return $out;
	}

	/**
	 * Shared renderer for simple stacked-line text blocks (address, signature).
	 *
	 * @param string $id                Block id.
	 * @param array  $a                 Block attributes.
	 * @param string $raw_lines         Newline-separated lines.
	 * @param int    $default_font_size Font size when the block sets none.
	 * @param string $default_color     Colour when the block sets none.
	 */
	protected function render_lines( $id, array $a, $raw_lines, $default_font_size, $default_color ) {
		$outer = $this->layout_rules( $a );
		$outer['font-family'] = self::pdf_font_family( ! empty( $a['fontFamily'] ) ? $a['fontFamily'] : $this->ctx_font );
		$outer['font-size']   = self::px( $a['fontSize'] ?? $default_font_size );
		$outer['font-weight'] = self::css_font_weight( $a['fontWeight'] ?? '', '400' );
		$outer['line-height'] = self::num_str( $a['lineHeight'] ?? 1.6 );
		$outer['color']       = self::css_color( $a['color'] ?? '', $default_color );
		$outer['text-align']  = self::css_keyword( $a['textAlign'] ?? '', array( 'left', 'center', 'right', 'justify' ), 'left' );

		$lines = explode( "\n", (string) $raw_lines );
		$html_lines = '';
		foreach ( $lines as $line ) {
			if ( '' === trim( $line ) ) {
				continue;
			}
			$html_lines .= '<div>' . esc_html( $line ) . '</div>';
		}
		$cls = self::css_class( $id );
		return array(
			'html' => "<div class=\"{$cls}\">{$html_lines}</div>",
			'css' => ".{$cls}{" . self::rule_string( $outer ) . '}'
		);
	}

	protected static function css_class( $id ) {
		// Namespaced + instance-scoped so a saved id can never collide with
		// theme/admin CSS or another block — matches the JS class prefix.
		return 'acdcert-' . preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $id );
	}

	protected static function px( $v ) {
		return self::num_str( (float) $v ) . 'px';
	}

	protected static function num_str( $v ) {
		$str = rtrim( rtrim( number_format( (float) $v, 3, '.', '' ), '0' ), '.' );
		return '' === $str ? '0' : $str;
	}

	/**
	 * Flat `prop:value;prop2:value2` CSS text — lands in a <style> block, no HTML escaping.
	 *
	 * @param array $rules Property => value map.
	 */
	protected static function rule_string( array $rules ) {
		$parts = array();
		foreach ( $rules as $k => $v ) {
			if ( null === $v || '' === $v ) {
				continue;
			}
			$parts[] = $k . ':' . $v;
		}
		return implode( ';', $parts );
	}
}
