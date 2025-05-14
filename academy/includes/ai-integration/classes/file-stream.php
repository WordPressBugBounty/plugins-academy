<?php
namespace Academy\AiIntegration\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
use Exception;
class FileStream {
	public string $name;
	public string $data;
	public string $mimi_type;

	public function __construct( string $file_path ) {
		if ( ! file_exists( $file_path ) || ! ( $this->mimi_type = mime_content_type( $file_path ) ) ) {
			throw new Exception( __( 'File is not exists.', 'academy-pro' ) );
		}
		$this->name = basename( $file_path );
		$this->data = file_get_contents( $file_path );
	}
}
