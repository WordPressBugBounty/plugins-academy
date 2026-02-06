<?php
namespace AcademyChatgpt\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
use Exception;
class FileStream {
	public string $name;
	public string $data;
	public string $mimi_type;

	public function __construct( string $file_path ) {
		$this->mimi_type = mime_content_type( $file_path );
		if ( ! file_exists( $file_path ) || ! ( $this->mimi_type ) ) {
			throw new Exception( __( 'File is not exists.', 'academy' ) );
		}
		$this->name = basename( $file_path );
		$this->data = file_get_contents( $file_path );// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}
}
