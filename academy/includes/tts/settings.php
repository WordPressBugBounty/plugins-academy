<?php
/**
 * Academy LMS — Text-to-Speech: frontend settings injection
 *
 * Reads TTS settings from the shared `academy_settings` option (managed by
 * the Learn Page settings tab in the Academy admin) and injects them into
 * `window.AcademyGlobal.tts` so the React lesson player can consume them
 * without an additional AJAX request.
 *
 * Autoloader path: Academy\TTS\Settings  →  includes/tts/settings.php
 */

namespace Academy\TTS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {

	public static function init(): void {
		$self = new self();
		add_filter( 'academy/assets/frontend_scripts_data', [ $self, 'get_text_reader_settings' ] );
	}

	/**
	 * Merges a `tts` key into AcademyGlobal so the React hook can read admin
	 * settings via `window.AcademyGlobal.tts` without a separate request.
	 *
	 * @param array $data Existing wp_localize_script data.
	 * @return array
	 */
	public function get_text_reader_settings( array $data ): array {
		$data['tts'] = [
			'enabled'     => (bool) \Academy\Helper::get_settings( 'is_enabled_lesson_text_reader', false ),
			'rate'        => (float) \Academy\Helper::get_settings( 'lesson_tts_rate', 1 ),
			'pitch'       => (float) \Academy\Helper::get_settings( 'lesson_tts_pitch', 1 ),
			'highlight'   => (bool) \Academy\Helper::get_settings( 'lesson_tts_highlight', true ),
			'auto_scroll' => (bool) \Academy\Helper::get_settings( 'lesson_tts_auto_scroll', true ),
			'hide_text'   => (bool) \Academy\Helper::get_settings( 'lesson_tts_hide_text', false ),
		];

		return $data;
	}
}
