<?php
namespace AcademyChatgpt\Platforms\Chatgpt\Prompts;

use AcademyChatgpt\Platforms\Chatgpt\Messages\{ ShortenSystemMessage, UserMessage };
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class ShortenPrompt extends Abstracts\Prompt {
	protected array $message_classes = [
		ShortenSystemMessage::class,
		UserMessage::class,
	];
}
