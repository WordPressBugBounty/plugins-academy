<?php
namespace AcademyChatgpt\Platforms\Chatgpt\Messages\Abstracts;

use AcademyChatgpt\Exceptions\{ PlaceholderMissingException, InvalidValueException };
use Academy\Classes\Sanitizer;
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
abstract class Message {
	protected string $role;
	protected string $content;
	protected array $placeholders;
	protected array $defaults = [];
	protected bool $is_filled = false;

	public function __construct() {
		$this->find_placeholders();
	}

	protected function find_placeholders() : void {
		preg_match_all( '|\{(.+?)\}|mi', $this->content, $matches );
		$this->placeholders = array_filter( array_unique( $matches[1] ?? [] ) );
	}

	public function get( array $input ) : array {
		$input = Sanitizer::sanitize_payload(
			array_combine(
				$this->placeholders,
				array_fill(
					0,
					count( $this->placeholders ),
					'string'
				)
			),
			$input
		);
		$input = array_merge( $this->defaults, $input );
		if ( ! $this->is_filled && count( $missing_keys = array_diff( $this->placeholders, array_keys( $input ) ) ) === 0 ) {
			foreach ( $this->placeholders as $placeholder ) {
				if ( method_exists( $this, "validate_{$placeholder}" ) &&
					boolval( $msg = call_user_func( [ $this, "validate_{$placeholder}" ], $input[ $placeholder ] ?? '' ) ) === false
				) {
					throw new InvalidValueException( sprintf( __( 'Invalid value is provided for: %s.', 'academy' ), $placeholder ) );
				}
				$this->content = str_replace( '{' . $placeholder . '}', $input[ $placeholder ] ?? '', $this->content );
			}

			$this->is_filled = true;
		} else {
			throw new PlaceholderMissingException( sprintf( __( '%s fields are missing.', 'academy' ), implode( ',', $missing_keys ) ) );
		}
		return [
			'role' => trim( $this->role ),
			'content' => trim( $this->content ),
		];
	}
}
