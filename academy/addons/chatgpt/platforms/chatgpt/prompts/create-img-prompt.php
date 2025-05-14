<?php
namespace AcademyChatgpt\Platforms\Chatgpt\Prompts;

use AcademyChatgpt\Platforms\Chatgpt\Messages\{ UserMessage };
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class CreateImgPrompt extends Abstracts\Prompt {
	protected array $message_classes = [
		UserMessage::class,
	];
}
