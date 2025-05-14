<?php
namespace Academy\AiIntegration\Platforms\Chatgpt\Messages;

use Academy\AiIntegration\Platforms\Chatgpt\Messages\Abstracts\Message;
use Academy\AiIntegration\Exceptions\InvalidValueException;
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class SystemMessage extends Message {
	protected array $defaults = [
		'character_limit' => 100,
		'html' => false,
	];
	protected string $role = 'system';
	protected string $content = '
		You are a helpful and approachable assistant. Your task is to generate a formal {type} for an online course based on the given content. The {type} should be concise, written in {language}, and limited to {character_limit} characters. Ensure the tone is {tone}.
	';

	public function get( array $input ) : array {
		if ( boolval( $input['html'] ?? false ) ) {
			$this->content .= '  Please use some html formatting tag if needed.';
		} else {
			$this->content .= ' Please respond in plaintext format, without using markdown, quotation marks, or HTML formatting.';
		}
		return parent::get( $input );
	}
	public function validate_character_limit( string $limit ) : bool {
		if ( intval( $limit ) <= 0 ) {
			throw new InvalidValueException( __( 'Character length must be greater than zero.', 'academy-pro' ) );
		}
		return true;
	}
}
