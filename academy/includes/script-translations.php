<?php
namespace Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads translations for Academy's JS bundles when WordPress can't find a
 * JSON file for them.
 *
 * WordPress looks for `academy-{locale}-{md5}.json`, where md5 is the hash of
 * the built script path (e.g. `assets/build/frontendCurriculums.4.0.1.js`).
 * Translation tools name their JSON after the source files the .po file
 * references instead, and the version in the bundle name changes the hash on
 * every release, so translations made on the site (for example with a
 * translation plugin) never reached the React apps.
 *
 * When WordPress's own lookup fails, this class:
 * 1. accepts `academy-{locale}-{handle}.json` in wp-content/languages/plugins,
 *    which survives plugin updates, and otherwise
 * 2. builds the JSON from the `academy` .mo file already loaded for PHP,
 *    limited to the strings the bundle uses (the `.i18n.php` manifest
 *    written by build-tools/i18n-strings-webpack-plugin.js).
 */
class ScriptTranslations {

	const DOMAIN = 'academy';

	public static function init() {
		add_filter( 'pre_load_script_translations', [ __CLASS__, 'fallback' ], 20, 4 );
	}

	/**
	 * WordPress calls this once per file it tries and a last time with
	 * `$file = false` once every file is missing. Only that last call is
	 * handled here.
	 *
	 * @param string|false|null $translations JSON, or null to keep looking.
	 * @param string|false      $file         File WordPress is about to read.
	 * @param string            $handle       Script handle.
	 * @param string            $domain       Text domain.
	 * @return string|false|null
	 */
	public static function fallback( $translations, $file, $handle, $domain ) {
		if ( null !== $translations || false !== $file || self::DOMAIN !== $domain ) {
			return $translations;
		}

		$script = wp_scripts()->query( $handle );
		if ( ! $script || ! is_string( $script->src ) || 0 !== strpos( $script->src, ACADEMY_ASSETS_URI . 'build/' ) ) {
			return $translations;
		}

		$locale = determine_locale();
		if ( 'en_US' === $locale ) {
			return $translations;
		}

		$handle_file = WP_LANG_DIR . '/plugins/' . self::DOMAIN . '-' . $locale . '-' . $handle . '.json';
		if ( is_readable( $handle_file ) ) {
			// phpcs:disable WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- local file, not a remote URL
			return (string) file_get_contents( $handle_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
			// phpcs:enable WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
		}

		$json = self::from_loaded_mo( $script->src, $locale );

		return null === $json ? $translations : $json;
	}

	/**
	 * Jed JSON for the bundle's strings, from the loaded `academy` .mo file.
	 *
	 * @param string $src    Script URL.
	 * @param string $locale Locale.
	 * @return string|null JSON, or null when there is nothing to add.
	 */
	protected static function from_loaded_mo( $src, $locale ) {
		$relative = substr( strtok( $src, '?' ), strlen( ACADEMY_ASSETS_URI ) );
		$manifest = ACADEMY_ASSETS_DIR_PATH . preg_replace( '/\.js$/', '.i18n.php', $relative );
		if ( $manifest === ACADEMY_ASSETS_DIR_PATH . $relative || ! is_readable( $manifest ) ) {
			return null;
		}
		$strings = include $manifest;
		if ( empty( $strings[ self::DOMAIN ] ) || ! is_array( $strings[ self::DOMAIN ] ) ) {
			return null;
		}

		// Loads the .mo just in time if no PHP string of the domain has been
		// translated yet on this request.
		get_translations_for_domain( self::DOMAIN );

		$controller = \WP_Translation_Controller::get_instance();
		$entries    = $controller->get_entries( self::DOMAIN );
		if ( ! $entries ) {
			return null;
		}

		$messages = [];
		foreach ( $strings[ self::DOMAIN ] as $string ) {
			// MO keys are "context\4msgid"; the plural's msgid is not part of the key.
			$key = is_array( $string ) ? $string[1] . "\4" . $string[0] : $string;
			if ( ! isset( $entries[ $key ] ) || '' === $entries[ $key ] ) {
				continue;
			}
			$messages[ $key ] = explode( "\0", $entries[ $key ] );
		}
		if ( ! $messages ) {
			return null;
		}

		$headers = $controller->get_headers( self::DOMAIN );

		return wp_json_encode(
			[
				'domain'      => 'messages',
				'locale_data' => [
					'messages' => [
						'' => [
							'domain'       => 'messages',
							'lang'         => $locale,
							'plural-forms' => isset( $headers['Plural-Forms'] ) ? $headers['Plural-Forms'] : 'nplurals=2; plural=(n != 1);',
						],
					] + $messages,
				],
			]
		);
	}
}
