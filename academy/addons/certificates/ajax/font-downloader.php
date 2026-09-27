<?php

namespace AcademyCertificates\Ajax;

use Academy\Classes\AbstractAjaxHandler;
use Academy\Classes\EventStreamServer;
use AcademyCertificates\Helper;
use AcademyCertificates\Installer;

class FontDownloader extends AbstractAjaxHandler {

	protected $namespace = ACADEMY_PLUGIN_SLUG . '_certificates';

	protected static EventStreamServer $sse;

	public function __construct() {
		$this->actions = [
			'download_fonts' => [
				'capability' => 'manage_options',
				'callback'   => [ $this, 'download_fonts' ],
			],
			'fetch_academy_certificates' => array(
				'callback' => array( $this, 'fetch_academy_certificates' )
			),
			'regenerate_academy_certificates' => array(
				'callback' => array( $this, 'regenerate_academy_certificates' )
			)
		];
	}

	public function download_fonts() {
		self::$sse = new EventStreamServer();
		self::$sse->listen( function () {
			$this->fonts_download();
			update_option( 'academy_mpdf_fonts_downloaded', true );
			self::$sse->emit_event( [
				'type'    => 'complete',
				'message' => esc_html__( 'Fonts download completed successfully!', 'academy' ),
			], true );
		} );
	}

	public function fetch_academy_certificates() {
		$certificates = Helper::necessary_certificates();
		$post_type    = 'academy_certificate';
		$certificate_args = [];
		if ( ! empty( $certificates ) ) {
			foreach ( $certificates as $certificate ) {
				$title = $certificate['title'] ?? '';

				if ( '' === $title ) {
					continue;
				}
				$post_slug = sanitize_title( $title );
				$existing = \Academy\Helper::get_page_by_slug( $post_slug, $post_type );

				if ( $existing instanceof \WP_Post ) {
					$certificate_args[] = (object) [
						'ID' => $existing->ID,
						'title' => $certificate['title'],
						'slug' => $post_slug,
					];
				}
			}
		}
		wp_send_json_success( $certificate_args );
	}

	/**
	 * Restore the bundled certificates to their shipped design.
	 *
	 * Restores IN PLACE. This used to hard-delete each default and let the
	 * installer insert a replacement, which gave every default a NEW post ID on
	 * every reset — silently orphaning any course whose
	 * `academy_course_certificate_id` (or the primary-certificate setting)
	 * pointed at one. Those courses then served the course page instead of a
	 * PDF, with nothing to say why. Keeping the post and rewriting its content
	 * restores the design without breaking a single reference.
	 *
	 * Only the seven bundled titles are touched; anything the author created is
	 * matched by neither slug nor title and is left completely alone.
	 */
	public function regenerate_academy_certificates() {
		$certificates = Helper::necessary_certificates();
		$post_type    = 'academy_certificate';
		$restored     = 0;
		$created      = 0;

		foreach ( $certificates as $index => $certificate ) {
			$title = $certificate['title'] ?? '';

			if ( '' === $title ) {
				continue;
			}

			$content   = Helper::get_default_certificate_content( $certificate['file'] ?? '' );
			$tree      = Helper::default_certificate_tree( Helper::default_certificate_image( $index + 1 ) );
			$post_slug = sanitize_title( $title );
			$existing  = \Academy\Helper::get_page_by_slug( $post_slug, $post_type );

			if ( ! $existing instanceof \WP_Post ) {
				$existing = \Academy\Helper::get_page_by_title( $title, $post_type );
			}

			if ( $existing instanceof \WP_Post ) {
				wp_update_post(
					array(
						'ID'           => $existing->ID,
						'post_title'   => $title,
						'post_content' => $content,
						'post_status'  => 'publish',
					)
				);
				// Re-seed the builder tree and drop the rendered html: the html
				// is only regenerated when the author saves, so leaving a stale
				// one would keep printing the design this reset just replaced.
				update_post_meta( $existing->ID, '_academy_certificate_tree', wp_slash( wp_json_encode( $tree ) ) );
				delete_post_meta( $existing->ID, '_academy_certificate_html' );
				++$restored;
				continue;
			}

			$new_id = wp_insert_post(
				array(
					'post_title'   => $title,
					'post_content' => $content,
					'post_status'  => 'publish',
					'post_type'    => $post_type,
				)
			);
			if ( $new_id && ! is_wp_error( $new_id ) ) {
				update_post_meta( $new_id, '_academy_certificate_tree', wp_slash( wp_json_encode( $tree ) ) );
			}
			++$created;
		}//end foreach

		$installer = new Installer();
		if ( method_exists( $installer, 'save_option' ) ) {
			$installer->save_option();
		}

		wp_send_json_success(
			sprintf(
				/* translators: 1: number of certificates restored in place. 2: number newly created. */
				__( 'Restored %1$d default certificates (%2$d re-created). Your own certificates were not touched.', 'academy' ),
				$restored,
				$created
			)
		);
	}

