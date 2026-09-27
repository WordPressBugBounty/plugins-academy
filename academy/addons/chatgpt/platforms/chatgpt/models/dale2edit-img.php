<?php
namespace AcademyChatgpt\Platforms\Chatgpt\Models;

use AcademyChatgpt\Classes\{ Http, HttpResponse, FileStream };
use AcademyChatgpt\Exceptions\InvalidResponseException;
use AcademyChatgpt\Platforms\Chatgpt\Prompts\Abstracts\Prompt;
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
use Exception;
class Dale2editImg extends Dale2createImg {
	public const SUPPORTED_FILE_TYPES = [
		'image/png'
	];
	public string $base_url = 'https://api.openai.com/v1/images/edits';
	public function payload(): array {
		// Nonce + capability are verified by AbstractAjaxHandler::handle_ajax_request() before any model runs.
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_FILES['image']['tmp_name'], $_FILES['mask']['tmp_name'] ) ) {
			throw new Exception( esc_html__( 'image and mask field is required.', 'academy' ) );
		}

		// Uploaded-file arrays: only tmp_name (a PHP-generated path) is used; the type is checked on the file itself.
		$image = $_FILES['image']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$mask  = $_FILES['mask']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( ! is_uploaded_file( $image['tmp_name'] ) || ! is_uploaded_file( $mask['tmp_name'] ) ) {
			throw new Exception( esc_html__( 'image and mask field is required.', 'academy' ) );
		}

		// The client-supplied MIME type can't be trusted — check the actual file contents.
		$image_type = wp_get_image_mime( $image['tmp_name'] );
		$mask_type  = wp_get_image_mime( $mask['tmp_name'] );
		if ( ! in_array( $image_type, self::SUPPORTED_FILE_TYPES, true ) || ! in_array( $mask_type, self::SUPPORTED_FILE_TYPES, true ) ) {
			throw new Exception( esc_html__( 'Only PNG Image is allowed.', 'academy' ) );
		}
		return [
			'model'  => $this->name,
			'image'  => new FileStream( $image['tmp_name'] ),
			'mask'   => new FileStream( $mask['tmp_name'] ),
			'prompt' => $this->prompt->get()[0]['content'],
			'n'      => 1,
			'size'   => '256x256',
		];
	}
	public function request(): HttpResponse {
		$this->http->set_headers( $this->headers() );
		$this->http->set_multipart_form_data( $this->payload() );

		return $this->image_url_to_base64();
	}
}
