<?php
namespace Academy\AiIntegration\Platforms\Chatgpt\Models;

use Academy\AiIntegration\Classes\{ Http, HttpResponse };
use Academy\AiIntegration\Exceptions\InvalidResponseException;
use Academy\AiIntegration\Platforms\Chatgpt\Prompts\Abstracts\Prompt;
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class Dale2createImg extends Abstracts\Model {
	public string $name = 'dall-e-2';
	public string $base_url = 'https://api.openai.com/v1/images/generations';

	public function headers() : array {
		return [
			'Content-Type'  => 'application/json',
			'Accept'  => 'application/json',
			'Authorization' => "Bearer {$this->api}",
		];
	}
	public function payload() : array {
		return [
			'model'  => $this->name,
			'prompt' => $this->prompt->get()[0]['content'],
			'n'      => 1,
			'size'   => '1024x1024',
		];
	}
	public function request() : HttpResponse {
		$this->http->set_headers( $this->headers() );
		$this->http->set_payload( $this->payload() );

		return $this->image_url_to_base64();
	}
	public function image_url_to_base64() : HttpResponse {
		$res = $this->http->post();
		if ( $msg = ( $res->as_array()['error']['message'] ?? false ) ) {
			throw new InvalidResponseException( $msg );
		}
		$this->content = $res->as_array()['data'][0]['url'] ?? '';

		if ( empty( $this->content ) ) {
			throw new InvalidResponseException( __( 'Empty', 'academy-pro' ) );
		}

		$this->content = file_get_contents( $this->content );
		if ( $this->content === false ) {
			throw new InvalidResponseException( __( 'Unable to fetch image data', 'academy-pro' ) );
		}
		$image_info = getimagesizefromstring( $this->content );
		$mime_type = $image_info['mime'];

		$base64_image = base64_encode( $this->content );
		$this->content = 'data:' . $mime_type . ';base64,' . $base64_image;
		return $res;
	}
}
