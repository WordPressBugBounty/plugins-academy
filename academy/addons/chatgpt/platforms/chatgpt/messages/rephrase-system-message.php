<?php
namespace AcademyChatgpt\Platforms\Chatgpt\Messages;

use AcademyChatgpt\Platforms\Chatgpt\Messages\Abstracts\Message;
use AcademyChatgpt\Exceptions\InvalidValueException;
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class RephraseSystemMessage extends Message {
	protected array $defaults = [
		'html' => false,
		'tone' => 'friendly',
	];
	protected string $role = 'system';
	protected string $content = '
		Your task is to rephrase the provided text while maintaining its original meaning. Express the content differently without altering its intent. Ensuring the tone aligns with a {tone}.
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
