<?php
namespace Academy\AiIntegration\Platforms\Chatgpt\Prompts\Abstracts;

use Academy\AiIntegration\Platforms\Chatgpt\Messages\Abstracts\Message;
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
			if (
				class_exists( $message ) &&
				( ( $ins = new $message() ) instanceof Message )
			) {
				$messages[] = $ins->get( $this->input );
			}
		}
		return $messages;
	}
}
