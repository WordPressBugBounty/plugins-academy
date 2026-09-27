<?php
namespace AcademyChatgpt\Platforms\Chatgpt\Models;

use AcademyChatgpt\Classes\{ Http, HttpResponse };
use AcademyChatgpt\Exceptions\InvalidResponseException;
use AcademyChatgpt\Platforms\Chatgpt\Prompts\Abstracts\Prompt;
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class Dale2createImg extends Abstracts\Model {
	public string $name = 'dall-e-2';
	public string $base_url = 'https://api.openai.com/v1/images/generations';

	public function headers(): array {
		return [
			'Content-Type'  => 'application/json',
			'Accept'  => 'application/json',
			'Authorization' => "Bearer {$this->api}",
		];
	}
	public function payload(): array {
		return [
			'model'  => $this->name,
			'prompt' => $this->prompt->get()[0]['content'],
			'n'      => 1,
			'size'   => '256x256',
		];
	}
	public function request(): HttpResponse {
		$this->http->set_headers( $this->headers() );
		$this->http->set_payload( $this->payload() );

		return $this->image_url_to_base64();
	}
	public function image_url_to_base64(): HttpResponse {
		$res = $this->http->post();
		$msg = ( $res->as_array()['error']['message'] ?? false );
		if ( $msg ) {
			throw new InvalidResponseException( esc_html( $msg ) );
		}
		$this->content = $res->as_array()['data'][0]['url'] ?? '';

		if ( empty( $this->content ) ) {
			throw new InvalidResponseException( esc_html__( 'Unable to handle this request.', 'academy' ) );
		}

		$image_response = wp_safe_remote_get( $this->content, [ 'timeout' => 15 ] ); // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- admin-only AI image generation; OpenAI's CDN can be slow.
		$this->content  = is_wp_error( $image_response ) || 200 !== (int) wp_remote_retrieve_response_code( $image_response ) ? false : wp_remote_retrieve_body( $image_response );
		if ( empty( $this->content ) ) {
			throw new InvalidResponseException( esc_html__( 'Unable to fetch image data', 'academy' ) );
		}
		$image_info = getimagesizefromstring( $this->content );
		$mime_type = $image_info['mime'];

		$base64_image = base64_encode( $this->content );// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$this->content = 'data:' . $mime_type . ';base64,' . $base64_image;
		return $res;
	}
}
