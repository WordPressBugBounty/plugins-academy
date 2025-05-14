<?php
namespace Academy\AiIntegration\Platforms\Chatgpt\Prompts;

use Academy\AiIntegration\Interfaces\ExpectsJson;
use Academy\AiIntegration\Platforms\Chatgpt\Messages\{ CourseGenerationSystemMessage, UserMessage };
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class CourseGenerationPrompt extends Abstracts\Prompt implements ExpectsJson {
	protected array $message_classes = [
		CourseGenerationSystemMessage::class,
		UserMessage::class,
	];
}
