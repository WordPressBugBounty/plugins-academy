<?php
namespace Academy\AiIntegration\Platforms\Chatgpt\Messages;

use Academy\AiIntegration\Platforms\Chatgpt\Messages\Abstracts\Message;
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class UserMessage extends Message {
	protected string $role = 'user';
	protected string $content = '
		{prompt}
	';
}
