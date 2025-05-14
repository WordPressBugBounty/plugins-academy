<?php
namespace Academy\AiIntegration\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class MultipartFormData {
	protected string $boundary;
	protected array $payload;
	public string $body;
	public array $headers;

	public function __construct( array $payload ) {
		$this->payload = $payload;
		$this->boundary = uniqid();
		$this->body     = '';
		$this->process();
	}
	protected function make_array_flat( array $array, ?string $prefix = null ) : array {
		$result = [];
		foreach ( $array as $key => $value ) {
			$prefixed_key = $prefix ? $prefix . '[' . $key . ']' : $key;
			if ( is_array( $value ) ) {
				$result = array_merge( $result, $this->make_array_flat( $value, $prefixed_key ) );
			} else {
				$result[ $prefixed_key ] = $value;
			}
		}
		return $result;
	}

	protected function process() : void {
		foreach ( $this->make_array_flat( $this->payload ) as $key => $value ) {
			if ( is_object( $value ) && $value instanceof FileStream ) {
				$this->body .= $this->process_file( $key, $value );
			} else {
				$this->body .= $this->process_text( $key, $value );
			}
		}
		$this->body .= '--' . $this->boundary . '--';

		$this->headers = [
			'Content-Type'  => 'multipart/form-data; boundary=' . $this->boundary,
			'Content-Length' => strlen( $this->body ),
		];
	}

	protected function process_text( string $key, string $value ) :string {
		$text = '--' . $this->boundary . "\r\n";
		$text .= 'Content-Disposition: form-data; name="' . $key . "\"\r\n\r\n";
		$text .= $value . "\r\n";
		return $text;
	}

	protected function process_file( string $key, FileStream $file ) : string {
		$fs = '--' . $this->boundary . "\r\n";
		$fs .= 'Content-Disposition: form-data; name="' . $key . '"; filename="' . $file->name . "\"\r\n";
		$fs .= 'Content-Type: ' . $file->mimi_type . "\r\n\r\n";
		$fs .= $file->data . "\r\n";
		return $fs;
	}
}
