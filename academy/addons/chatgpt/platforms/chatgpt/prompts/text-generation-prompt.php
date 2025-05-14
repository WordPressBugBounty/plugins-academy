<?php
namespace AcademyChatgpt\Platforms\Chatgpt\Prompts;

use AcademyChatgpt\Platforms\Chatgpt\Messages\{ SystemMessage, UserMessage };
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class TextGenerationPrompt extends Abstracts\Prompt {
	protected array $message_classes = [
		SystemMessage::class,
		UserMessage::class,
	];
}
