<?php
namespace Academy\AiIntegration\Platforms\Chatgpt\Prompts;

use Academy\AiIntegration\Platforms\Chatgpt\Messages\{ UserMessage };
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class CreateImgPrompt extends Abstracts\Prompt {
	protected array $message_classes = [
		UserMessage::class,
	];
}
