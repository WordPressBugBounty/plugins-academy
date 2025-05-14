<?php
namespace AcademyChatgpt\Platforms\Chatgpt\Messages;

use AcademyChatgpt\Platforms\Chatgpt\Messages\Abstracts\Message;
use AcademyChatgpt\Exceptions\InvalidValueException;
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class ShortenSystemMessage extends Message {
	protected array $defaults = [
		'html' => false,
		'tone' => 'friendly',
	];
	protected string $role = 'system';
	protected string $content = '
		You are an assistant tasked with shortening the provided text. Just make it more concise while keeping the core meaning. Ensure the output is shorter than the original text. Ensuring the tone aligns with a {tone}.
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