	private function fonts_download(): void {
		$font_zip_url = 'https://kodezen.com/wp-content/uploads/assets/alms-ttfonts.zip';
		$filename     = 'ttfonts.zip';

		$upload     = wp_upload_dir();
		$upload_dir = $upload['basedir'];
		$fonts_dir  = trailingslashit( $upload_dir ) . '/academy_uploads/mpdf/';
		$sse        = self::$sse;

		if ( ! is_dir( $fonts_dir ) ) {
			if ( ! wp_mkdir_p( $fonts_dir ) ) {
				$sse->emit_event( [
					'type'    => 'message',
					'message' => esc_html__( 'Failed to create fonts directory.', 'academy' ),
				], true );
			}
		}

		$filepath = trailingslashit( $fonts_dir ) . $filename;
		if ( ! wp_is_writable( $fonts_dir ) ) {
			$sse->emit_event( [
				'type'    => 'message',
				'message' => esc_html__( 'Failed to open file for writing.', 'academy' ),
			], true );
		}

		add_action( 'requests-curl.before_send', [ __CLASS__, 'percentage_callback' ] );
		// Admin-only, one-time download of a large font pack, streamed to disk
		// with live progress — hence the long timeout and plain wp_remote_get().
		// phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get, WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
		$result = wp_remote_get( $font_zip_url, [
			'stream'      => true,
			'filename'    => $filepath,
			'timeout'     => 300,
			'redirection' => 5,
		] );
		// phpcs:enable WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get, WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
		remove_action( 'requests-curl.before_send', [ __CLASS__, 'percentage_callback' ] );

		if ( wp_remote_retrieve_response_code( $result ) >= 400 ) {
			$sse->emit_event( [
				'type'    => 'message',
				'message' => esc_html__( 'Failed to download zip file.', 'academy' ),
			], true );
		}
		$sse->emit_event( [
			'type'    => 'message',
			'message' => esc_html__( 'Download complete. Extracting...', 'academy' ),
		] );

		// Load WP_Filesystem
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();
		global $wp_filesystem;

		$unzip_result = unzip_file( $filepath, $fonts_dir );
		if ( is_wp_error( $unzip_result ) ) {
			$sse->emit_event( [
				'type'    => 'message',
				/* translators: %s: unzip error message. */
				'message' => sprintf( esc_html__( 'Failed to extract zip file: %s', 'academy' ), esc_html( $unzip_result->get_error_message() ) ),
			], true );
		}

		if ( $wp_filesystem->is_dir( $fonts_dir . 'alms-ttfonts' ) ) {
			$result = $wp_filesystem->move( $fonts_dir . 'alms-ttfonts', $fonts_dir . 'ttfonts', true );
			if ( ! $result ) {
				$sse->emit_event( [
					'type'    => 'message',
					'message' => esc_html__( 'Failed to rename directory.', 'academy' ),
				], true );
			}
		}

		$sse->emit_event( [
			'type'    => 'message',
			'message' => esc_html__( 'Unzip complete!', 'academy' ),
		] );

		// Delete the zip file
		if ( file_exists( $filepath ) ) {
			wp_delete_file( $filepath );
			$sse->emit_event( [
				'type'    => 'message',
				'message' => esc_html__( 'Zip file deleted.', 'academy' ),
			] );
		}
	}

	public static function percentage_callback( $args ) {
		// Hooks the WP HTTP API's own cURL handle to stream download progress; there is no WP API for this.
		// phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_setopt
		curl_setopt( $args, CURLOPT_NOPROGRESS, false );
		curl_setopt( $args, CURLOPT_PROGRESSFUNCTION, function ( $resource, $download_size, $downloaded ) {
			if ( $download_size > 0 ) {
				$percent = round( ( $downloaded / $download_size ) * 100 );
				self::$sse->emit_event( [
					'type'    => 'percentage',
					'message' => $percent,
				] );
			}
		} );
		// phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_setopt
	}
}
