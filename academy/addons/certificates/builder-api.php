<?php
/**
 * REST endpoints for the React certificate builder.
 *
 * The builder stores its design as a JSON tree in the `_academy_certificate_tree`
 * post meta of an `academy_certificate` post (post_content is left empty — that
 * is how Helper::render_certificate tells a new certificate from a legacy
 * Gutenberg one). These routes load and persist that tree, decoupled from the
 * block-editor REST surface.
 *
 * @package Academy\Certificates
 */

namespace AcademyCertificates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BuilderApi {

	const META_KEY  = '_academy_certificate_tree';
	const META_HTML = '_academy_certificate_html';
	const POST_TYPE = 'academy_certificate';

	public static function init() {
		$self = new self();
		add_action( 'init', array( $self, 'register_meta' ) );
		add_action( 'rest_api_init', array( $self, 'register_routes' ) );
	}

	public function register_meta() {
		$auth = function () {
			return current_user_can( 'edit_academy_certificates' );
		};
		// The tree is the editable source of truth; the html is the pre-rendered,
		// mPDF-safe content the PDF generator wraps into the page.
		foreach ( array( self::META_KEY, self::META_HTML ) as $key ) {
			register_post_meta(
				self::POST_TYPE,
				$key,
				array(
					'type'          => 'string',
					'single'        => true,
					'show_in_rest'  => false, // Served via the dedicated routes below.
					'auth_callback' => $auth,
				)
			);
		}
	}

	public function register_routes() {
		$namespace = ACADEMY_PLUGIN_SLUG . '/v1';

		register_rest_route(
			$namespace,
			'/certificate-builder',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_permission_check' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/certificate-builder/(?P<id>[\d]+)',
			array(
				'args' => array(
					'id' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'item_permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'item_permission_check' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/certificate-builder/(?P<id>[\d]+)/preview',
			array(
				'args' => array(
					'id' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'preview_item' ),
					// Same broadened check as `/preview-tree`, not
					// `item_permission_check`: the course-settings certificate
					// picker's "Preview as PDF" action calls this with the
					// certificate's own saved tree (fetched via
					// `/preview-tree` first) for a course editor who usually
					// can't edit that certificate directly. Rendering a
					// client-supplied tree to PDF is inherently read-only
					// (nothing is persisted), so the same read-access rule
					// applies.
					'permission_callback' => array( $this, 'preview_tree_permission_check' ),
				),
			)
		);

		// Read-only design lookup for surfaces that only need to *show* a
		// published certificate's design (the course-settings certificate
		// picker's live thumbnails), not edit it. `item_permission_check`
		// deliberately requires `edit_post` on the certificate itself — right
		// for the builder, wrong here: the instructor picking a certificate
		// for their course usually didn't author it and can't edit it, but
		// still needs to see what it looks like.
		register_rest_route(
			$namespace,
			'/certificate-builder/(?P<id>[\d]+)/preview-tree',
			array(
				'args' => array(
					'id' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'preview_tree_permission_check' ),
				),
			)
		);
	}

	/**
	 * Creating a certificate needs the post type's own create capability.
	 *
	 * @return true|\WP_Error
	 */
	public function create_permission_check() {
		$post_type = get_post_type_object( self::POST_TYPE );
		if ( current_user_can( 'manage_options' ) || ( $post_type && current_user_can( $post_type->cap->create_posts ) ) ) {
			return true;
		}
		return $this->forbidden();
	}

