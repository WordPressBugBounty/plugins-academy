<?php
namespace AcademyChatgpt\Platforms\Chatgpt\Prompts;

use AcademyChatgpt\Platforms\Chatgpt\Messages\{ RephraseSystemMessage, UserMessage };
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class RephrasePrompt extends Abstracts\Prompt {
	protected array $message_classes = [
		RephraseSystemMessage::class,
		UserMessage::class,
	];
}
