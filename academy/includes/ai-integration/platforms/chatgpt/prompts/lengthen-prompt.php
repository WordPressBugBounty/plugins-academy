<?php
namespace Academy\AiIntegration\Platforms\Chatgpt\Prompts;

use Academy\AiIntegration\Platforms\Chatgpt\Messages\{ LengthenSystemMessage, UserMessage };
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class LengthenPrompt extends Abstracts\Prompt {
	protected array $message_classes = [
		LengthenSystemMessage::class,
		UserMessage::class,
	];
}
