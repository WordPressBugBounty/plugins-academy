<?php
namespace Academy\AiIntegration\Platforms\Chatgpt\Prompts;

use Academy\AiIntegration\Platforms\Chatgpt\Messages\{ RephraseSystemMessage, UserMessage };
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class RephrasePrompt extends Abstracts\Prompt {
	protected array $message_classes = [
		RephraseSystemMessage::class,
		UserMessage::class,
	];
}
