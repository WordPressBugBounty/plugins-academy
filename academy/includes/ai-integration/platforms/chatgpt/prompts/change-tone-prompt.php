<?php
namespace Academy\AiIntegration\Platforms\Chatgpt\Prompts;

use Academy\AiIntegration\Platforms\Chatgpt\Messages\{ ChangeToneSystemMessage, UserMessage };
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class ChangeTonePrompt extends Abstracts\Prompt {
	protected array $message_classes = [
		ChangeToneSystemMessage::class,
		UserMessage::class,
	];
}
