<?php
namespace Academy\Admin\Playlist\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Platform {
	public function detail() : array;
	public function videos() : array;
}
