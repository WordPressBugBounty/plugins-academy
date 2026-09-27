<?php
/**
 * Server-side certificate page wrapper.
 *
 * The certificate builder reuses the mail builder's grid system and renders
 * its block content to mPDF-safe, inline-styled table HTML CLIENT-SIDE. That
 * content is stored verbatim; this class only builds the fixed-size page shell
 * around it — @page geometry, the background (color + image), and the real QR
 * code swapped in for the editor's placeholder. No per-block rendering happens
 * here, so the mail blocks (heading/text/image/columns/section/…) never need a
 * PHP twin.
 *
 * @package Academy\Certificates
 */

namespace AcademyCertificates\Render;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Renderer {

	/** [ long-edge, short-edge ] in millimetres. */
	const PAGE_SIZES = array(
		'A4'     => array( 297, 210 ),
		'Letter' => array( 279.4, 215.9 ),
	);

	/**
	 * Resolve a certificate tree's page metrics.
	 *
	 * @param array $root Tree root.
	 */
	public static function page_dims( array $root ) {
		$a    = isset( $root['attributes'] ) && is_array( $root['attributes'] ) ? $root['attributes'] : array();
		$size = isset( $a['pageSize'] ) && isset( self::PAGE_SIZES[ $a['pageSize'] ] ) ? $a['pageSize'] : 'A4';
		$landscape = ( isset( $a['orientation'] ) ? $a['orientation'] : 'landscape' ) !== 'portrait';
		return array(
			'size'        => $size,
			'orientation' => $landscape ? 'L' : 'P',
		);
	}

	/**
	 * Wrap stored content HTML into a print-ready page.
	 *
	 * @param array  $root         The tree root (for page size / bg attributes).
	 * @param string $content_html The client-rendered, merge-substituted content.
	 * @return array { css, html, size, orientation }
	 */
	public function render( array $root, $content_html ) {
		$a    = isset( $root['attributes'] ) && is_array( $root['attributes'] ) ? $root['attributes'] : array();
		$dims = self::page_dims( $root );

		return array(
			'css'         => $this->page_css( $a ),
			'html'        => $this->swap_divider( $this->swap_qr( $this->swap_local_images( (string) $content_html ) ) ),
			'size'        => $dims['size'],
			'orientation' => $dims['orientation'],
		);
	}

	/**
	 * Page and body shell CSS (font, background color + image).
	 *
	 * The background belongs on `@page`, NOT on `body`: mPDF paints the page
	 * background only from the @page rule and silently ignores a background on
	 * body — which is why a certificate's artwork never reached the PDF even
	 * though mPDF had fetched and embedded the image. Verified against the
	 * bundled mpdf 8.2. `body` keeps only the typography.
	 *
	 * @param array $a Root attributes.
	 */
	protected function page_css( array $a ) {
		$font = ! empty( $a['fontFamily'] ) ? preg_replace( '/[^a-zA-Z0-9 ,_-]/', '', (string) $a['fontFamily'] ) : 'Poppins';
		$bg   = self::css_color( isset( $a['bgColor'] ) ? $a['bgColor'] : '', '#FFFFFF' );

		$page = 'margin:0;background-color:' . $bg . ';';
		if ( ! empty( $a['bgImage'] ) ) {
			$page .= "background-image:url('" . $this->image_source( $a['bgImage'] ) . "');";
			$page .= 'background-image-resize:' . self::bg_resize( isset( $a['bgSize'] ) ? $a['bgSize'] : 'cover' ) . ';';
		}

		$body = "margin:0;padding:0;font-family:'" . esc_attr( $font ) . "';";

		return '@page{' . $page . '}body{' . $body . '}';
	}

