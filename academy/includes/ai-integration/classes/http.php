<?php
namespace Academy\AiIntegration\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
use Academy\AiIntegration\Exceptions\{ EmptyUrlException, ReqFailedException };
class Http {
	const METHOD_GET    = 'GET';
	const METHOD_POST   = 'POST';
	const METHOD_PUT    = 'PUT';
	const METHOD_PATCH  = 'PATCH';
	const METHOD_DELETE = 'DELETE';

	protected array $allowed_http_verb = [
		self::METHOD_GET,
		self::METHOD_POST,
		self::METHOD_PUT,
		self::METHOD_PATCH,
		self::METHOD_DELETE,
	];

	protected string $url;
	protected string $method      = self::METHOD_POST;
	protected bool $blocking      = true;
	protected string $httpversion = '1.0';
	protected array $payload      = [];
	protected array $headers      = [];
	protected array $cookies      = [];
	protected ?string $user_agent = null;
	protected int $redirection    = 0;
	protected int $timeout        = MINUTE_IN_SECONDS;
	protected ?MultipartFormData $multipart_form_data = null;

	public function __construct( string $url = '' ) {
		$this->url = $url;
	}

	public static function request( string $url ): Http {
		return new self( $url );
	}

	public function get( string $path = '' ) : HttpResponse {
		return $this->make( $path, self::METHOD_GET );
	}

	public function post( string $path = '' ) : HttpResponse {
		return $this->make( $path, self::METHOD_POST );
	}

	public function set_payload( array $data ): self {
		$this->payload = $data;

		return $this;
	}
	public function set_multipart_form_data( array $data ): self {
		$this->multipart_form_data = new MultipartFormData( $data );

		return $this;
	}

	public function set_headers( array $headers ): self {
		$this->headers = array_merge( $this->headers, $headers );

		return $this;
	}

	public function set_user_agent( string $user_agent ): self {
		$this->user_agent = $user_agent;

		return $this;
	}

	public function make( string $path = '', ?string $method = null ) : HttpResponse {
		if ( empty( $this->url . $path ) ) {
			throw new EmptyUrlException( __( 'URL is empty', 'academy-pro' ) );
		}

		if ( $method && in_array( strtoupper( $method ), $this->allowed_http_verb, true ) ) {
			$this->method = strtoupper( $method );
		}

		$args = [
			'redirection' => $this->redirection,
			'method'      => $this->method,
			'timeout'     => $this->timeout,
			'blocking'    => $this->blocking,
			'httpversion' => $this->httpversion,
		];

		if ( ! empty( $this->user_agent ) ) {
			$args['user-agent'] = $this->user_agent;
		}

		if ( ! empty( $this->headers ) ) {
			$args['headers'] = $this->headers;
		}

		if ( ! empty( $this->cookies ) ) {
			$args['cookies'] = $this->cookies;
		}

		if ( ! empty( $this->payload ) ) {
			$args['body'] = trim( wp_json_encode( $this->payload ) );
		}
		if ( ! is_null( $this->multipart_form_data ) ) {
			$args['body'] = $this->multipart_form_data->body;
			$args['headers'] = array_merge( $this->multipart_form_data->headers, $args['headers'] ?? [] );
		}
		$response = wp_remote_request( $this->url . $path, $args );

		if ( is_wp_error( $response ) ) {
			throw new ReqFailedException( __( 'Request failed.', 'academy-pro' ) );
		}

		return new HttpResponse( $response );

	}
}
