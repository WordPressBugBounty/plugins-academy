<?php
namespace AcademyCertificates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyCertificates\PDF\Generator;

class Helper {

	public static function render_certificate( $course_id, $template_id, $student_id, $verification_id = '' ) {
		if ( ! get_option( 'academy_mpdf_fonts_downloaded', false ) ) {
			self::send_notice( __( 'Please download the fonts before generating the PDF.', 'academy' ) );
		}

		$certificate = get_post( $template_id );

		// New builder stores its design as a JSON tree in post meta; legacy
		// certificates store Gutenberg blocks in post_content. Bail only when
		// there is neither.
		$certificate_tree = get_post_meta( $template_id, '_academy_certificate_tree', true );

		if ( empty( $certificate_tree ) && empty( $certificate->post_content ) ) {
			return;
		}

		if ( ! $student_id && ! empty( $verification_id ) ) {
			global $wpdb;
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom query with no WP API equivalent
			$student_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT user_id 
					FROM {$wpdb->comments} 
					WHERE comment_content = %s 
					LIMIT 1",
					$verification_id
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}
		$user_data = get_userdata( $student_id );
		$fname = $user_data->first_name ?? '';
		$lname = $user_data->last_name ?? '';
		$student_name = $user_data->display_name ?? '';

		// Set student name if first and last names are available
		if ( ! empty( $fname ) && ! empty( $lname ) ) {
			$student_name = $fname . ' ' . $lname;
		}

		// Optional values, set to empty strings if data is unavailable
		$course_title = get_the_title( $course_id );
		$instructors = \Academy\Helper::get_instructors_by_course_id( $course_id );
		$instructor_name = ! empty( $instructors ) ? $instructors[0]->display_name : 'Instructor Missing';
		$course_place = get_bloginfo( 'name' ) ?? 'Course Place Missing';

		$course_completed = \Academy\Helper::is_completed_course( $course_id, $student_id, true, $verification_id );
		$completion_date  = ( $course_completed && ! empty( $course_completed->completion_date ) ) ? date_i18n( get_option( 'date_format' ), strtotime( $course_completed->completion_date ) ) : __( 'Completion Date Missing', 'academy' ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		$total_topics = \Academy\Helper::get_total_topic_title_by_course_id( $course_id );
		$course_requirement  = get_post_meta( $course_id, 'academy_course_materials_included', true );
		$course_materials  = get_post_meta( $course_id, 'academy_course_requirements', true );
		$what_learn       = get_post_meta( $course_id, 'academy_course_benefits', true );
		// Replace dynamic placeholders with available values or default messages
		$certificate_template_dynamic_code_args = apply_filters( 'academy_certificates/template_dynamic_codes', [ '{{learner}}', '{{course_title}}', '{{instructor}}', '{{course_place}}', '{{completion_date}}', '{{total_topics}}', '{{course_requirements}}', '{{course_materials}}', '{{what_you_will_learn}}' ] );
		$certificate_template_dynamic_variable_args = apply_filters( 'academy_certificates/template_dynamic_codes_variables', [ $student_name, $course_title, $instructor_name, $course_place, $completion_date, $total_topics, $course_requirement, $course_materials, $what_learn ], $student_id, $course_id );

		// `version: 3` = the CANVAS builder (absolute x/y/w/h placement) — PHP
		// renders straight from the tree, no client HTML blob involved. See
		// AcademyCertificates\Render\CanvasRenderer's docblock for why.
		$decoded_tree = self::decode_canvas_tree( $certificate_tree );
		if ( null !== $decoded_tree ) {
			return self::render_canvas_certificate(
				$decoded_tree,
				$course_id,
				$student_id,
				$verification_id,
				array_combine( $certificate_template_dynamic_code_args, $certificate_template_dynamic_variable_args )
			);
		}

		// New tree-based certificate → wrap the stored, merge-substituted HTML.
		// The html is produced by the builder on save, so a design that has a
		// tree but has never been saved (the bundled defaults ship with both a
		// tree for the editor AND their classic markup) still prints from
		// post_content rather than bailing out to a blank response.
		$certificate_html = get_post_meta( $template_id, '_academy_certificate_html', true );
		if ( ! empty( $certificate_tree ) && '' !== trim( (string) $certificate_html ) ) {
			return self::render_tree_certificate(
				$template_id,
				$course_id,
				$student_id,
				$verification_id,
				array_combine( $certificate_template_dynamic_code_args, $certificate_template_dynamic_variable_args )
			);
		}

		// A true legacy certificate — no tree meta at all, so it predates the
		// tree builder entirely — has, until now, always fallen through to
		// the raw-block path below, which renders correctly only while
		// ABlocks is active AND its block render_callback's own (separately
		// computed) merge substitution still agrees with this method's. That
		// silently broke for certificates carried over from before the
		// canvas builder existed. `LegacyMigrator` already converts this
		// exact markup into a clean, self-contained canvas tree for the
		// builder UI when an author opens it (see BuilderApi::get_item()) —
		// this does the same conversion for the actual PDF render path, so a
		// certificate self-heals on its very next render instead of needing
		// someone to open and re-save it first.
		if ( empty( $certificate_tree ) ) {
			require_once __DIR__ . '/legacy-migrator.php';
			$migrated_tree = LegacyMigrator::convert_to_canvas( (string) $certificate->post_content );
			if ( null !== $migrated_tree ) {
				return self::render_canvas_certificate(
					$migrated_tree,
					$course_id,
					$student_id,
					$verification_id,
					array_combine( $certificate_template_dynamic_code_args, $certificate_template_dynamic_variable_args )
				);
			}
		}

		$certificate_template = str_replace(
			$certificate_template_dynamic_code_args,
			$certificate_template_dynamic_variable_args,
			$certificate->post_content
		);

		$blocks = parse_blocks( $certificate_template );
		if ( ! empty( $blocks ) && 'ablocks/academy-certificate' === $blocks[0]['blockName'] ) {
			$attrs = $blocks[0]['attrs'];
			$pageSize = $attrs['pageSize'] ?? 'A4';
			$pageOrientation = $attrs['pageOrientation'] ?? 'L';
		}

		// Extract CSS from block content
		$cssContent = '';
		if ( ! empty( $blocks ) ) {
			foreach ( $blocks as $block ) {
				$htmlContent = render_block( $block );
				preg_match_all( '/<style\b[^>]*>(.*?)<\/style>/is', $htmlContent, $matches );
				if ( ! empty( $matches[1] ) ) {
					foreach ( $matches[1] as $cssBlock ) {
						$cssContent .= $cssBlock;
					}
				}
			}
		}

		// Sanitize CSS content
		$cssContent = str_replace( '>', ' ', $cssContent );

		// Generate PDF preview
		$certificate_pdf = new Generator( $course_id, $student_id, $certificate_template, $cssContent, $pageSize, $pageOrientation );
		return $certificate_pdf->preview_certificate( get_the_title( $course_id ) );
	}

	/**
	 * Render a tree-based (new builder) certificate to a PDF.
	 *
	 * The editor stores its block content as ready-to-print, mPDF-safe HTML
	 * (the mail-builder table output). Here we substitute the merge codes into
	 * that HTML and let the Renderer wrap it in the fixed-size page shell —
	 * no per-block rendering server-side.
	 *
	 * @param int    $template_id     Certificate post ID (holds the tree + html meta).
	 * @param int    $course_id
	 * @param int    $student_id
	 * @param string $verification_id
	 * @param array  $merge           token => value map from the dynamic-codes filters.
	 */
	protected static function render_tree_certificate( $template_id, $course_id, $student_id, $verification_id, array $merge ) {
		$tree = json_decode( get_post_meta( $template_id, '_academy_certificate_tree', true ), true );
		$html = get_post_meta( $template_id, '_academy_certificate_html', true );
		if ( ! is_array( $tree ) || empty( $tree['root'] ) || '' === trim( (string) $html ) ) {
			return;
		}

		$merge = self::with_verification_merge( $merge, $course_id, $student_id, $verification_id );

		// Substitute the dynamic codes into the stored content HTML.
		$content = str_replace( array_keys( $merge ), array_values( $merge ), $html );

		$renderer = new \AcademyCertificates\Render\Renderer();
		$out      = $renderer->render( $tree['root'], $content );

		$certificate_pdf = new Generator(
			$course_id,
			$student_id,
			$out['html'],
			$out['css'],
			$out['size'],
			$out['orientation'],
			true
		);
		return $certificate_pdf->preview_certificate( get_the_title( $course_id ) );
	}

	/**
	 * Render a `version: 3` (canvas) certificate straight from its tree data —
	 * no client HTML blob involved. Merge codes are substituted directly into
	 * each node's text-bearing attributes (`text`, the QR block's `value`)
	 * before handing the tree to `CanvasRenderer`, since there's no
	 * pre-rendered HTML string to string-replace into for this format.
	 *
	 * @param array  $tree            Decoded `version: 3` tree ({version, root}).
	 * @param int    $course_id
	 * @param int    $student_id
	 * @param string $verification_id
	 * @param array  $merge           token => value map from the dynamic-codes filters.
	 */
	protected static function render_canvas_certificate( array $tree, $course_id, $student_id, $verification_id, array $merge ) {
		$merge = self::with_verification_merge( $merge, $course_id, $student_id, $verification_id );
		$root  = self::substitute_merge_tree( $tree['root'], $merge );

		$renderer = new \AcademyCertificates\Render\CanvasRenderer();
		$out      = $renderer->render_canvas( $root );

		$certificate_pdf = new Generator(
			$course_id,
			$student_id,
			$out['html'],
			$out['css'],
			$out['size'],
			$out['orientation'],
			true
		);
		return $certificate_pdf->preview_certificate( get_the_title( $course_id ) );
	}

	/**
	 * Add the QR block's `{{verification_url}}` to a dynamic-codes merge map
	 * and run the `academy_certificates/tree_merge_map` filter — shared by
	 * both tree-based render paths (flow and canvas) since the verification
	 * link logic (prefer Academy Pro's per-student hash) doesn't depend on
	 * which builder produced the design.
	 *
	 * @param array  $merge           Token => value map.
	 * @param int    $course_id       Course the certificate is for.
	 * @param int    $student_id      Learner the certificate is for.
	 * @param string $verification_id Verification id from the request, if any.
	 * @return array
	 */
	protected static function with_verification_merge( array $merge, $course_id, $student_id, $verification_id ) {
		$verify_url = add_query_arg( array( 'source' => 'certificate' ), get_permalink( $course_id ) );
		// The same token embedded in `verify_url` above, exposed as its own
		// plain-text tag so a template can print it next to the QR code
		// (e.g. "Verification Code: {{verification_code}}") instead of only
		// encoding it into the URL.
		$verification_code = '';
		if ( class_exists( '\AcademyProCertificates\Helper' ) ) {
			$hash = \AcademyProCertificates\Helper::get_certificate_verification_hash_by_course_and_student_id( $student_id, $course_id );
			if ( $hash ) {
				$verification_code = $hash;
				$verify_url = add_query_arg( array(
					'source' => 'certificate',
					'verify' => $hash
				), get_permalink( $course_id ) );
			}
		} elseif ( ! empty( $verification_id ) ) {
			$verification_code = $verification_id;
			$verify_url = add_query_arg( array(
				'source' => 'certificate',
				'verify' => $verification_id
			), get_permalink( $course_id ) );
		}
		$merge['{{verification_url}}']  = $verify_url;
		$merge['{{verification_code}}'] = $verification_code;
		return apply_filters( 'academy_certificates/tree_merge_map', $merge, $student_id, $course_id );
	}

	/**
	 * Deep-copy `$root` substituting merge codes into every child's `text`
	 * (text/heading) and `value` (qr) attribute. Only these two carry
	 * merge-tag content in the canvas block set (see capabilities.js's
	 * `content`/`qr` capability groups) — everything else (colors, sizes,
	 * image URLs) is author-set, not templated.
	 *
	 * @param array $root  Tree root.
	 * @param array $merge Token => value map.
	 * @return array
	 */
	public static function substitute_merge_tree( array $root, array $merge ) {
		$tokens = array_keys( $merge );
		$values = array_values( $merge );
		if ( ! empty( $root['children'] ) && is_array( $root['children'] ) ) {
			foreach ( $root['children'] as &$child ) {
				if ( ! is_array( $child ) || empty( $child['attributes'] ) ) {
					continue;
				}
				foreach ( array( 'text', 'value' ) as $field ) {
					if ( isset( $child['attributes'][ $field ] ) && is_string( $child['attributes'][ $field ] ) ) {
						$child['attributes'][ $field ] = str_replace( $tokens, $values, $child['attributes'][ $field ] );
					}
				}
			}
			unset( $child );
		}
		return $root;
	}

	/**
	 * Decode a certificate's `_academy_certificate_tree` meta and return it
	 * ONLY if it's a well-formed `version: 3` (canvas) tree — null otherwise
	 * (empty meta, malformed JSON, a `version: 2` flow tree, or a decoded
	 * value with no `root`). Extracted from `render_certificate()`'s dispatch
	 * so the version-detection rule itself — the thing that decides which of
	 * three render paths a certificate takes — is unit-testable without the
	 * rest of that method's heavy WordPress/mPDF dependencies.
	 *
	 * @param string|false $certificate_tree_json Raw post meta value.
	 * @return array|null
	 */
	public static function decode_canvas_tree( $certificate_tree_json ) {
		if ( empty( $certificate_tree_json ) ) {
			return null;
		}
		$decoded = json_decode( $certificate_tree_json, true );
		if ( ! is_array( $decoded ) || empty( $decoded['root'] ) ) {
			return null;
		}
		if ( ! isset( $decoded['version'] ) || 3 !== (int) $decoded['version'] ) {
			return null;
		}
		return $decoded;
	}

	protected static function send_notice( string $message ) {
		?>
		<p>
			<?php echo esc_html( $message ); ?>
			<a href="<?php echo esc_url( home_url() ); ?>"><?php esc_html_e( 'Back to Home', 'academy' ); ?></a>
		</p>
		<?php
		exit;
	}

	public static function necessary_certificates() {
		$default_certificates = array(
			array(
				'title' => esc_html__( 'Certificate 1', 'academy' ),
				'file' => 'certificates/dummy-certificate/certificate-1.php',
			),
			array(
				'title' => esc_html__( 'Certificate 2', 'academy' ),
				'file' => 'certificates/dummy-certificate/certificate-2.php',
			),
			array(
				'title' => esc_html__( 'Certificate 3', 'academy' ),
				'file' => 'certificates/dummy-certificate/certificate-3.php',
			),
			array(
				'title' => esc_html__( 'Certificate 4', 'academy' ),
				'file' => 'certificates/dummy-certificate/certificate-4.php',
			),
			array(
				'title' => esc_html__( 'Certificate 5', 'academy' ),
				'file' => 'certificates/dummy-certificate/certificate-5.php',
			),
			array(
				'title' => esc_html__( 'Certificate 6', 'academy' ),
				'file' => 'certificates/dummy-certificate/certificate-6.php',
			),
			array(
				'title' => esc_html__( 'Certificate 7', 'academy' ),
				'file' => 'certificates/dummy-certificate/certificate-7.php',
			),
		);

		return $default_certificates;
	}

	/**
	 * The builder tree a bundled certificate opens with.
	 *
	 * The shipped defaults are classic Gutenberg designs held in post_content,
	 * which the block builder cannot show — open one and you get a blank page,
	 * and saving it would overwrite the design with nothing. Giving each default
	 * an equivalent tree makes them editable like any certificate the wizard
	 * creates. Mirrors dev_academy/.../CertificateBuilder/presets.js; keep the
	 * two in step.
	 *
	 * @param string $image Background image URL for this design.
	 * @return array
	 */
	public static function default_certificate_tree( $image ) {
		$n = 0;
		$node = function ( $type, $attributes ) use ( &$n ) {
			++$n;
			return array(
				'id'         => 'n_default_' . $n,
				'type'       => $type,
				'attributes' => $attributes,
				'children'   => array(),
			);
		};
		$type = function ( $size, $weight, $color, $height = 1.5 ) {
			return array(
				'fontFamily' => 'inherit',
				'fontSize'   => $size,
				'fontWeight' => $weight,
				'lineHeight' => $height,
				'color'      => $color,
			);
		};

		return array(
			'version' => 2,
			'root'    => array(
				'id'         => 'root',
				'type'       => 'certificate',
				'attributes' => array(
					'pageSize'     => 'A4',
					'orientation'  => 'landscape',
					'fontFamily'   => 'Poppins',
					'bgColor'      => '#FFFFFF',
					'bgImage'      => $image,
					'bgSize'       => 'cover',
					'contentBg'    => 'transparent',
					'padding'      => array( 64, 96, 64, 96 ),
					'contentWidth' => 1123,
				),
				'children'   => array(
					$node( 'heading', array(
						'text'       => __( 'Certificate of Achievement', 'academy' ),
						'tag'        => 'h1',
						'alignment'  => 'center',
						'typography' => $type( 34, '700', '#1F2937', 1.2 ),
					) ),
					$node( 'text', array(
						'text'       => __( 'This is proudly presented to', 'academy' ),
						'alignment'  => 'center',
						'typography' => $type( 15, '400', '#6B7280' ),
					) ),
					$node( 'heading', array(
						'text'       => '{{learner}}',
						'tag'        => 'h2',
						'alignment'  => 'center',
						'typography' => $type( 30, '700', '#111827', 1.3 ),
					) ),
					$node( 'divider', array(
						'color'     => '#C9A24B',
						'thickness' => 2,
						'width'     => 30,
						'alignment' => 'center',
						'padding'   => array( 12, 0, 16, 0 ),
					) ),
					$node( 'text', array(
						'text'       => __( 'for completing', 'academy' ) . ' {{course_title}}',
						'alignment'  => 'center',
						'typography' => $type( 14, '400', '#6B7280' ),
					) ),
					$node( 'spacer', array( 'height' => 14 ) ),
					$node( 'text', array(
						'text'       => '{{completion_date}}',
						'alignment'  => 'center',
						'typography' => $type( 13, '600', '#374151' ),
					) ),
				),
			),
		);
	}

	/**
	 * Background image URL for the nth bundled certificate (1-based).
	 *
	 * @param int $index 1-based position of the bundled certificate.
	 */
	public static function default_certificate_image( $index ) {
		return ACADEMY_ASSETS_URI . 'images/certificate/certificate-' . (int) $index . '.png';
	}

	/**
	 * Does this id still point at a real certificate?
	 *
	 * @param int $id Certificate post ID.
	 */
	public static function certificate_exists( $id ) {
		$post = $id ? get_post( (int) $id ) : null;
		return $post instanceof \WP_Post && 'academy_certificate' === $post->post_type;
	}

	/**
	 * Render a bundled certificate template to its stored markup.
	 *
	 * `require`, not `require_once`: the same template can legitimately be
	 * rendered more than once in a single request — a reset regenerates every
	 * default and the installer may run in the same breath — and a `_once`
	 * include produces NOTHING the second time, which would silently store an
	 * empty certificate.
	 *
	 * @param string $file Path relative to the addons directory.
	 * @return string The rendered markup, or '' when the template is missing.
	 */
	public static function get_default_certificate_content( $file ) {
		$path = ACADEMY_ADDONS_DIR_PATH . $file;
		if ( ! $file || ! file_exists( $path ) ) {
			return '';
		}
		ob_start();
		require $path;
		return (string) ob_get_clean();
	}
}
