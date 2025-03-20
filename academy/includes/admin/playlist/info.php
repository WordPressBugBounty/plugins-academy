<?php
namespace Academy\Admin\Playlist;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Academy\Admin\Playlist\Interfaces\Platform;

class Info {
	public string $api;
	public string $playlist_id;
	private Platform $platform;
	public function __construct( string $api, string $playlist_id, string $platform ) {
		$this->playlist_id = $playlist_id;
		$this->api         = $api;
		$platform_ins = new $platform( $this );

		if ( ! ( $platform_ins instanceof Platform ) ) {
			throw new \Exception( 'Invalid Platform' );
		}
		$this->platform    = $platform_ins;
	}

	public function get() : Platform {
		return $this->platform;
	}
}
