<?php
namespace Academy\Admin\Playlist\Platforms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Academy\Admin\Playlist\Request;
use Academy\Admin\Playlist\Info;
use Academy\Admin\Playlist\Interfaces\Platform;
class Youtube implements Platform {
	private Info $Info;
	private Request $request;
	public function __construct( Info $info ) {
		$this->info    = $info;
		$this->request = new Request( 'https://www.googleapis.com/youtube/v3' );

	}

	public function detail() : array {
		return $this->request->get( "/playlists?part=snippet&id={$this->info->playlist_id}&key={$this->info->api}" )['items'] ?? [];
	}
	public function videos() : array {
		return $this->request->get_items( $this->info->playlist_id, $this->info->api );
	}
}
