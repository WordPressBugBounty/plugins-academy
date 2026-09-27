<?php
namespace Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Small site state grouped into a few array options instead of one option
 * row per flag. Academy Pro uses it with its own option names.
 *
 * - `academy_db_versions`: table/schema version per component.
 * - `academy_migrations`: one-time jobs that already ran (or their progress).
 * - `academy_design_state`: pattern and layout page IDs, template style,
 *   theme palette sync.
 *
 * Each value is read from the group, so it is still one option row, loaded
 * with the rest of the autoloaded options.
 */
class Options {

	const DB_VERSIONS = 'academy_db_versions';

	const MIGRATIONS = 'academy_migrations';

	const DESIGN_STATE = 'academy_design_state';

	const PRO_DB_VERSIONS = 'academy_pro_db_versions';

	const PRO_MIGRATIONS = 'academy_pro_migrations';

	/**
	 * A value from a group.
	 *
	 * @param string $group   Group option name.
	 * @param string $key     Key in the group.
	 * @param mixed  $default Returned when the key isn't set.
	 * @return mixed
	 */
	public static function get( $group, $key, $default = null ) {
		$values = self::all( $group );
		return array_key_exists( $key, $values ) ? $values[ $key ] : $default;
	}

	/**
	 * Whether a group has the key, whatever its value.
	 *
	 * @param string $group Group option name.
	 * @param string $key   Key in the group.
	 * @return bool
	 */
	public static function has( $group, $key ) {
		return array_key_exists( $key, self::all( $group ) );
	}

	/**
	 * Store a value in a group.
	 *
	 * @param string $group Group option name.
	 * @param string $key   Key in the group.
	 * @param mixed  $value Value.
	 * @return void
	 */
	public static function set( $group, $key, $value ) {
		$values = self::all( $group );
		if ( array_key_exists( $key, $values ) && $values[ $key ] === $value ) {
			return;
		}
		$values[ $key ] = $value;
		update_option( $group, $values );
	}

	/**
	 * Remove a key from a group.
	 *
	 * @param string $group Group option name.
	 * @param string $key   Key in the group.
	 * @return void
	 */
	public static function delete( $group, $key ) {
		$values = self::all( $group );
		if ( ! array_key_exists( $key, $values ) ) {
			return;
		}
		unset( $values[ $key ] );
		update_option( $group, $values );
	}

	/**
	 * Every value in a group.
	 *
	 * @param string $group Group option name.
	 * @return array
	 */
	public static function all( $group ) {
		$values = get_option( $group, [] );
		return is_array( $values ) ? $values : [];
	}
}
