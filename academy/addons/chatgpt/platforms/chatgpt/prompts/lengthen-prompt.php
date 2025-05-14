<?php
namespace AcademyChatgpt\Platforms\Chatgpt\Prompts;

use AcademyChatgpt\Platforms\Chatgpt\Messages\{ LengthenSystemMessage, UserMessage };
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class LengthenPrompt extends Abstracts\Prompt {
	protected array $message_classes = [
		LengthenSystemMessage::class,
		UserMessage::class,
	];
}
