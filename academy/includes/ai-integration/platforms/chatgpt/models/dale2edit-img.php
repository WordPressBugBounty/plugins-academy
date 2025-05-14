<?php
namespace Academy\AiIntegration\Platforms\Chatgpt\Models;

use Academy\AiIntegration\Classes\{ Http, HttpResponse, FileStream };
use Academy\AiIntegration\Exceptions\InvalidResponseException;
use Academy\AiIntegration\Platforms\Chatgpt\Prompts\Abstracts\Prompt;
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
use Exception;
class Dale2editImg extends Dale2createImg {
	public const SUPPORTED_FILE_TYPES = [
		'image/png'
	];
	public string $base_url = 'https://api.openai.com/v1/images/edits';
	public function payload() : array {
		if ( ! isset( $_FILES['image'] ) || ! isset( $_FILES['mask'] ) ) {
			throw new Exception( __( 'image and mask field is required.', 'academy-pro' ) );
		}

		$image = $_FILES['image'];
		$mask  = $_FILES['mask'];

		if ( ! in_array( $image['type'], self::SUPPORTED_FILE_TYPES ) || ! in_array( $mask['type'], self::SUPPORTED_FILE_TYPES ) ) {
			throw new Exception( __( 'Only PNG Image is allowed.', 'academy-pro' ) );
		}

		return [
			'model'  => $this->name,
			'image'  => new FileStream( $image['tmp_name'] ),
			'mask'   => new FileStream( $mask['tmp_name'] ),
			'prompt' => $this->prompt->get()[0]['content'],
			'n'      => 1,
			'size'   => '1024x1024',
		];
	}
	public function request() : HttpResponse {
		$this->http->set_headers( $this->headers() );
		$this->http->set_multipart_form_data( $this->payload() );

		return $this->image_url_to_base64();
	}

}
