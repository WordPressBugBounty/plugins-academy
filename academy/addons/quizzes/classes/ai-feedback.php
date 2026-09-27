<?php
namespace AcademyQuizzes\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates a short, personalized feedback message for a single quiz answer.
 *
 * Provider order:
 *  1. The ChatGPT addon, if it's active and has an API key configured.
 *  2. WordPress's own AI Client (wp-includes/php-ai-client), if it has at
 *     least one provider registered and configured.
 *  3. Neither — callers get null and nothing is generated. This is optional
 *     enrichment on top of a quiz result, never a hard requirement, so a
 *     missing/misconfigured provider is silently skipped rather than surfaced
 *     as an error to the student.
 */
class AiFeedback {

	/**
	 * Whether any usable AI provider is available right now.
	 */
	public static function is_available(): bool {
		return self::is_chatgpt_ready() || self::is_wp_ai_client_ready();
	}

	/**
	 * Generates feedback text for one answered question, trying providers in
	 * order. Returns null if nothing could be generated.
	 *
	 * The context holds: question (the question text), given_answer and
	 * correct_answer (formatted for reading; the correct answer may be empty)
	 * and is_correct (whether the student's answer was correct).
	 *
	 * @param array $context The answer to explain.
	 */
	public static function generate( array $context ): ?string {
		if ( self::is_chatgpt_ready() ) {
			$feedback = self::generate_via_chatgpt( $context );
			if ( null !== $feedback ) {
				return $feedback;
			}
		}

		if ( self::is_wp_ai_client_ready() ) {
			$feedback = self::generate_via_wp_ai_client( $context );
			if ( null !== $feedback ) {
				return $feedback;
			}
		}

		return null;
	}

	/**
	 * Flattens the answer shapes produced by Helper::prepare_given_answer()
	 * and Helper::prepare_correct_answer() (a list of answer objects, or a
	 * single one for fillInTheBlanks/shortAnswer) into a plain, readable string.
	 *
	 * @param array $answer_data
	 */
	public static function format_answer( $answer_data ): string {
		if ( empty( $answer_data ) ) {
			return '';
		}

		// A single answer (fillInTheBlanks correct_answer, shortAnswer — a plain
		// associative array; imageAnswer items build the same shape) — wrap it.
		// Everything else (True/False, single/multiple choice) is a list of
		// stdClass DB rows straight from Classes\Query, not associative arrays.
		if ( is_object( $answer_data ) || ( is_array( $answer_data ) && isset( $answer_data['answer_title'] ) ) ) {
			$answer_data = array( $answer_data );
		}

		$titles = array();
		foreach ( (array) $answer_data as $answer ) {
			// Normalizes both stdClass rows and associative arrays alike.
			$answer = (array) $answer;
			$title  = (string) ( $answer['answer_title'] ?? '' );
			$title  = trim( wp_strip_all_tags( html_entity_decode( $title ) ) );

			if ( '' === $title && ! empty( $answer['image_url'] ) ) {
				$title = __( 'an image option', 'academy' );
			}

			if ( '' !== $title ) {
				$titles[] = $title;
			}
		}

		return implode( ', ', $titles );
	}

	private static function is_chatgpt_ready(): bool {
		return \Academy\Helper::get_addon_active_status( 'chatgpt' )
			&& '' !== trim( (string) \Academy\Helper::get_settings( 'chatgpt_api_key', '' ) );
	}

	private static function is_wp_ai_client_ready(): bool {
		if ( ! class_exists( '\WordPress\AiClient\AiClient' ) ) {
			return false;
		}

		try {
			$registry = \WordPress\AiClient\AiClient::defaultRegistry();
			foreach ( $registry->getRegisteredProviderIds() as $provider_id ) {
				if ( \WordPress\AiClient\AiClient::isConfigured( $provider_id ) ) {
					return true;
				}
			}
		} catch ( \Throwable $e ) {
			return false;
		}

		return false;
	}

	private static function generate_via_chatgpt( array $context ): ?string {
		$api_key = trim( (string) \Academy\Helper::get_settings( 'chatgpt_api_key', '' ) );
		$model   = \Academy\Helper::get_settings( 'chatgpt_model', 'gpt-3.5-turbo' );

		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'timeout' => 20, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- LLM completions routinely take >3s.
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => "Bearer {$api_key}",
				),
				'body'    => wp_json_encode(
					array(
						'model'    => $model,
						'messages' => array(
							array(
								'role'    => 'system',
								'content' => self::system_instruction(),
							),
							array(
								'role'    => 'user',
								'content' => self::build_user_prompt( $context ),
							),
						),
					)
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$text = trim( (string) ( $body['choices'][0]['message']['content'] ?? '' ) );

		return '' !== $text ? $text : null;
	}

	private static function generate_via_wp_ai_client( array $context ): ?string {
		try {
			$result = \WordPress\AiClient\AiClient::prompt( self::build_user_prompt( $context ) )
				->usingSystemInstruction( self::system_instruction() )
				->generateTextResult();

			$text = trim( $result->toText() );

			return '' !== $text ? $text : null;
		} catch ( \Throwable $e ) {
			return null;
		}
	}

	private static function system_instruction(): string {
		return 'You are a supportive tutor giving a student brief feedback on one quiz question they just answered. '
			. 'In 2-3 short sentences: say whether they got it right, explain why in simple terms, and if they got it '
			. 'wrong, briefly point them toward the correct idea without being condescending. Respond in plaintext, '
			. 'no markdown, no headings.';
	}

	private static function build_user_prompt( array $context ): string {
		$lines = array(
			'Question: ' . $context['question'],
			"Student's answer: " . $context['given_answer'],
		);

		if ( ! empty( $context['correct_answer'] ) ) {
			$lines[] = 'Correct answer: ' . $context['correct_answer'];
		}

		$lines[] = 'The student answered this ' . ( $context['is_correct'] ? 'correctly' : 'incorrectly' ) . '.';

		return implode( "\n", $lines );
	}
}
