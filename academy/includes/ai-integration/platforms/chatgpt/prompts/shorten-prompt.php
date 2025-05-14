<?php
namespace Academy\AiIntegration\Platforms\Chatgpt\Prompts;

use Academy\AiIntegration\Platforms\Chatgpt\Messages\{ ShortenSystemMessage, UserMessage };
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class ShortenPrompt extends Abstracts\Prompt {
	protected array $message_classes = [
		ShortenSystemMessage::class,
		UserMessage::class,
	];
}
