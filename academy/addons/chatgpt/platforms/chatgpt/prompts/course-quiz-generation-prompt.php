<?php
namespace AcademyChatgpt\Platforms\Chatgpt\Prompts;

use AcademyChatgpt\Interfaces\ExpectsJson;
use AcademyChatgpt\Platforms\Chatgpt\Messages\{ CourseQuizGenerationSystemMessage, UserMessage };
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class CourseQuizGenerationPrompt extends Abstracts\Prompt implements ExpectsJson {
	protected array $message_classes = [
		CourseQuizGenerationSystemMessage::class,
		UserMessage::class,
	];
}
