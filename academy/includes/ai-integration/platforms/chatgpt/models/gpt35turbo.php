<?php
namespace Academy\AiIntegration\Platforms\Chatgpt\Models;

use Academy\AiIntegration\Classes\Http;
use Academy\AiIntegration\Platforms\Chatgpt\Prompts\Abstracts\Prompt;
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class Gpt35turbo extends Gpt4o {
	public string $name = 'gpt-3.5-turbo';
}