	/**
	 * A filesystem path for an image this site serves, else the URL untouched.
	 *
	 * The mPDF library fetches a URL over HTTP, which is both slower than reading the file
	 * and fragile: outside a web request `site_url()` reports http:// (is_ssl()
	 * is false in CLI and cron), the fetch fails, and the artwork silently
	 * vanishes from the PDF. Anything under this site's uploads or the plugin's
	 * own assets is therefore handed to mPDF as a path.
	 *
	 * @param string $url Image URL from the certificate's page settings.
	 */
	protected function image_source( $url ) {
		$url = (string) $url;

		// A base64 `data:` URI (the editor's media-picker fallback used before
		// a real WP media upload was wired in, and still possible on an
		// already-saved design). `esc_url()` below strips any scheme outside
		// its allowlist — `data:` included — collapsing it to '' and making
		// the image vanish from the PDF, so materialize it to a real file
		// first, the same "real file over base64" fix already applied to the
		// QR and divider images elsewhere in this class.
		if ( 0 === strpos( $url, 'data:image/' ) ) {
			$path = $this->materialize_data_uri( $url );
			return null !== $path ? $path : '';
		}

		$uploads = wp_upload_dir();
		$roots   = array(
			$uploads['baseurl']  => $uploads['basedir'],
			ACADEMY_ASSETS_URI   => ACADEMY_ASSETS_DIR_PATH,
		);

		// Scheme-insensitive: the stored URL and site_url() can disagree on
		// http vs https depending on where the certificate was authored.
		$target = preg_replace( '#^https?://#', '', $url );

		foreach ( $roots as $base_url => $base_dir ) {
			$needle = preg_replace( '#^https?://#', '', untrailingslashit( $base_url ) );
			if ( '' === $needle || 0 !== strpos( $target, $needle ) ) {
				continue;
			}
			$path = self::path_within( untrailingslashit( $base_dir ) . strtok( substr( $target, strlen( $needle ) ), '?' ), $base_dir );
			// A quote would break out of the CSS url('…'); fall back to the URL.
			if ( null !== $path && false === strpos( $path, "'" ) ) {
				return $path;
			}
		}

		// mPDF fetches anything else itself, from the server. Only hand it a
		// URL WordPress considers safe for a server-side request, so a design
		// cannot aim that fetch at localhost or the private network.
		return wp_http_validate_url( $url ) ? esc_url( $url ) : '';
	}

	/**
	 * The resolved path, if it is a real file inside `$base_dir`; null otherwise.
	 *
	 * The path is built from a URL, so `../` segments in it must not reach
	 * files outside the directory the URL was matched against.
	 *
	 * @param string $path     Candidate filesystem path.
	 * @param string $base_dir Directory the path has to stay inside.
	 * @return string|null
	 */
	protected static function path_within( $path, $base_dir ) {
		$real = realpath( $path );
		$base = realpath( $base_dir );
		if ( false === $real || false === $base || ! is_file( $real ) ) {
			return null;
		}
		return 0 === strpos( $real, trailingslashit( $base ) ) ? $real : null;
	}

	/**
	 * A colour that is safe inside a CSS rule, else the fallback.
	 *
	 * Tree attributes are author-supplied and land verbatim in the stylesheet
	 * mPDF parses, so a value like `red;background:url(…)` must not be able
	 * to add declarations (and with them, server-side fetches).
	 *
	 * @param mixed  $value    Colour from the tree.
	 * @param string $fallback Used when the value is not a plain colour.
	 * @return string
	 */
	protected static function css_color( $value, $fallback ) {
		$value = trim( (string) $value );
		if ( preg_match( '/^(#[0-9a-f]{3,8}|(rgb|hsl)a?\([0-9.,%\s\/]+\)|[a-z]+)$/i', $value ) ) {
			return $value;
		}
		return $fallback;
	}

