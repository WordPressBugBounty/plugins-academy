<?php

namespace AcademyCertificates\Ajax;

use Academy\Classes\AbstractAjaxHandler;
use Academy\Classes\EventStreamServer;

class FontDownloader extends AbstractAjaxHandler {

	protected $namespace = ACADEMY_PLUGIN_SLUG . '_certificates';

	protected static EventStreamServer $sse;

	public function __construct() {
		$this->actions = [
			'download_fonts' => [
				'capability' => 'manage_options',
				'callback'   => [ $this, 'download_fonts' ],
			],
		];
	}

	public function download_fonts() {
		self::$sse = new EventStreamServer();
		self::$sse->listen( function () {
			$this->fonts_download();
			update_option( 'academy_mpdf_fonts_downloaded', true );
			self::$sse->emitEvent( [
				'type'    => 'complete',
				'message' => esc_html__( 'Fonts download completed successfully!', 'academy' ),
			], true );
		} );
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
				$sse->emitEvent( [
					'type'    => 'message',
					'message' => esc_html__( 'Failed to create fonts directory.', 'academy' ),
				], true );
			}
		}

		$filepath = trailingslashit( $fonts_dir ) . $filename;

		$fp = fopen( $filepath, 'w+' );
		if ( ! $fp ) {
			$sse->emitEvent( [
				'type'    => 'message',
				'message' => esc_html__( 'Failed to open file for writing.', 'academy' ),
			], true );
		}
		fclose( $fp );

		add_action( 'requests-curl.before_send', [ __CLASS__, 'percentage_callback' ] );
		$result = wp_remote_get( $font_zip_url, [
			'stream'      => true,
			'filename'    => $filepath,
			'timeout'     => 300,
			'redirection' => 5,
		] );
		remove_action( 'requests-curl.before_send', [ __CLASS__, 'percentage_callback' ] );

		if ( wp_remote_retrieve_response_code( $result ) >= 400 ) {
			$sse->emitEvent( [
				'type'    => 'message',
				'message' => esc_html__( 'Failed to download zip file.', 'academy' ),
			], true );
		}
		$sse->emitEvent( [
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
			$sse->emitEvent( [
				'type'    => 'message',
				'message' => sprintf( __( 'Failed to extract zip file: %s', 'academy' ), $unzip_result->get_error_message() ),
			], true );
		}

		if ( $wp_filesystem->is_dir( $fonts_dir . 'alms-ttfonts' ) ) {
			$result = $wp_filesystem->move( $fonts_dir . 'alms-ttfonts', $fonts_dir . 'ttfonts', true );
			if ( ! $result ) {
				$sse->emitEvent( [
					'type'    => 'message',
					'message' => esc_html__( 'Failed to rename directory.', 'academy' ),
				], true );
			}
		}

		$sse->emitEvent( [
			'type'    => 'message',
			'message' => esc_html__( 'Unzip complete!', 'academy' ),
		] );

		// Delete the zip file
		if ( file_exists( $filepath ) ) {
			unlink( $filepath );
			$sse->emitEvent( [
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
				self::$sse->emitEvent( [
					'type'    => 'percentage',
					'message' => $percent,
				] );
			}
		} );
	}

}
