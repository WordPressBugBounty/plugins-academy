<?php
/**
 * WP-CLI command: `wp academy seed`.
 *
 * A thin wrapper over {@see Manager} for generating and removing dummy data
 * from the command line.
 *
 * @package AcademySeeder\Classes
 */

namespace AcademySeeder\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cli {

	/**
	 * Seed dummy data.
	 *
	 * ## OPTIONS
	 *
	 * [--only=<keys>]
	 * : Comma-separated provider keys to seed (e.g. course,categories). Defaults
	 *   to the default-selected providers.
	 *
	 * [--count=<n>]
	 * : Override the record count for every selected provider.
	 *
	 * ## EXAMPLES
	 *
	 *     wp academy seed run
	 *     wp academy seed run --only=course
	 *
	 * @param array $args       Positional args (unused).
	 * @param array $assoc_args Flags.
	 *
	 * @return void
	 */
	public function run( $args, $assoc_args ) {
		$only = [];
		if ( ! empty( $assoc_args['only'] ) ) {
			$only = array_filter( array_map( 'trim', explode( ',', $assoc_args['only'] ) ) );
		}

		$default_count = isset( $assoc_args['count'] ) ? max( 0, (int) $assoc_args['count'] ) : null;

		$logger = static function ( $stage, $provider, $info ) {
			if ( 'before' === $stage ) {
				\WP_CLI::log( sprintf( '→ Seeding %s (%d)…', $provider->get_label(), (int) $info ) );
			} elseif ( 'error' === $stage ) {
				\WP_CLI::warning( sprintf( '%s: %s', $provider->get_label(), $info ) );
			}
		};

		$context = Manager::get_instance()->run( [
			'only'          => $only,
			'default_count' => $default_count,
			'logger'        => $logger,
		] );

		$summary = [];
		foreach ( $context->get_records() as $record ) {
			$summary[ $record['type'] ] = ( $summary[ $record['type'] ] ?? 0 ) + 1;
		}

		if ( empty( $summary ) ) {
			\WP_CLI::warning( 'Nothing was created. Are the required addons active?' );
			return;
		}

		foreach ( $summary as $type => $number ) {
			\WP_CLI::log( sprintf( '  %s: %d', $type, $number ) );
		}

		\WP_CLI::success( 'Dummy data seeded.' );
	}

	/**
	 * Remove everything previously seeded.
	 *
	 * ## EXAMPLES
	 *
	 *     wp academy seed reset
	 *
	 * @param array $args       Positional args (unused).
	 * @param array $assoc_args Flags (unused).
	 *
	 * @return void
	 */
	public function reset( $args, $assoc_args ) {
		$manager = Manager::get_instance();

		if ( ! $manager->has_manifest() ) {
			\WP_CLI::warning( 'No seeded data recorded — nothing to remove.' );
			return;
		}

		$deleted = $manager->reset();
		$total   = array_sum( $deleted );

		\WP_CLI::success( sprintf( 'Removed %d seeded object(s).', $total ) );
	}
}
