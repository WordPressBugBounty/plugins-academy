<?php
namespace AcademyChatgpt\Platforms\Chatgpt\Models\Abstracts;

use AcademyChatgpt\Classes\{ Http, HttpResponse };
use AcademyChatgpt\Exceptions\InvalidResponseException;
use AcademyChatgpt\Platforms\Chatgpt\Prompts\Abstracts\Prompt;
use AcademyChatgpt\Interfaces\ExpectsJson;
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
abstract class Model {
	public string $name;
	public string $api;
	public string $base_url;
	public Http $http;
	public Prompt $prompt;
	public $content;

	public function __construct( string $api, Prompt $prompt ) {
		$this->http   = Http::request( $this->base_url );
		$this->api    = $api;
		$this->prompt = $prompt;
	}
	abstract public function headers() : array;

	abstract public function payload() : array;

	public function request() : HttpResponse {
		$this->http->set_headers( $this->headers() );
		$this->http->set_payload( $this->payload() );

		$res = $this->http->post();
		if ( $msg = ( $res->as_array()['error']['message'] ?? false ) ) {
			throw new InvalidResponseException( $msg );
		}
		$this->content = $res->as_array()['choices'][0]['message']['content'] ?? '';
		if ( $this->prompt instanceof ExpectsJson ) {
			preg_match( '|\{.*\}|s', $this->content, $matches );
			if ( ! isset( $matches[0] ) ) {
				throw new InvalidResponseException( $this->content );
			}
			$this->content = json_decode( $matches[0], true );
			$this->content = json_last_error() === JSON_ERROR_NONE ? $this->content : [];
		}

		if ( empty( $this->content ) ) {
			throw new InvalidResponseException( __( 'Unable to handle this request.', 'academy' ) );
		}

		return $res;
	}
}
