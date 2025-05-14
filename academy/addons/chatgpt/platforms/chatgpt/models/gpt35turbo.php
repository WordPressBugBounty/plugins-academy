<?php
namespace AcademyChatgpt\Platforms\Chatgpt\Models;

use AcademyChatgpt\Classes\Http;
use AcademyChatgpt\Platforms\Chatgpt\Prompts\Abstracts\Prompt;
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class Gpt35turbo extends Gpt4o {
	public string $name = 'gpt-3.5-turbo';
}
