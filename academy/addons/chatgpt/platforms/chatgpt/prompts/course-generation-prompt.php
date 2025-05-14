<?php
namespace AcademyChatgpt\Platforms\Chatgpt\Prompts;

use AcademyChatgpt\Interfaces\ExpectsJson;
use AcademyChatgpt\Platforms\Chatgpt\Messages\{ CourseGenerationSystemMessage, UserMessage };
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class CourseGenerationPrompt extends Abstracts\Prompt implements ExpectsJson {
	protected array $message_classes = [
		CourseGenerationSystemMessage::class,
		UserMessage::class,
	];
}
