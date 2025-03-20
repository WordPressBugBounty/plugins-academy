<?php
namespace Academy\Admin\Playlist;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Request {
	private string $base;
	private string $platform;
	public function __construct( string $base ) {
		$this->base = $base;
	}

	public function get( string $path ) : array {
		$res = wp_remote_get( $this->base . $path );
		return is_wp_error( $res ) ? [] : json_decode( $res['body'] ?? '{}', true );
	}

	public function get_items( $playlist_id, $api_key ) : array {
		$next_page_token = '';
		$videos = [];
		do {
			$api_url = $this->base . "/playlistItems?part=snippet&maxResults=50&playlistId={$playlist_id}&key={$api_key}&pageToken={$next_page_token}";

			$response = wp_remote_get( $api_url );
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			if ( ! empty( $data['items'] ) ) {
				foreach ( $data['items'] as $item ) {
					$videos[] = [
						'title' => $item['snippet']['title'],
						'description' => $item['snippet']['description'],
						'video_id' => $item['snippet']['resourceId']['videoId']
					];
				}
			}

			$next_page_token = $data['nextPageToken'] ?? '';

		} while ( ! empty( $next_page_token ) );

		return ! is_wp_error( $response ) ? $videos : [];
	}
}
