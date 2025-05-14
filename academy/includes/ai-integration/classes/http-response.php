<?php
namespace Academy\AiIntegration\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
use stdClass;
class HttpResponse {
	protected array $response;

	public function __construct( array $response ) {
		$this->response = $response;
	}

	public function get_cookie( string $name ) : string {
		return strval( wp_remote_retrieve_cookie_value( $this->response, $name ) );
	}

	public function get_header( string $name ) : string {
		return strval( wp_remote_retrieve_header( $this->response, $name ) );
	}

	public function status_code() : int {
		return intval( wp_remote_retrieve_response_code( $this->response ) );
	}

	public function status_msg() : string {
		return strval( wp_remote_retrieve_response_message( $this->response ) );
	}

	public function body() : string {
		return strval( wp_remote_retrieve_body( $this->response ) );
	}

	public function as_array() : array {
		$data = json_decode( $this->body(), true );
		return json_last_error() === JSON_ERROR_NONE ? $data : [];
	}

	public function as_object() : object {
		$data = json_decode( $this->body(), false );
		return json_last_error() === JSON_ERROR_NONE ? $data : new stdClass();
	}

}
