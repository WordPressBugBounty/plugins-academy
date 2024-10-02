<?php
namespace Academy\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class ExportBase {
	public function array_to_xml( $data, &$xml ) {
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$subNode = $xml->addChild( $key );
				$this->array_to_xml( $value, $subNode );
			} else {
				$xml->addChild( $key, htmlspecialchars( $value ) );
			}
		}
	}

	public function flatten_array( $array, $prefix = '' ) {
		$result = array();
		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) ) {
				$result = array_merge( $result, $this->flatten_array( $value, $prefix . $key . '_' ) );
			} else {
				$result[ $prefix . $key ] = $value;
			}
		}
		return $result;
	}

	public function write_nested_csv( $array, $fp ) {
		foreach ( $array as $row ) {
			$flattenRow = $this->flatten_array( $row );
			fputcsv( $fp, $flattenRow );
		}
	}

	public function array_to_csv_download( $data, $filename = 'export.csv', $allow_header = true ) {
		header( 'Content-Type: application/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
		$f = fopen( 'php://output', 'w' );

		// Write the CSV header row
		if ( $allow_header ) {
			$headerRow = array_keys( $this->flatten_array( $data[0] ) );
			fputcsv( $f, $headerRow );
		}

		// Write the nested array data to CSV
		$this->write_nested_csv( $data, $f );
		// Close the output stream
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
		fclose( $f );
	}
}
