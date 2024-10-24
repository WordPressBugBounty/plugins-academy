<?php

namespace AcademyEasyDigitalDownloads\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes\AbstractAjaxHandler;
use Academy\Classes\Sanitizer;
use Academy\Helper;
use EDD_Download;

class Admin extends AbstractAjaxHandler {
	protected $namespace = ACADEMY_PLUGIN_SLUG . '_edd';

	public function __construct() {
		$this->actions = array(
			'get_download' => array(
				'callback' => array( $this, 'edd_get_download' ),
				'capability' => 'manage_academy_instructor',
			),
			'fetch_downloads' => array(
				'callback' => array( $this, 'edd_fetch_downloads' ),
				'capability' => 'manage_academy_instructor',
			),
		);
	}

	public function edd_get_download( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'productId' => 'integer',
		], $payload_data );

		$downloadId = isset( $payload['productId'] ) ? $payload['productId'] : 0;
		$download   = new \EDD_Download( $downloadId );
		if ( $download->ID ) {
			$response = [
				'download_id'   => $downloadId,
				'regular_price' => $download->get_price(),
			];
			wp_send_json_success( $response );
		}
		wp_send_json_error( [
			'message' => 'Download not found'
		] );
	}

	public function edd_fetch_downloads() {
		$downloads = Helper::get_edd_products();
		$results   = [];
		if ( $downloads ) {
			foreach ( $downloads as $download ) {
				$results[] = array(
					'label' => $download->post_title,
					'value' => $download->ID,
				);
			}
		}
		wp_send_json_success( $results );
	}
}
