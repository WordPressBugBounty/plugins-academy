<?php
namespace Academy\AiIntegration\Platforms\Chatgpt\Prompts;

use Academy\AiIntegration\Platforms\Chatgpt\Messages\{ TranslationSystemMessage, UserMessage };
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class TranslationPrompt extends Abstracts\Prompt {
	protected array $message_classes = [
		TranslationSystemMessage::class,
		UserMessage::class,
	];
}
