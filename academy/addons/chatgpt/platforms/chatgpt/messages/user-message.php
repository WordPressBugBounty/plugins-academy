<?php
namespace AcademyChatgpt\Platforms\Chatgpt\Messages;

use AcademyChatgpt\Platforms\Chatgpt\Messages\Abstracts\Message;
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
class UserMessage extends Message {
	protected string $role = 'user';
	protected string $content = '
		{prompt}
	';
}
