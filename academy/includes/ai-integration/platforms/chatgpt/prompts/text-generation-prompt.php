<?php
namespace Academy\AiIntegration\Platforms\Chatgpt\Prompts;

use Academy\AiIntegration\Platforms\Chatgpt\Messages\{ SystemMessage, UserMessage };
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class TextGenerationPrompt extends Abstracts\Prompt {
	protected array $message_classes = [
		SystemMessage::class,
		UserMessage::class,
	];
}
