<?php
namespace Academy\AiIntegration\Platforms\Chatgpt\Messages;

use Academy\AiIntegration\Platforms\Chatgpt\Messages\Abstracts\Message;
use Academy\AiIntegration\Exceptions\InvalidValueException;
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class ChangeToneSystemMessage extends Message {
	protected array $defaults = [
		'html' => false,
	];
	protected string $role = 'system';
	protected string $content = '
		You are an assistant tasked with revising the provided text to adopt a {tone} tone. Maintain the original meaning of the content while ensuring the tone aligns with a {tone} style. Ensure that the number of sentences in the output is equal to the number of sentences in the input. Don’t exceed the input character limit, just change the tone.
	';

	public function get( array $input ) : array {
		if ( boolval( $input['html'] ?? false ) ) {
			$this->content .= ' Please use some html formatting tag if needed.';
		} else {
			$this->content .= ' Please respond in plaintext format, without using markdown, quotation marks, or HTML formatting.';
		}
		return parent::get( $input );
	}
}
