<?php
namespace AcademyChatgpt\Platforms\Chatgpt;

use Exception;
use Academy\Classes\AbstractAjaxHandler;
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

class Init extends AbstractAjaxHandler {
	protected $namespace = ACADEMY_PLUGIN_SLUG;
	protected string $api;
	protected string $model;
	protected string $img_model;
	protected array $models = [
		'gpt-4o'        => Models\Gpt4o::class,
		'gpt-4o-mini'   => Models\Gpt4oMini::class,
		'gpt-3.5-turbo' => Models\Gpt35turbo::class,
		'dall-e-2:create' => Models\Dale2createImg::class,
		'dall-e-2:edit' => Models\Dale2editImg::class,
	];
	protected array $prompts = [
		'text_generation'  => Prompts\TextGenerationPrompt::class,
		'change_tone'      => Prompts\ChangeTonePrompt::class,
		'translation'      => Prompts\TranslationPrompt::class,
		'rephrase'         => Prompts\RephrasePrompt::class,
		'simplify'         => Prompts\SimplifyPrompt::class,
		'shorten'          => Prompts\ShortenPrompt::class,
		'lengthen'         => Prompts\LengthenPrompt::class,
		'course_generation' => Prompts\CourseGenerationPrompt::class,
		'img:create_img'    => Prompts\CreateImgPrompt::class,
	];

	public function __construct() {
		$this->api     = $this->get_setting( 'chatgpt_api_key', '' );
		$this->model   = $this->get_setting( 'chatgpt_model', 'gpt-3.5-turbo' );
		$this->img_model = $this->get_setting( 'chatgpt_img_model', 'dall-e-2' );
		$this->actions = [
			'ai_integration/chatgpt' => [
				'callback'   => [ $this, 'handle' ],
				'capability' => 'read',
			],
			'ai_integration/chatgpt/img_handler' => [
				'callback'   => [ $this, 'img_handler' ],
				'capability' => 'read',
			],
			'ai_integration/chatgpt/key_test' => [
				'callback'   => [ $this, 'key_test' ],
				'capability' => 'manage_options',
			]
		];
	}

	protected function authorize() : void {
		if ( current_user_can( 'manage_options' ) ) {
			return;
		} elseif ( in_array( 'academy_instructor', array_values( wp_get_current_user()->roles ), true ) &&
			$this->get_setting( 'allow_instructor_to_use_chatgpt', true )
		) {
			return;
		}
		wp_send_json_error( __( 'Unauthorized.', 'academy' ), 401 );
	}

	public function key_test( array $payload_data ) : void {
		if ( empty( $key = $payload_data['key'] ?? '' ) ) {
			wp_send_json_error( __( 'Key field is required.', 'academy' ), 500 );
		}
		$this->api = $key;
		$this->handle( [
			'type' => 'test',
			'language' => 'english',
			'tone' => 'formal',
			'prompt_handler' => 'text_generation',
			'prompt'         => 'Hello GPT, are you working?',
		] );
	}
	public function handle( array $payload_data ) : void {
		$this->authorize();
		$prompt_handler = sanitize_text_field( $payload_data['prompt_handler'] ?? '' );
		if ( ! array_key_exists( $this->model, $this->models ) ) {
			wp_send_json_error( __( 'Model is Not Assigned.', 'academy' ), 500 );
		}

		if ( empty( $handler_class = $this->prompts[ $prompt_handler ] ?? '' ) ) {
			wp_send_json_error( __( 'Prompt handler is Not Assigned.', 'academy' ), 500 );
		}

		if ( empty( $this->api ) ) {
			wp_send_json_error( __( 'API key is required.', 'academy' ), 422 );
		}

		try {
			$response = ( $model = new $this->models[ $this->model ](
				$this->api,
				new $handler_class( $payload_data )
			) )->request();
			wp_send_json_success( $model->content );
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage(), 500 );
		}

	}

	public function img_handler( array $payload_data ) : void {
		$this->authorize();
		$type = sanitize_text_field( $payload_data['action_type'] ?? 'create' );
		$prompt_handler = 'img:' . sanitize_text_field( $payload_data['prompt_handler'] ?? 'create_img' );
		if ( ! in_array( $type, [ 'create', 'edit' ] ) ) {
			wp_send_json_error( __( 'action type field is required..', 'academy' ), 500 );
		}
		if ( ! array_key_exists( $this->img_model . ':' . $type, $this->models ) ) {
			wp_send_json_error( __( 'Model is Not Assigned.', 'academy' ), 500 );
		}

		if ( empty( $handler_class = $this->prompts[ $prompt_handler ] ?? '' ) ) {
			wp_send_json_error( __( 'Prompt handler is Not Assigned.', 'academy' ), 500 );
		}

		if ( empty( $this->api ) ) {
			wp_send_json_error( __( 'API key is required.', 'academy' ), 422 );
		}

		try {
			$response = ( $model = new $this->models[ $this->img_model() . ':' . $type ](
				$this->api,
				new $handler_class( $payload_data )
			) )->request();
			wp_send_json_success( $model->content );
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage(), 500 );
		}

	}

	protected function get_setting( string $name, $default = '' ) {
		return $GLOBALS['academy_settings']->{$name} ?? $default;
	}
}
