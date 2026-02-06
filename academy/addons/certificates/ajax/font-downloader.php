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

	public function regenerate_academy_certificates() {
		$certificates = Helper::necessary_certificates();
		$post_type    = 'academy_certificate';

		if ( ! empty( $certificates ) ) {
			foreach ( $certificates as $certificate ) {
				$title = $certificate['title'] ?? '';

				if ( '' === $title ) {
					continue;
				}

				$post_slug = sanitize_title( $title );
				$existing = \Academy\Helper::get_page_by_slug( $post_slug, $post_type );

				if ( $existing instanceof \WP_Post ) {
					wp_delete_post( $existing->ID, true );
				}
			}
		}

		// Recreate certificates & update options
		$installer = new Installer();
		if ( method_exists( $installer, 'insert_default_certificate' ) ) {
			$installer->insert_default_certificate();
		}

		if ( method_exists( $installer, 'save_option' ) ) {
			$installer->save_option();
		}

		wp_send_json_success( __( 'Successfully Re-generated certificates.', 'academy' ) );
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
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		$fp = fopen( $filepath, 'w+' );
		if ( ! $fp ) {
			$sse->emit_event( [
				'type'    => 'message',
				'message' => esc_html__( 'Failed to open file for writing.', 'academy' ),
			], true );
		}
		fclose( $fp );// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

		add_action( 'requests-curl.before_send', [ __CLASS__, 'percentage_callback' ] );
		$result = wp_remote_get( $font_zip_url, [
			'stream'      => true,
			'filename'    => $filepath,
			'timeout'     => 300,
			'redirection' => 5,
		] );
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
				'message' => sprintf( __( 'Failed to extract zip file: %s', 'academy' ), $unzip_result->get_error_message() ),
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
			unlink( $filepath );
			$sse->emit_event( [
				'type'    => 'message',
				'message' => esc_html__( 'Zip file deleted.', 'academy' ),
			] );
		}
	}

	public static function percentage_callback( $args ) {
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
	}

}
