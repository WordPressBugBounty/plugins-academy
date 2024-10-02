<?php
namespace  Academy\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes\AbstractAjaxHandler;
use Academy\Classes\Pages;

class Tools extends AbstractAjaxHandler {
	public function __construct() {
		$this->actions = array(
			'fetch_academy_status' => array(
				'callback' => array( $this, 'fetch_academy_status' )
			),
			'fetch_academy_pages' => array(
				'callback' => array( $this, 'fetch_academy_pages' )
			),
			'regenerate_academy_pages' => array(
				'callback' => array( $this, 'regenerate_academy_pages' )
			),
		);
	}
	public function fetch_academy_status() {
		$tools     = new \Academy\Classes\Tools();
		$wordpress = $tools->get_wordpress_environment_status();
		$server    = $tools->get_server_environment_status();
		wp_send_json_success( [
			'wordpress' => $wordpress,
			'server'    => $server
		] );
	}
	public function fetch_academy_pages() {
		$pages = Pages::get_necessary_pages();
		wp_send_json_success( $pages );
	}

	public function regenerate_academy_pages() {
		$status = Pages::regenerate_necessary_pages();
		wp_send_json_success( $status );
	}
}
