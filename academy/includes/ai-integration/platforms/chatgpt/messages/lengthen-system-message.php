<?php
namespace Academy\AiIntegration\Platforms\Chatgpt\Messages;

use Academy\AiIntegration\Platforms\Chatgpt\Messages\Abstracts\Message;
use Academy\AiIntegration\Exceptions\InvalidValueException;
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class LengthenSystemMessage extends Message {
	protected array $defaults = [
		'html' => false,
		'tone' => 'friendly',
	];
	protected string $role = 'system';
	protected string $content = '
		Your task is to enhance the provided text by adding more detail and depth, while staying true to the original meaning and intent. Ensuring the tone aligns with a {tone}.
	';

	public function get( array $input ) : array {
		if ( boolval( $input['html'] ?? false ) ) {
			$this->content .= '  Please use some html formatting tag if needed.';
		} else {
			$this->content .= ' Please respond in plaintext format, without using markdown, quotation marks, or HTML formatting.';
		}
		return parent::get( $input );
	}
}
