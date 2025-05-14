<?php
namespace Academy\AiIntegration\Platforms\Chatgpt\Prompts;

use Academy\AiIntegration\Platforms\Chatgpt\Messages\{ SimplifySystemMessage, UserMessage };
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class SimplifyPrompt extends Abstracts\Prompt {
	protected array $message_classes = [
		SimplifySystemMessage::class,
		UserMessage::class,
	];
}
