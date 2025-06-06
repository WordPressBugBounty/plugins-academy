<?php
namespace Academy\AiIntegration\Platforms\Chatgpt\Models;

use Academy\AiIntegration\Classes\Http;
use Academy\AiIntegration\Platforms\Chatgpt\Prompts\Abstracts\Prompt;
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class Gpt4o extends Abstracts\Model {
	public string $name = 'gpt-4o';
	public string $base_url = 'https://api.openai.com/v1/chat/completions';

	public function headers() : array {
		return [
			'Content-Type'  => 'application/json',
			'Accept'  => 'application/json',
			'Authorization' => "Bearer {$this->api}",
		];
	}
	public function payload() : array {
		return [
			'model'    => $this->name,
			'messages' => $this->prompt->get(),
		];
	}
}