	/**
	 * Loading, saving and previewing act on one certificate, so they need
	 * permission to edit that certificate, not just certificates in general.
	 *
	 * @param \WP_REST_Request $request Request carrying the certificate id.
	 * @return true|\WP_Error
	 */
	public function item_permission_check( $request ) {
		$id   = (int) $request['id'];
		$post = get_post( $id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return new \WP_Error( 'rest_not_found', __( 'Certificate not found.', 'academy' ), array( 'status' => 404 ) );
		}
		if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_post', $id ) ) {
			return true;
		}
		return $this->forbidden();
	}

	/**
	 * `/preview-tree`: read-only, and only for a published certificate —
	 * anyone who can edit certificates OR courses may look, not just whoever
	 * can edit that one certificate. Never exposes a draft's design to
	 * someone who can't already edit it.
	 *
	 * @param \WP_REST_Request $request Request carrying the certificate id.
	 * @return true|\WP_Error
	 */
	public function preview_tree_permission_check( $request ) {
		$id   = (int) $request['id'];
		$post = get_post( $id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return new \WP_Error( 'rest_not_found', __( 'Certificate not found.', 'academy' ), array( 'status' => 404 ) );
		}
		if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_post', $id ) ) {
			return true;
		}
		if ( 'publish' !== $post->post_status ) {
			return $this->forbidden();
		}
		if ( current_user_can( 'edit_academy_certificates' ) || current_user_can( 'edit_academy_courses' ) ) {
			return true;
		}
		return $this->forbidden();
	}

	/**
	 * The error both permission callbacks deny with.
	 *
	 * @return \WP_Error
	 */
	protected function forbidden() {
		return new \WP_Error( 'rest_forbidden', __( 'You are not allowed to edit certificates.', 'academy' ), array( 'status' => rest_authorization_required_code() ) );
	}

	public function get_item( $request ) {
		$id   = (int) $request['id'];
		$post = get_post( $id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return new \WP_Error( 'rest_not_found', __( 'Certificate not found.', 'academy' ), array( 'status' => 404 ) );
		}

		$raw  = get_post_meta( $id, self::META_KEY, true );
		$tree = $raw ? json_decode( $raw, true ) : null;

		// No tree meta at all means this certificate predates the tree
		// builder (it was made in the old Ablocks/Gutenberg one, whose
		// content lives in post_content instead). Convert that content into
		// a tree for THIS response only — Save persists it for real, same
		// as any other edit — so the old design shows up instead of a blank
		// canvas that quietly discards it.
		$migrated = false;
		if ( ! $tree && '' !== trim( (string) $post->post_content ) ) {
			require_once __DIR__ . '/legacy-migrator.php';
			$tree     = LegacyMigrator::convert( $post->post_content );
			$migrated = null !== $tree;
		}

		return rest_ensure_response(
			array(
				'id'       => $id,
				'title'    => html_entity_decode( get_the_title( $id ) ),
				'status'   => $post->post_status,
				'tree'     => $tree, // null → the editor opens a blank tree.
				'migrated' => $migrated,
			)
		);
	}

	public function create_item( $request ) {
		$title = $this->sanitize_title( $request->get_param( 'title' ) );

		$post_id = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_title'   => $title,
				'post_status'  => $this->can_publish() ? 'publish' : 'draft',
				'post_content' => '',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$this->save_design( $post_id, $request->get_param( 'tree' ), $request->get_param( 'html' ) );

		return rest_ensure_response( array( 'id' => (int) $post_id ) );
	}

	/**
	 * Whether the current user may publish certificates.
	 *
	 * @return bool
	 */
	protected function can_publish() {
		$post_type = get_post_type_object( self::POST_TYPE );
		return current_user_can( 'manage_options' ) || ( $post_type && current_user_can( $post_type->cap->publish_posts ) );
	}

	public function update_item( $request ) {
		$id   = (int) $request['id'];
		$post = get_post( $id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return new \WP_Error( 'rest_not_found', __( 'Certificate not found.', 'academy' ), array( 'status' => 404 ) );
		}

		$update = array( 'ID' => $id );
		if ( null !== $request->get_param( 'title' ) ) {
			$update['post_title'] = $this->sanitize_title( $request->get_param( 'title' ) );
		}
		if ( null !== $request->get_param( 'status' ) ) {
			$status = sanitize_key( $request->get_param( 'status' ) );
			if ( in_array( $status, array( 'publish', 'draft', 'pending' ), true ) && ( 'publish' !== $status || $this->can_publish() ) ) {
				$update['post_status'] = $status;
			}
		}
		if ( count( $update ) > 1 ) {
			$result = wp_update_post( $update, true );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		if ( null !== $request->get_param( 'tree' ) ) {
			$this->save_design( $id, $request->get_param( 'tree' ), $request->get_param( 'html' ) );
		}

		return rest_ensure_response( array(
			'id' => $id,
			'success' => true
		) );
	}

	/**
	 * Persist a design: the editable tree (source of truth) and, for
	 * `version: 2` (flow) trees only, the pre-rendered content HTML the PDF
	 * generator wraps. `version: 3` (canvas) trees render straight from the
	 * tree at request time (`Helper::render_canvas_certificate`) — there is
	 * no client HTML blob in that format's storage contract, so none is
	 * stored, and any stale HTML meta from a certificate's pre-canvas history
	 * is cleared so nothing downstream could mistake it for authoritative.
	 * Accepts the tree as an array (decoded JSON body) or a JSON string; only
	 * stores a structurally-valid tree.
	 *
	 * @param int          $post_id Certificate post ID.
	 * @param array|string $tree    Design tree, decoded or as JSON.
	 * @param string|null  $html    Pre-rendered content HTML (flow trees only).
	 */
	protected function save_design( $post_id, $tree, $html ) {
		if ( is_string( $tree ) ) {
			$tree = json_decode( $tree, true );
		}
		if ( ! is_array( $tree ) || empty( $tree['root'] ) ) {
			return;
		}
		$json = wp_json_encode( $tree );
		if ( false === $json ) {
			return;
		}
		update_post_meta( $post_id, self::META_KEY, wp_slash( $json ) );

		$is_canvas = isset( $tree['version'] ) && 3 === (int) $tree['version'];
		if ( $is_canvas ) {
			delete_post_meta( $post_id, self::META_HTML );
		} elseif ( null !== $html ) {
			// Content is author-designed markup with inline styles; keep the
			// tags mPDF needs (tables, inline formatting, the QR marker div).
			update_post_meta( $post_id, self::META_HTML, wp_slash( self::sanitize_html( (string) $html ) ) );
		}
	}

	/**
	 * "Preview as PDF" renders a certificate tree against sample merge data
	 * and streams the real mPDF output inline. Canvas (`version: 3`) trees
	 * render directly from the submitted tree; flow (`version: 2`) trees use
	 * their saved, mPDF-safe HTML so saved certificates can be previewed from
	 * course settings as well as from the builder.
	 *
	 * @param \WP_REST_Request $request Request carrying the id and the in-progress tree.
	 */
	public function preview_item( $request ) {
		$id   = (int) $request['id'];
		$post = get_post( $id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return new \WP_Error( 'rest_not_found', __( 'Certificate not found.', 'academy' ), array( 'status' => 404 ) );
		}

		$tree = $request->get_param( 'tree' );
		if ( is_string( $tree ) ) {
			$tree = json_decode( $tree, true );
		}
		if ( ! is_array( $tree ) || empty( $tree['root'] ) ) {
			return new \WP_Error( 'rest_invalid_tree', __( 'A certificate design is required to preview.', 'academy' ), array( 'status' => 400 ) );
		}

		$sample = apply_filters(
			'academy_certificates/preview_sample_merge',
			array(
				'{{learner}}'          => __( 'Alexandria Montgomery-Fitzgerald III', 'academy' ),
				'{{course_title}}'     => __( 'Advanced Enterprise Cloud Architecture and Distributed Systems Design Masterclass', 'academy' ),
				'{{completion_date}}'  => date_i18n( get_option( 'date_format' ) ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				'{{verification_url}}' => home_url( '/' ),
			)
		);

		if ( 3 === (int) ( $tree['version'] ?? 0 ) ) {
			$root     = \AcademyCertificates\Helper::substitute_merge_tree( $tree['root'], $sample );
			$renderer = new \AcademyCertificates\Render\CanvasRenderer();
			$out      = $renderer->render_canvas( $root );
		} elseif ( 2 === (int) ( $tree['version'] ?? 0 ) ) {
			$html = get_post_meta( $id, self::META_HTML, true );
			if ( '' !== trim( (string) $html ) ) {
				$content  = str_replace( array_keys( $sample ), array_values( $sample ), $html );
				$renderer = new \AcademyCertificates\Render\Renderer();
				$out      = $renderer->render( $tree['root'], $content );
			} else {
				$content = str_replace( array_keys( $sample ), array_values( $sample ), $post->post_content );
				$blocks  = parse_blocks( $content );
				$size    = 'A4';
				$orientation = 'L';
				$css     = '';
				foreach ( $blocks as $block ) {
					if ( 'ablocks/academy-certificate' === ( $block['blockName'] ?? '' ) ) {
						$attrs       = $block['attrs'] ?? array();
						$size        = $attrs['pageSize'] ?? $size;
						$orientation = $attrs['pageOrientation'] ?? $orientation;
					}
					$block_html = render_block( $block );
					preg_match_all( '/<style\b[^>]*>(.*?)<\/style>/is', $block_html, $matches );
					$css .= implode( '', $matches[1] ?? array() );
				}
				$out = array(
					'html'        => $content,
					'css'         => str_replace( '>', ' ', $css ),
					'size'        => $size,
					'orientation' => $orientation,
				);
			}//end if
		} else {
			return new \WP_Error( 'rest_invalid_tree', __( 'The certificate design format is not supported for preview.', 'academy' ), array( 'status' => 400 ) );
		}//end if

		$preview_title = get_the_title( $id );
		if ( '' === $preview_title ) {
			$preview_title = __( 'Certificate preview', 'academy' );
		}

		$certificate_pdf = new \AcademyCertificates\PDF\Generator( 0, 0, $out['html'], $out['css'], $out['size'], $out['orientation'], true );
		return $certificate_pdf->preview_certificate( $preview_title );
	}

	/**
	 * Kses the stored content HTML down to the tags the certificate uses.
	 *
	 * @param string $html Content HTML from the builder.
	 */
	protected static function sanitize_html( $html ) {
		$common = array(
			'style'        => true,
			'class'        => true,
			'align'        => true,
			'valign'       => true,
			'width'        => true,
			'height'       => true,
			'border'       => true,
			'cellpadding'  => true,
			'cellspacing'  => true,
			'role'         => true,
			'title'        => true,
			'data-emb-qr'  => true,
			'data-emb-qr-fg' => true,
			'data-emb-qr-bg' => true,
			'data-emb-divider' => true,
			'data-emb-divider-color' => true,
			'data-emb-divider-thickness' => true,
			'data-emb-divider-style' => true,
			'data-emb-editable' => true,
			'data-emb-field'    => true,
		);
		$allowed = array();
		// 'text' (SVG) — the social block's icon labels — and 'circle', used
		// nowhere yet but cheap insurance against the same silent-strip bug.
		foreach ( array( 'table', 'thead', 'tbody', 'tr', 'td', 'th', 'div', 'span', 'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'svg', 'rect', 'circle', 'path', 'g', 'text', 'a' ) as $tag ) {
			$allowed[ $tag ] = $common;
		}
		$allowed['a']['href']         = true;
		$allowed['a']['target']       = true;
		$allowed['a']['rel']          = true;
		$allowed['img']               = $common + array(
			'src' => true,
			'alt' => true
		);
		$allowed['svg']              += array(
			'viewbox' => true,
			'xmlns' => true,
			'preserveaspectratio' => true,
			'fill' => true
		);
		$allowed['rect']             += array(
			'x' => true,
			'y' => true,
			'rx' => true,
			'ry' => true,
			'fill' => true,
			'stroke' => true,
			'stroke-width' => true
		);
		$allowed['circle']           += array(
			'cx' => true,
			'cy' => true,
			'r' => true,
			'fill' => true,
			'stroke' => true,
			'stroke-width' => true
		);
		$allowed['path']             += array(
			'd' => true,
			'fill' => true
		);
		// wp_kses drops a disallowed tag but keeps its text content, which is
		// exactly wrong for <text>: the label used to survive as bare text
		// dropped straight into the parent <svg> (no positioning, no font).
		$allowed['text']             += array(
			'x' => true,
			'y' => true,
			'dx' => true,
			'dy' => true,
			'text-anchor' => true,
			'dominant-baseline' => true,
			'fill' => true,
			'font-family' => true,
			'font-size' => true,
			'font-weight' => true,
		);
		return wp_kses( $html, $allowed );
	}

	protected function sanitize_title( $title ) {
		$title = sanitize_text_field( (string) $title );
		return '' !== $title ? $title : __( 'Untitled Certificate', 'academy' );
	}
}
