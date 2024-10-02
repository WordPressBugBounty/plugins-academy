<?php
namespace Academy\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class FileUpload {

	public function upload_file( $file, $supported_file_types = [] ) {
		if ( ! empty( $file ) && ! empty( $file['name'] ) ) {
			$filename = $file['name'];
			do_action( 'academy/before_upload_file', $filename );
		}

		$this->create_folder();

		$results = array(
			'error' => apply_filters( 'academy/file_upload_error_message', __( 'Error occurred, please try again', 'academy' ) ),
			'path' => '',
			'url' => '',
			'file_name' => ''
		);

		$path = ( isset( $file['name'] ) ) ? sanitize_text_field( $file['name'] ) : '';
		$ext  = pathinfo( $path, PATHINFO_EXTENSION );

		if ( count( $supported_file_types ) && ! in_array( $ext, $supported_file_types, true ) ) {
			return apply_filters( 'academy/not_supported_upload_file_error_message', __( 'Invalid file extension', 'academy' ) );
		}

		$filename    = md5( time() ) . basename( $path );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$file        = ( isset( $file['tmp_name'] ) ) ? file_get_contents( sanitize_text_field( $file['tmp_name'] ) ) : '';
		$upload_file = wp_upload_bits( $filename, null, $file );

		if ( $upload_file['error'] ) {
			$results['error'] = $upload_file['error'];
			return $results;
		}

		rename( $upload_file['file'], $this->get_file_path( $filename ) );

		$file_data  = $this->get_file_data( $filename );
		$results['error'] = '';
		$results['path']        = $file_data['path'];
		$results['url']         = $file_data['url'];
		$results['file_name']   = $filename;

		return $results;
	}

	public function unzip_uploaded_file( $zip_file, $file_name ) {
		// Unzip
		$zip = new \ZipArchive();

		if ( $zip->open( $zip_file ) === true ) {
			// Specify the directory where you want to extract the files
			$extract_path = $this->get_upload_dir(); // Replace with the actual directory path

			// Extract the files to a directory with the same name as the zip file (without the extension)
			$zip_folder_name = preg_replace( '/[^A-Za-z0-9\-]/', '_', pathinfo( $zip_file, PATHINFO_FILENAME ) );
			$extracted_folder = $extract_path . '/' . $zip_folder_name;

			// Create the extracted folder if it doesn't exist
			if ( ! is_dir( $extracted_folder ) ) {
				mkdir( $extracted_folder, 0755, true );
			}

			// Extract the files
			$zip->extractTo( $extracted_folder );
			$zip->close();

			// Delete the original zip file
			unlink( $zip_file );

			return $zip_folder_name;
		}//end if
		return false;
	}

	public function get_file_data( $filename ) {
		return array(
			'path' => $this->get_file_path( $filename ),
			'url' => $this->get_file_url( $filename ),
		);
	}

	public function get_file_path( $filename ) {
		return $this->get_upload_dir() . '/' . $filename;
	}

	public function get_file_url( $filename ) {
		return $this->get_upload_url() . '/' . $filename;
	}

	public function get_upload_url() {
		$upload     = wp_upload_dir();
		$upload_url = $upload['baseurl'];
		$upload_url = $upload_url . '/academy_uploads';
		return $upload_url;
	}

	public function get_upload_dir() {
		$upload     = wp_upload_dir();
		$upload_dir = $upload['basedir'];
		$upload_dir = $upload_dir . '/academy_uploads';
		return $upload_dir;
	}
	public function create_folder() {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$upload_dir = $this->get_upload_dir();

		if ( ! $wp_filesystem->is_dir( $upload_dir ) ) {
			wp_mkdir_p( $upload_dir );
		}
	}
	public function delete_file( $file_name ) {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		if ( is_dir( $file_name ) ) {
			return $wp_filesystem->rmdir( $file_name, true );
		}
		return false;
	}
}
