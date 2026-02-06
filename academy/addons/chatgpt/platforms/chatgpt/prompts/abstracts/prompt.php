<?php
namespace AcademyChatgpt\Platforms\Chatgpt\Prompts\Abstracts;

use AcademyChatgpt\Platforms\Chatgpt\Messages\Abstracts\Message;
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
abstract class Prompt {
	protected array $input;
	protected array $message_classes;
	public function __construct( array $input ) {
		$this->input = $input;
	}

	public function get() : array {
		$messages = [];
		foreach ( $this->message_classes as $message ) {
			$ins = new $message();
			if (
				class_exists( $message ) &&
				( $ins instanceof Message )
			) {
				$messages[] = $ins->get( $this->input );
			}
		}
		return $messages;
	}
}
