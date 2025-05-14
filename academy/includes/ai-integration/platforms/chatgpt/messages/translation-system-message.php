<?php
namespace Academy\AiIntegration\Platforms\Chatgpt\Messages;

use Academy\AiIntegration\Platforms\Chatgpt\Messages\Abstracts\Message;
use Academy\AiIntegration\Exceptions\InvalidValueException;
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class TranslationSystemMessage extends Message {
	protected array $defaults = [
		'html' => false,
		'tone' => 'friendly',
	];
	protected string $role = 'system';
	protected string $content = '
		Your task is to translate the provided text into {language}. If the text is not in {language}, first determine its original language. Make sure the translation faithfully reflects the meaning and intent of the original content. Ensuring the tone aligns with a {tone}.
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
