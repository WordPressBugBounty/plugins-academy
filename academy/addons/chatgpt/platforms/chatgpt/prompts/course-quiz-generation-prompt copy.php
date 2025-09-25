<?php
namespace AcademyChatgpt\Platforms\Chatgpt\Prompts;

use AcademyChatgpt\Interfaces\ExpectsJson;
use AcademyChatgpt\Platforms\Chatgpt\Messages\{ CourseAssignmentGenerationSystemMessage, UserMessage };
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class CourseAssignmentGenerationPrompt extends Abstracts\Prompt implements ExpectsJson {
	protected array $message_classes = [
		CourseAssignmentGenerationSystemMessage::class,
		UserMessage::class,
	];
}