	/**
	 * One of an allowed set of CSS keywords, else the fallback.
	 *
	 * @param mixed  $value    Keyword from the tree.
	 * @param array  $allowed  Accepted keywords.
	 * @param string $fallback Used when the value is not in the set.
	 * @return string
	 */
	protected static function css_keyword( $value, array $allowed, $fallback ) {
		$value = strtolower( trim( (string) $value ) );
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	/**
	 * A CSS font-weight, else the fallback.
	 *
	 * @param mixed  $value    Weight from the tree.
	 * @param string $fallback Used when the value is not a font-weight.
	 * @return string
	 */
	protected static function css_font_weight( $value, $fallback ) {
		$value = strtolower( trim( (string) $value ) );
		return preg_match( '/^(normal|bold|bolder|lighter|[1-9]00)$/', $value ) ? $value : $fallback;
	}

	/**
	 * Decode a `data:image/<ext>;base64,<data>` URI to a real file under the
	 * same uploads scratch dir the QR/divider PNGs use, and return its path.
	 * Cached by a hash of the encoded data so the same design's repeat
	 * renders (preview, re-download) reuse the file instead of re-decoding.
	 *
	 * @param string $data_uri Full `data:image/...;base64,...` string.
	 * @return string|null Absolute file path, or null if it couldn't be decoded.
	 */
	protected function materialize_data_uri( $data_uri ) {
		if ( ! preg_match( '/^data:image\/([a-zA-Z0-9.+-]+);base64,(.+)$/s', $data_uri, $m ) ) {
			return null;
		}

		// The extension names a file written under uploads, so it must never
		// be taken from the URI as-is: `data:image/php;base64,…` would drop an
		// executable script there. Only raster formats mPDF embeds are
		// accepted, and the decoded bytes must really be that format.
		$types = array(
			'png'  => 'image/png',
			'jpg'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'gif'  => 'image/gif',
			'webp' => 'image/webp',
		);
		$ext   = strtolower( $m[1] );
		if ( ! isset( $types[ $ext ] ) ) {
			return null;
		}

		$data = base64_decode( $m[2], true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $data ) {
			return null;
		}

		$info = getimagesizefromstring( $data );
		if ( false === $info || $types[ $ext ] !== $info['mime'] ) {
			return null;
		}
		$ext = 'jpeg' === $ext ? 'jpg' : $ext;

		$dir = trailingslashit( $this->get_upload_dir() ) . 'images';
		wp_mkdir_p( $dir );
		$path = $dir . '/' . md5( $m[2] ) . '.' . $ext;

		// phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents -- cache file inside the uploads dir
		if ( ! file_exists( $path ) && false === file_put_contents( $path, $data ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
		// phpcs:enable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
			return null;
		}

		return $path;
	}

	/**
	 * Rewrite every `<img src="…">` in the content HTML through
	 * `image_source()` — the same local-file-path swap the page background
	 * already gets. Without it, an Image block pointing at this site's own
	 * uploads/assets (e.g. a logo dragged into the certificate) silently
	 * disappears from the real PDF: mPDF fetches `<img>` `src` URLs itself,
	 * separately from the background-image CSS path, and that fetch hits the
	 * same http(s)-mismatch/no-remote-fetch failure mode `image_source()` was
	 * written to avoid — it just was never applied to this second place a
	 * URL can appear.
	 *
	 * @param string $html Content HTML.
	 */
	protected function swap_local_images( $html ) {
		return preg_replace_callback(
			'/(<img\s[^>]*\bsrc=")([^"]*)(")/i',
			function ( $m ) {
				return $m[1] . esc_attr( $this->image_source( html_entity_decode( $m[2], ENT_QUOTES ) ) ) . $m[3];
			},
			$html
		);
	}

	/**
	 * The editor's `bgSize` → mPDF's `background-image-resize`.
	 *
	 * The mPDF library cannot crop, so it has no true CSS `cover`: 6 stretches to both page
	 * dimensions, which is what a background drawn at the page ratio wants, and
	 * is the closest thing to a full bleed. `contain` maps to 3 — scale down to
	 * fit inside, keeping the aspect ratio.
	 *
	 * @param string $size cover|contain|stretch.
	 */
	protected static function bg_resize( $size ) {
		return 'contain' === $size ? 3 : 6;
	}

	/**
	 * Replace each `data-emb-qr="VALUE"` placeholder the editor emitted with a
	 * real QR image, or, failing that, a bordered box showing the URL so the
	 * certificate still carries verifiable information. VALUE is already
	 * merge-substituted by the caller, so it holds the resolved verification
	 * URL. Shared by both render paths that call `render()` — the plain-HTML
	 * "view certificate" page and the PDF generator — so one fix covers both.
	 *
	 * @param string $html Content HTML.
	 */
	protected function swap_qr( $html ) {
		return preg_replace_callback(
			'/<div\s+data-emb-qr="([^"]*)"([^>]*)>.*?<\/div>/s',
			function ( $m ) {
				$value = html_entity_decode( $m[1], ENT_QUOTES );
				$attrs = $m[2]; // keeps the inline style (size/background) + fg/bg data attrs
				if ( '' === trim( $value ) ) {
					return '<div' . $attrs . '>&nbsp;</div>';
				}
				$path = $this->generate_qr_png_file(
					$value,
					$this->qr_color( $attrs, 'data-emb-qr-fg', '#000000' ),
					$this->qr_color( $attrs, 'data-emb-qr-bg', '#FFFFFF' )
				);
				if ( null !== $path ) {
					// mPDF ignores `display:inline-block` on a <div> nested in a
					// table (same shrink-to-fit bug hit elsewhere in this
					// renderer) — the container silently expands to the full
					// table-cell width, so an `<img width:100%>` inside it blows
					// up to that width instead of the block's configured size.
					// Reading the intended px size back off the div's own style
					// and setting it directly on the <img> sidesteps the container
					// entirely.
					$size = $this->qr_box_size( $attrs );
					$dim  = $size ? ' width="' . (int) $size . '" height="' . (int) $size . '"' : '';
					// A real file path, NOT a base64 data: URI — a broken/failed
					// base64 image decode was observed not just leaving a blank
					// box but corrupting the position of whatever block came
					// after it on the page. File paths are the same trusted,
					// already-proven image-loading path `image_source()` uses
					// for backgrounds, for the same reason: more reliable than
					// asking mPDF to decode an embedded image inline.
					// A local filesystem path for mPDF, not a URL — esc_url() would mangle Windows paths.
					return '<div' . $attrs . '><img src="' . esc_attr( $path ) . '"' . $dim . ' style="display:block' . ( $size ? '' : ';width:100%;height:100%' ) . '" /></div>'; // phpcs:ignore WordPressVIPMinimum.Security.ProperEscapingFunction.hrefSrcEscUrl
				}//end if
				$box = ';text-align:center;font-size:6px;line-height:1.2;word-wrap:break-word;border:1px solid #000;';
				return '<div' . $attrs . ' data-fallback="' . esc_attr( $box ) . '">' . esc_html( $value ) . '</div>';
			},
			$html
		);
	}

	/**
	 * Pull the `width:NNpx` the editor set on the placeholder div's inline style.
	 *
	 * @param string $attrs Raw attribute string of the placeholder div.
	 */
	protected function qr_box_size( $attrs ) {
		if ( preg_match( '/style="[^"]*width:\s*(\d+)px/', $attrs, $m ) ) {
			return (int) $m[1];
		}
		return 0;
	}

	/**
	 * Pull a `data-emb-qr-fg`/`-bg` hex color out of the placeholder div's raw attribute string.
	 *
	 * @param string $attrs     Raw attribute string of the placeholder div.
	 * @param string $data_attr Data attribute holding the colour.
	 * @param string $default   Colour when the attribute is missing or invalid.
	 */
	protected function qr_color( $attrs, $data_attr, $default ) {
		if ( preg_match( '/' . preg_quote( $data_attr, '/' ) . '="(#[0-9a-fA-F]{3,6})"/', $attrs, $m ) ) {
			return $m[1];
		}
		return $default;
	}

	/**
	 * Render `$value` (already merge-substituted) as a QR code PNG and save it
	 * to a real file, returning its filesystem path. No QR package
	 * (`mpdf/qrcode`) is installed in this plugin's vendor tree — it's only
	 * ever a suggestion of mpdf/mpdf, never actually required — so a small,
	 * dependency-free encoder is vendored instead (`lib/QrGenerator.php`,
	 * MIT-licensed, no Composer package needed) rather than leaving the
	 * certificate's QR block permanently falling back to a plain bordered URL
	 * box. A file path (not a base64 data: URI) mirrors `image_source()`
	 * below for the same reason: mPDF decoding an embedded image inline has
	 * proven less reliable here.
	 *
	 * Cached by a hash of its inputs under the same `mpdf` scratch directory
	 * mPDF's own tempDir uses, so repeat renders of the same certificate
	 * (preview, re-download) reuse the file instead of re-encoding.
	 *
	 * @param string $value Value to encode.
	 * @param string $fg    Foreground hex colour.
	 * @param string $bg    Background hex colour.
	 * @return string|null Absolute file path, or null if GD isn't available.
	 */
	protected function generate_qr_png_file( $value, $fg, $bg ) {
		if ( ! function_exists( 'imagecreatetruecolor' ) ) {
			return null;
		}

		$dir = trailingslashit( $this->get_upload_dir() ) . 'mpdf/qr';
		wp_mkdir_p( $dir );
		$path = $dir . '/' . md5( $value . '|' . $fg . '|' . $bg ) . '.png';

		if ( file_exists( $path ) ) {
			return $path;
		}

		require_once __DIR__ . '/../lib/QrGenerator.php';

		try {
			$qr = \Academy\Certificates\QrGenerator\QRCode::getMinimumQRCode( $value, QR_ERROR_CORRECT_LEVEL_M );
			// size=6px/module, margin=12px — comfortably scannable at the block's
			// default 120px box while still fitting the smallest custom size.
			$image = $qr->createImage( 6, 12, hexdec( ltrim( $fg, '#' ) ), hexdec( ltrim( $bg, '#' ) ) );
		} catch ( \RuntimeException $e ) {
			// Unencodable value (e.g. too long for any QR version) — keep the placeholder.
			return null;
		}

		imagepng( $image, $path );
		imagedestroy( $image );

		return file_exists( $path ) ? $path : null;
	}

	/**
	 * Replace each `data-emb-divider="1"` placeholder with a real solid-color
	 * PNG `<img>`. Every CSS/table/SVG technique tried for this line —
	 * `border-top`, a background-filled cell, an inline SVG rect/line —
	 * collapsed to a sliver or vanished in mPDF despite rendering correctly
	 * in the canvas; a real raster image is the one content type proven
	 * reliable in this pipeline (see `swap_qr()`). Non-solid styles
	 * (dashed/dotted/double) keep their CSS `border-top` — that div only
	 * carries no background (see `renderDivider` in blocks.js), so it's left
	 * alone here since this fix only ever addressed the solid/background case.
	 *
	 * @param string $html Content HTML.
	 */
	protected function swap_divider( $html ) {
		return preg_replace_callback(
			'/<div\s+data-emb-divider="1"([^>]*)><\/div>/s',
			function ( $m ) {
				$attrs     = $m[1];
				$color     = $this->qr_color( $attrs, 'data-emb-divider-color', '#E2E8F0' );
				$thickness = 0;
				if ( preg_match( '/data-emb-divider-thickness="(\d+)"/', $attrs, $tm ) ) {
					$thickness = (int) $tm[1];
				}
				if ( $thickness < 1 ) {
					return '<div' . $attrs . '></div>';
				}
				$path = $this->generate_divider_png_file( $color, $thickness );
				if ( null === $path ) {
					return '<div' . $attrs . '></div>';
				}
				// A local filesystem path for mPDF, not a URL — esc_url() would mangle Windows paths.
				return '<img src="' . esc_attr( $path ) . '" width="100%" height="' . $thickness . '" style="display:block" />'; // phpcs:ignore WordPressVIPMinimum.Security.ProperEscapingFunction.hrefSrcEscUrl
			},
			$html
		);
	}

	/**
	 * A 2x{thickness}px solid-color PNG, saved to a real file and cached by
	 * a hash of its inputs (same rationale as `generate_qr_png_file()`: a
	 * file path is far more reliably decoded by mPDF than a base64 data:
	 * URI). Stretched to the full row width via the `<img width="100%">`
	 * that references it.
	 *
	 * @param string $color     Hex colour.
	 * @param int    $thickness Height in px.
	 */
	protected function generate_divider_png_file( $color, $thickness ) {
		if ( ! function_exists( 'imagecreatetruecolor' ) ) {
			return null;
		}

		$dir = trailingslashit( $this->get_upload_dir() ) . 'mpdf/divider';
		wp_mkdir_p( $dir );
		$path = $dir . '/' . md5( $color . '|' . $thickness ) . '.png';

		if ( file_exists( $path ) ) {
			return $path;
		}

		$rgb   = sscanf( ltrim( $color, '#' ), '%02x%02x%02x' );
		$image = imagecreatetruecolor( 2, $thickness );
		$fill  = imagecolorallocate( $image, $rgb[0] ?? 0, $rgb[1] ?? 0, $rgb[2] ?? 0 );
		imagefilledrectangle( $image, 0, 0, 1, $thickness - 1, $fill );

		imagepng( $image, $path );
		imagedestroy( $image );

		return file_exists( $path ) ? $path : null;
	}

	/** Shared uploads scratch dir — mirrors the PDF generator's own `get_upload_dir()`. */
	protected function get_upload_dir() {
		$upload = wp_upload_dir();
		return trailingslashit( $upload['basedir'] ) . 'academy_uploads';
	}
}
